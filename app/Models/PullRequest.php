<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PullRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'developer_id',
        'external_id',
        'title',
        'status',
        'opened_at',
        'merged_at'
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }

    public function developer()
    {
        return $this->belongsTo(Developer::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function bugFix()
    {
        return $this->hasOne(BugFix::class);
    }
}
