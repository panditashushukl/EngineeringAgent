<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Developer extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'provider',
        'name',
        'email',
        'username',
        'avatar'
    ];

    public function commits()
    {
        return $this->hasMany(Commit::class);
    }

    public function pullRequests()
    {
        return $this->hasMany(PullRequest::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function deployments()
    {
        return $this->hasMany(Deployment::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function bugFixes()
    {
        return $this->hasMany(BugFix::class);
    }

    public function metrics()
    {
        return $this->hasMany(DeveloperMetric::class);
    }
    
    public function insights()
    {
        return $this->hasMany(
            DeveloperInsight::class
        );
    }

    public function repositories()
    {
        return $this->belongsToMany(Repository::class, 'developer_repository');
    }

    public static function normalizeName(string $name): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    }

    public static function findOrCreateForProvider(
        string $provider,
        ?string $externalId,
        ?string $username,
        ?string $email = null,
        array $additionalData = []
    ): self {
        $developer = null;

        // Normalize inputs
        $username = $username ? trim($username) : null;
        $email = $email ? strtolower(trim($email)) : null;
        $externalId = $externalId ? trim($externalId) : null;
        $name = !empty($additionalData['name']) ? trim($additionalData['name']) : null;

        // 1. Try by external_id + provider
        if (!empty($externalId)) {
            $developer = self::where('provider', $provider)
                ->where('external_id', (string) $externalId)
                ->first();
        }

        // 2. Try by email (case-insensitive)
        if (!$developer && !empty($email)) {
            $developer = self::whereRaw('LOWER(email) = ?', [$email])->first();
        }

        // 3. Parse private noreply emails (GitHub, GitLab, etc.) to extract the username and look up by username
        if (!$developer && !empty($email)) {
            if (preg_match('/^(?:\d+\+)?(.+)@users\.noreply\.github\.com$/i', $email, $matches)) {
                $extractedUsername = trim($matches[1]);
                $developer = self::where('provider', 'github')
                    ->where('username', 'LIKE', $extractedUsername)
                    ->first();
            }
        }

        // 4. Try by username + provider (case-insensitive)
        if (!$developer && !empty($username)) {
            $developer = self::where('provider', $provider)
                ->where('username', 'LIKE', $username)
                ->first();
        }

        // 5. Try by username globally (case-insensitive)
        if (!$developer && !empty($username)) {
            $developer = self::where('username', 'LIKE', $username)
                ->first();
        }

        // 5.5. Try by fuzzy username + provider (case-insensitive Levenshtein <= 2)
        if (!$developer && !empty($username)) {
            $developers = self::where('provider', $provider)->get();
            foreach ($developers as $dev) {
                if ($dev->username && $dev->username !== 'unknown' && levenshtein(strtolower($dev->username), strtolower($username)) <= 2) {
                    $developer = $dev;
                    break;
                }
            }
        }

        // 5.6. Try by fuzzy username globally (case-insensitive Levenshtein <= 2)
        if (!$developer && !empty($username)) {
            $developers = self::all();
            foreach ($developers as $dev) {
                if ($dev->username && $dev->username !== 'unknown' && levenshtein(strtolower($dev->username), strtolower($username)) <= 2) {
                    $developer = $dev;
                    break;
                }
            }
        }

        // 6. Try by name + provider (using normalized alphanumeric characters)
        if (!$developer && !empty($name)) {
            $normalizedInputName = self::normalizeName($name);
            $developers = self::where('provider', $provider)->get();
            foreach ($developers as $dev) {
                if (self::normalizeName($dev->name) === $normalizedInputName) {
                    $developer = $dev;
                    break;
                }
            }
        }

        // 7. Try by name globally
        if (!$developer && !empty($name)) {
            $normalizedInputName = self::normalizeName($name);
            $developers = self::all();
            foreach ($developers as $dev) {
                if (self::normalizeName($dev->name) === $normalizedInputName) {
                    $developer = $dev;
                    break;
                }
            }
        }

        $data = array_filter([
            'external_id' => $externalId ? (string) $externalId : null,
            'email' => $email,
            'avatar' => $additionalData['avatar'] ?? null,
        ], fn($val) => $val !== null && $val !== '');

        $data['provider'] = $provider;
        $data['username'] = $username ?: ($developer ? $developer->username : 'unknown');

        if (!empty($name)) {
            $data['name'] = $name;
        }

        if ($developer) {
            // Keep original username, email, external_id if they are more complete/standard
            if (empty($data['email']) && !empty($developer->email)) {
                unset($data['email']);
            }
            if (empty($data['external_id']) && !empty($developer->external_id)) {
                unset($data['external_id']);
            }
            // If the developer already has a standard/valid username, keep it rather than replacing with a typo fallback
            if (!empty($developer->username) && $developer->username !== 'unknown' && !empty($username)) {
                $dist = levenshtein(strtolower($developer->username), strtolower($username));
                if ($dist > 0 && $dist <= 2) {
                    unset($data['username']);
                }
            }
            $developer->update($data);
            return $developer;
        }

        if (empty($data['name'])) {
            $data['name'] = $username ?: 'Unknown';
        }

        return self::create($data);
    }
}
