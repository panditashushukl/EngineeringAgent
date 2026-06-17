<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowReport extends Model
{
    protected $fillable = [
        'report_text',
        'metrics_snapshot'
    ];

    protected $casts = [
        'metrics_snapshot' => 'array'
    ];
}
