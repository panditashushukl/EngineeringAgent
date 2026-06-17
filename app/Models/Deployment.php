<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'developer_id',
        'environment',
        'deployed_at'
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
