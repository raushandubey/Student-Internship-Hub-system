<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'from_status',
        'to_status',
        'changed_by',
        'actor_type',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the application that this log belongs to
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the user who made the status change
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
