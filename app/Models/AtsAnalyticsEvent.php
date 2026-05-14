<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AtsAnalyticsEvent — Event log for ML foundation.
 *
 * Tracks recruiter interactions, optimization actions, and outcomes
 * to build training data for future intelligent recommendations.
 */
class AtsAnalyticsEvent extends Model
{
    protected $table = 'ats_analytics_events';

    protected $fillable = [
        'user_id', 'internship_id', 'event_type', 'event_data', 'actor_role',
    ];

    protected $casts = [
        'event_data' => 'array',
    ];

    public function user()       { return $this->belongsTo(User::class); }
    public function internship() { return $this->belongsTo(Internship::class); }

    /* ── Event Type Constants ────────────────────────────────────── */

    const SCORE_COMPUTED       = 'score_computed';
    const REWRITE_TRIGGERED    = 'rewrite_triggered';
    const REWRITE_COMPLETED    = 'rewrite_completed';
    const PDF_DOWNLOADED       = 'pdf_downloaded';
    const RECRUITER_VIEWED     = 'recruiter_viewed';
    const SHORTLISTED          = 'shortlisted';
    const APPLICATION_APPROVED = 'application_approved';
    const APPLICATION_REJECTED = 'application_rejected';
    const RANK_COMPUTED        = 'rank_computed';

    /* ── Quick Log Helper ─────────────────────────────────────────── */

    public static function log(
        string $eventType,
        ?int $userId,
        ?int $internshipId,
        array $data = [],
        string $actorRole = 'system'
    ): void {
        try {
            static::create([
                'event_type'    => $eventType,
                'user_id'       => $userId,
                'internship_id' => $internshipId,
                'event_data'    => $data,
                'actor_role'    => $actorRole,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('AtsAnalyticsEvent::log failed', [
                'event_type' => $eventType,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
