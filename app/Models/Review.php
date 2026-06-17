<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'pull_request_id',
        'reviewer_id',
        'state',
        'reviewed_at'
    ];

    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(
            Developer::class,
            'reviewer_id'
        );
    }
}
