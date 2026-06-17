<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'developer_id',
        'external_id',
        'title',
        'status',
        'closed_at'
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }

    public function developer()
    {
        return $this->belongsTo(Developer::class);
    }
    
}
