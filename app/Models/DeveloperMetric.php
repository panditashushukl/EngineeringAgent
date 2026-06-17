<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeveloperMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'developer_id',
        'period_start',
        'period_end',
        'commits',
        'prs_created',
        'prs_merged',
        'reviews_done',
        'bugs_fixed',
        'deployments',
        'task_completion_score',
        'code_quality_score',
        'review_score',
        'delivery_speed_score',
        'developer_score'
    ];

    public function developer()
    {
        return $this->belongsTo(Developer::class);
    }
}
