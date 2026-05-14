<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CandidateIntelligence Model
 *
 * Stores per-candidate ATS intelligence scores, recruiter trust indicators,
 * ranking position, and skill analysis for the Recruitment Intelligence Engine.
 */
class CandidateIntelligence extends Model
{
    use HasFactory;

    protected $table = 'candidate_intelligence';

    protected $fillable = [
        'user_id', 'internship_id', 'application_id',
        // ATS Scores
        'overall_ats_score', 'skill_match_score', 'keyword_score',
        'experience_score', 'project_score', 'technical_depth_score', 'format_score',
        // Recruiter Trust Indicators
        'role_category', 'backend_match', 'frontend_match',
        'project_quality', 'technical_depth', 'recruiter_readability', 'experience_level',
        // Skill Analysis
        'matching_skills', 'missing_skills', 'critical_missing_skills', 'optional_missing_skills',
        // Weakness
        'weak_areas', 'recommendations', 'summary_weak', 'weak_bullet_count',
        // Ranking
        'rank_position', 'total_candidates', 'rank_tier',
        // Optimization
        'before_score', 'after_score', 'ai_optimized',
        // Analytics
        'recruiter_viewed', 'recruiter_viewed_at', 'shortlisted', 'shortlisted_at',
    ];

    protected $casts = [
        'matching_skills'       => 'array',
        'missing_skills'        => 'array',
        'critical_missing_skills' => 'array',
        'optional_missing_skills' => 'array',
        'weak_areas'            => 'array',
        'recommendations'       => 'array',
        'summary_weak'          => 'boolean',
        'ai_optimized'          => 'boolean',
        'recruiter_viewed'      => 'boolean',
        'shortlisted'           => 'boolean',
        'recruiter_viewed_at'   => 'datetime',
        'shortlisted_at'        => 'datetime',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                       */
    /* ------------------------------------------------------------------ */

    public function user()       { return $this->belongsTo(User::class); }
    public function internship() { return $this->belongsTo(Internship::class); }
    public function application(){ return $this->belongsTo(Application::class); }

    /* ------------------------------------------------------------------ */
    /*  Trust Indicator Labels                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Human-readable match quality label.
     */
    public static function matchLabel(int $score): string
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 65) return 'Strong';
        if ($score >= 50) return 'Medium';
        if ($score >= 30) return 'Weak';
        return 'Poor';
    }

    /**
     * CSS class for match quality badge.
     */
    public static function matchClass(string $label): string
    {
        return match($label) {
            'Excellent' => 'intel-badge-excellent',
            'Strong'    => 'intel-badge-strong',
            'Medium'    => 'intel-badge-medium',
            'Weak'      => 'intel-badge-weak',
            default     => 'intel-badge-poor',
        };
    }

    /**
     * Score color for progress bars.
     */
    public static function scoreColor(int $score): string
    {
        if ($score >= 75) return '#6fcf97';  // green
        if ($score >= 55) return '#f2c94c';  // yellow
        if ($score >= 35) return '#f2994a';  // orange
        return '#eb5757';                     // red
    }

    /**
     * Rank tier label + class.
     */
    public function rankTierBadge(): array
    {
        return match($this->rank_tier) {
            'top10'   => ['label' => '🏆 Top 10%',   'class' => 'rank-top10'],
            'top25'   => ['label' => '⭐ Top 25%',   'class' => 'rank-top25'],
            'average' => ['label' => '📊 Average',   'class' => 'rank-avg'],
            'below'   => ['label' => '📉 Below Avg', 'class' => 'rank-below'],
            default   => ['label' => '—',            'class' => 'rank-unranked'],
        };
    }

    /**
     * Overall profile strength (for candidate dashboard).
     */
    public function profileStrength(): array
    {
        $score = $this->overall_ats_score;
        if ($score >= 80) return ['level' => 'Strong',  'color' => '#6fcf97', 'pct' => $score];
        if ($score >= 60) return ['level' => 'Medium',  'color' => '#f2c94c', 'pct' => $score];
        if ($score >= 40) return ['level' => 'Developing', 'color' => '#f2994a', 'pct' => $score];
        return ['level' => 'Weak', 'color' => '#eb5757', 'pct' => $score];
    }

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                              */
    /* ------------------------------------------------------------------ */

    public function scopeForInternship($query, int $internshipId)
    {
        return $query->where('internship_id', $internshipId);
    }

    public function scopeRanked($query)
    {
        return $query->whereNotNull('rank_position')->orderBy('rank_position');
    }

    public function scopeTopCandidates($query, int $limit = 10)
    {
        return $query->orderByDesc('overall_ats_score')->limit($limit);
    }
}
