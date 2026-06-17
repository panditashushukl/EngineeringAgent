<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commit extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'developer_id',
        'sha',
        'message',
        'committed_at'
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
