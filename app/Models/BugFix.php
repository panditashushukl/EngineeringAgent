<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BugFix extends Model
{
    use HasFactory;

    protected $fillable = [
        'pull_request_id',
        'developer_id',
        'reason'
    ];

    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class);
    }

    public function developer()
    {
        return $this->belongsTo(Developer::class);
    }
}
