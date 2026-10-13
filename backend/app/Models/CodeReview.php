<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CodeReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'code_snippet',
        'language',
        'quality_score',
        'issues',
        'ip_hash'
    ];

    protected $casts = [
        'issues' => 'array'
    ];
}
