<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Internship extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'organization',
        'required_skills',
        'duration',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'is_active' => 'boolean',
    ];
}