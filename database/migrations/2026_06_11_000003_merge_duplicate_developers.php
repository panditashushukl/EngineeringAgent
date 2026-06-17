<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicates = \App\Models\Developer::select('username', 'provider')
            ->groupBy('username', 'provider')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $devs = \App\Models\Developer::where('username', $duplicate->username)
                ->where('provider', $duplicate->provider)
                ->orderBy('id') // keep the earliest one
                ->get();

            $primary = $devs->first();
            $others = $devs->slice(1);

            foreach ($others as $other) {
                // Update primary with fields from other if primary lacks them
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
        }

        // Recalculate metrics to make sure scores and stats are correct for the merged developers
        try {
            $metricService = app(\App\Services\Metrics\DeveloperMetricService::class);
            $metrics = \App\Models\DeveloperMetric::all();
            foreach ($metrics as $metric) {
                $developer = $metric->developer;
                if (!$developer) {
                    continue;
                }
                $start = \Carbon\Carbon::parse($metric->period_start);
                $end = \Carbon\Carbon::parse($metric->period_end);
                
                $results = $metricService->calculate($developer, $start, $end);
                $metric->update($results);
            }
        } catch (\Throwable $e) {
            // Ignore/log errors during testing
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Merging operations cannot be automatically reversed.
    }
};
