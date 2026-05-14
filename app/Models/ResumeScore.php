<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ResumeScore
 *
 * Stores the AI-computed match score for a user's resume against a job.
 * Score breakdown enables granular BEFORE/AFTER comparison.
 */
class ResumeScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'internship_id',
        'overall_score',
        'skill_match_score',
        'keyword_score',
        'format_score',
        'matching_skills',
        'missing_skills',
        'issues',
        'strengths',
        'match_tier',
        'resume_version_id',
    ];

    protected $casts = [
        'matching_skills' => 'array',
        'missing_skills'  => 'array',
        'issues'          => 'array',
        'strengths'       => 'array',
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

    public function resumeVersion()
    {
        return $this->belongsTo(ResumeVersion::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Business Logic                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * Gate tier based on overall score.
     *
     * < 60  → low   (show warning)
     * 60-70 → medium
     * > 70  → high  (recommended)
     */
    public static function scoreTier(int $score): string
    {
        if ($score >= 70) {
            return 'high';
        }

        if ($score >= 60) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Human-readable gate message.
     */
    public function gateMessage(): string
    {
        return match ($this->match_tier) {
            'high'   => '✅ High match — recommended to apply!',
            'medium' => '⚠️ Moderate match — consider improving your resume first.',
            default  => '🔴 Low chances of selection — improve your resume before applying.',
        };
    }

    /**
     * Badge colour class for the UI.
     */
    public function badgeClass(): string
    {
        return match ($this->match_tier) {
            'high'   => 'bg-green-100 text-green-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            default  => 'bg-red-100 text-red-800',
        };
    }
}
