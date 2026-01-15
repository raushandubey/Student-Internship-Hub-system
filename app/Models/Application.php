<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'internship_id',
        'status',
    ];

    /**
     * Get the user who applied
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the internship applied for
     */
    public function internship()
    {
        return $this->belongsTo(Internship::class);
    }
}
