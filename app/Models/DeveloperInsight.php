<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeveloperInsight extends Model
{
    use HasFactory;

    protected $fillable = [

        'developer_id',

        'summary',

        'strengths',

        'weaknesses',

        'risks',

        'recommendations',

        'raw_response'
    ];

    protected $casts = [

        'strengths' => 'array',

        'weaknesses' => 'array',

        'risks' => 'array',

        'recommendations' => 'array',
    ];

    public function developer()
    {
        return $this->belongsTo(
            Developer::class
        );
    }
}