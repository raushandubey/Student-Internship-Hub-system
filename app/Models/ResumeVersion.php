<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ResumeVersion
 *
 * Represents a snapshot of a resume — either the original uploaded PDF content
 * or an AI-rewritten version generated for a specific job.
 */
class ResumeVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'internship_id',
        'version_number',
        'label',
        'content',
        'file_path',
        'score_at_creation',
        'type',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                       */
    /* ------------------------------------------------------------------ */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function internship()
    {
        return $this->belongsTo(Internship::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Returns true if this is an AI-rewritten version.
     */
    public function isAiVersion(): bool
    {
        return $this->type === 'ai_rewrite';
    }

    /**
     * Returns the original version for a given user+job combo.
     */
    public static function original(int $userId, int $internshipId): ?self
    {
        return self::where('user_id', $userId)
            ->where('internship_id', $internshipId)
            ->where('type', 'original')
            ->orderBy('created_at')
            ->first();
    }

    /**
     * Returns the latest AI-rewritten version for a user+job combo.
     */
    public static function latestAiVersion(int $userId, int $internshipId): ?self
    {
        return self::where('user_id', $userId)
            ->where('internship_id', $internshipId)
            ->where('type', 'ai_rewrite')
            ->orderByDesc('version_number')
            ->first();
    }
}
