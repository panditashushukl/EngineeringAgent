<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_id',
        'external_id',
        'name',
        'owner',
        'provider',
        'default_branch'
    ];

    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }

    public function commits()
    {
        return $this->hasMany(Commit::class);
    }

    public function pullRequests()
    {
        return $this->hasMany(PullRequest::class);
    }

    public function deployments()
    {
        return $this->hasMany(Deployment::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function developers()
    {
        return $this->belongsToMany(Developer::class, 'developer_repository');
    }
}
