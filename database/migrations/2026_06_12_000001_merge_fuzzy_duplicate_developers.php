<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Developer;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $allDevs = Developer::all();
        $processedIds = [];

        foreach ($allDevs as $currentDev) {
            if (in_array($currentDev->id, $processedIds)) {
                continue;
            }

            // Find all developers that match this developer fuzzy-wise
            $matches = collect([$currentDev]);
            $processedIds[] = $currentDev->id;

            foreach ($allDevs as $otherDev) {
                if (in_array($otherDev->id, $processedIds)) {
                    continue;
                }

                if ($this->areFuzzyDuplicates($currentDev, $otherDev)) {
                    $matches->push($otherDev);
                    $processedIds[] = $otherDev->id;
                }
            }

            if ($matches->count() > 1) {
                // Determine the primary developer:
                // 1. Has external_id
                // 2. Earliest created
                $primary = $matches->sortBy(function ($dev) {
                    return [
                        empty($dev->external_id) ? 1 : 0,
                        $dev->id
                    ];
                })->first();

                $duplicates = $matches->reject(fn($d) => $d->id === $primary->id);

                foreach ($duplicates as $other) {
                    // Update primary with fields from duplicate if primary lacks them
                    $primaryUpdate = [];
                    if (empty($primary->external_id) && !empty($other->external_id)) {
                        $primaryUpdate['external_id'] = $other->external_id;
                    }
                    if (empty($primary->email) && !empty($other->email)) {
                        $primaryUpdate['email'] = $other->email;
                    }
                    if (empty($primary->avatar) && !empty($other->avatar)) {
                        $primaryUpdate['avatar'] = $other->avatar;
                    }
                    if (!empty($primaryUpdate)) {
                        $primary->update($primaryUpdate);
                    }

                    // Re-assign commits
                    DB::table('commits')->where('developer_id', $other->id)
                        ->update(['developer_id' => $primary->id]);

                    // Re-assign pull requests
                    DB::table('pull_requests')->where('developer_id', $other->id)
                        ->update(['developer_id' => $primary->id]);

                    // Re-assign reviews
                    DB::table('reviews')->where('reviewer_id', $other->id)
                        ->update(['reviewer_id' => $primary->id]);

                    // Re-assign deployments
                    DB::table('deployments')->where('developer_id', $other->id)
                        ->update(['developer_id' => $primary->id]);

                    // Re-assign tasks
                    DB::table('tasks')->where('developer_id', $other->id)
                        ->update(['developer_id' => $primary->id]);

                    // Re-assign bug fixes
                    DB::table('bug_fixes')->where('developer_id', $other->id)
                        ->update(['developer_id' => $primary->id]);

                    // Re-assign repository relations
                    $repoIds = DB::table('developer_repository')
                        ->where('developer_id', $other->id)
                        ->pluck('repository_id');

                    foreach ($repoIds as $repoId) {
                        $exists = DB::table('developer_repository')
                            ->where('developer_id', $primary->id)
                            ->where('repository_id', $repoId)
                            ->exists();

                        if (!$exists) {
                            DB::table('developer_repository')->insert([
                                'developer_id' => $primary->id,
                                'repository_id' => $repoId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                    DB::table('developer_repository')
                        ->where('developer_id', $other->id)
                        ->delete();

                    // Delete metrics and insights of the duplicate
                    DB::table('developer_metrics')->where('developer_id', $other->id)->delete();
                    DB::table('developer_insights')->where('developer_id', $other->id)->delete();

                    // Finally, delete the duplicate developer record
                    DB::table('developers')->where('id', $other->id)->delete();
                }

                // Recalculate metrics for the primary developer
                try {
                    $metricService = app(\App\Services\Metrics\DeveloperMetricService::class);
                    $metrics = \App\Models\DeveloperMetric::where('developer_id', $primary->id)->get();
                    foreach ($metrics as $metric) {
                        $start = \Carbon\Carbon::parse($metric->period_start);
                        $end = \Carbon\Carbon::parse($metric->period_end);
                        $results = $metricService->calculate($primary, $start, $end);
                        $metric->update($results);
                    }
                } catch (\Throwable $e) {
                    // Ignore/log errors during testing
                }
            }
        }
    }

    /**
     * Check if two developers are fuzzy duplicates of each other.
     */
    private function areFuzzyDuplicates(Developer $a, Developer $b): bool
    {
        // 1. Match by external_id if both have one
        if (!empty($a->external_id) && !empty($b->external_id) && $a->provider === $b->provider) {
            return trim($a->external_id) === trim($b->external_id);
        }

        // 2. Match by email case-insensitively if both have one
        if (!empty($a->email) && !empty($b->email)) {
            if (strtolower(trim($a->email)) === strtolower(trim($b->email))) {
                return true;
            }
        }

        // 3. Match by username case-insensitively if both have one
        if (!empty($a->username) && !empty($b->username)) {
            // Case-insensitive username match under same provider
            if ($a->provider === $b->provider && strcasecmp(trim($a->username), trim($b->username)) === 0) {
                return true;
            }
            // Global case-insensitive username match
            if (strcasecmp(trim($a->username), trim($b->username)) === 0) {
                return true;
            }
        }

        // 4. Match by extracted username from noreply emails
        $usernameA = $a->username;
        if (!empty($a->email) && preg_match('/^(?:\d+\+)?(.+)@users\.noreply\.github\.com$/i', $a->email, $matches)) {
            $usernameA = trim($matches[1]);
        }
        $usernameB = $b->username;
        if (!empty($b->email) && preg_match('/^(?:\d+\+)?(.+)@users\.noreply\.github\.com$/i', $b->email, $matches)) {
            $usernameB = trim($matches[1]);
        }
        if (strcasecmp($usernameA, $usernameB) === 0) {
            return true;
        }

        // 5. Match by fuzzy name comparison (normalized alphanumeric, lowercase)
        if (!empty($a->name) && !empty($b->name)) {
            $normA = Developer::normalizeName($a->name);
            $normB = Developer::normalizeName($b->name);
            if ($normA === $normB) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Merging operations cannot be automatically reversed.
    }
};
