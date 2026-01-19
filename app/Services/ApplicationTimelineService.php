<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationStatusLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ApplicationTimelineService
 * 
 * Provides timeline visualization and predictions for applications.
 * Uses historical data to estimate processing times.
 */
class ApplicationTimelineService
{
    private const CACHE_TTL = 600; // 10 minutes

    /**
     * Get timeline data for a single application
     */
    public function getApplicationTimeline(Application $application): array
    {
        $stages = [
            'pending' => ['label' => 'Applied', 'icon' => 'paper-plane', 'order' => 1],
            'under_review' => ['label' => 'Under Review', 'icon' => 'search', 'order' => 2],
            'shortlisted' => ['label' => 'Shortlisted', 'icon' => 'star', 'order' => 3],
            'interview_scheduled' => ['label' => 'Interview', 'icon' => 'video', 'order' => 4],
            'approved' => ['label' => 'Approved', 'icon' => 'check-circle', 'order' => 5],
            'rejected' => ['label' => 'Rejected', 'icon' => 'times-circle', 'order' => -1],
        ];

        $currentStatus = $application->status->value;
        $logs = $application->statusLogs()->orderBy('created_at', 'asc')->get();

        $timeline = [];
        $completedStages = [];

        // Build completed stages from logs
        foreach ($logs as $log) {
            $completedStages[$log->to_status] = $log->created_at;
        }

        // Build timeline array
        foreach ($stages as $status => $info) {
            if ($status === 'rejected' && $currentStatus !== 'rejected') {
                continue; // Skip rejected if not rejected
            }

            $isCompleted = isset($completedStages[$status]);
            $isCurrent = $status === $currentStatus;

            $timeline[] = [
                'status' => $status,
                'label' => $info['label'],
                'icon' => $info['icon'],
                'order' => $info['order'],
                'completed' => $isCompleted,
                'current' => $isCurrent,
                'completed_at' => $completedStages[$status] ?? null,
            ];
        }

        // Sort by order (rejected at end if present)
        usort($timeline, fn($a, $b) => $a['order'] <=> $b['order']);

        return [
            'stages' => $timeline,
            'current_status' => $currentStatus,
            'is_terminal' => in_array($currentStatus, ['approved', 'rejected']),
            'prediction' => $this->getPrediction($application),
        ];
    }

    /**
     * Get prediction for next action based on historical averages
     */
    public function getPrediction(Application $application): ?array
    {
        $currentStatus = $application->status->value;

        // No prediction for terminal states
        if (in_array($currentStatus, ['approved', 'rejected'])) {
            return null;
        }

        $averages = $this->getHistoricalAverages();
        $nextStatus = $this->getNextExpectedStatus($currentStatus);

        if (!$nextStatus || !isset($averages[$currentStatus])) {
            return null;
        }

        $avgDays = $averages[$currentStatus]['avg_days'] ?? 3;
        $daysSinceLastChange = $application->updated_at->diffInDays(now());

        return [
            'next_status' => $nextStatus,
            'next_label' => $this->getStatusLabel($nextStatus),
            'avg_days' => round($avgDays, 1),
            'days_waiting' => $daysSinceLastChange,
            'message' => $this->getPredictionMessage($currentStatus, $avgDays, $daysSinceLastChange),
        ];
    }

    /**
     * Get historical average processing times
     */
    public function getHistoricalAverages(): array
    {
        return Cache::remember('timeline_averages', self::CACHE_TTL, function () {
            $transitions = [
                'pending' => 'under_review',
                'under_review' => 'shortlisted',
                'shortlisted' => 'interview_scheduled',
                'interview_scheduled' => 'approved',
            ];

            $averages = [];

            foreach ($transitions as $from => $to) {
                $avgDays = ApplicationStatusLog::where('from_status', $from)
                    ->where('to_status', $to)
                    ->selectRaw('AVG(TIMESTAMPDIFF(DAY, 
                        (SELECT created_at FROM application_status_logs AS prev 
                         WHERE prev.application_id = application_status_logs.application_id 
                         AND prev.to_status = application_status_logs.from_status 
                         ORDER BY created_at DESC LIMIT 1), 
                        created_at)) as avg_days')
                    ->value('avg_days');

                $averages[$from] = [
                    'next_status' => $to,
                    'avg_days' => $avgDays ?? $this->getDefaultDays($from),
                ];
            }

            return $averages;
        });
    }

    /**
     * Get default processing days when no historical data
     */
    private function getDefaultDays(string $status): float
    {
        return match($status) {
            'pending' => 3,
            'under_review' => 5,
            'shortlisted' => 4,
            'interview_scheduled' => 7,
            default => 3,
        };
    }

    /**
     * Get next expected status in pipeline
     */
    private function getNextExpectedStatus(string $current): ?string
    {
        return match($current) {
            'pending' => 'under_review',
            'under_review' => 'shortlisted',
            'shortlisted' => 'interview_scheduled',
            'interview_scheduled' => 'approved',
            default => null,
        };
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'pending' => 'Pending',
            'under_review' => 'Under Review',
            'shortlisted' => 'Shortlisted',
            'interview_scheduled' => 'Interview Scheduled',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Generate prediction message
     */
    private function getPredictionMessage(string $current, float $avgDays, int $daysWaiting): string
    {
        $nextLabel = $this->getStatusLabel($this->getNextExpectedStatus($current) ?? '');
        
        if ($daysWaiting < $avgDays) {
            $remaining = round($avgDays - $daysWaiting);
            return "Typically moves to {$nextLabel} in {$remaining} more day(s)";
        } else {
            return "Usually takes {$avgDays} days. You've waited {$daysWaiting} days.";
        }
    }

    /**
     * Get timeline summary for all user applications
     */
    public function getUserTimelineSummary(int $userId): array
    {
        $applications = Application::with(['internship', 'statusLogs'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $applications->map(function ($app) {
            return [
                'id' => $app->id,
                'internship' => [
                    'title' => $app->internship->title,
                    'organization' => $app->internship->organization,
                ],
                'timeline' => $this->getApplicationTimeline($app),
                'applied_at' => $app->created_at,
                'match_score' => $app->match_score,
            ];
        })->toArray();
    }
}
