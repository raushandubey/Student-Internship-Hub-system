<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RecruiterAnalyticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminRecruiterAnalyticsController extends Controller
{
    protected RecruiterAnalyticsService $analyticsService;

    public function __construct(RecruiterAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display recruiter analytics dashboard
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6
     */
    public function index(Request $request)
    {
        $dateRange = $this->parseDateRange($request);

        $analyticsData = $this->analyticsService->getRecruiterPerformanceData($dateRange);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return view('admin.recruiter-analytics.index', compact(
            'analyticsData',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Generate and stream CSV report
     * Requirements: 17.1, 17.2, 17.3, 17.4, 17.5
     */
    public function generateReport(Request $request)
    {
        $dateRange = $this->parseDateRange($request);

        $analyticsData = $this->analyticsService->getRecruiterPerformanceData($dateRange);

        $filename = 'recruiter-performance-report-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($analyticsData) {
            $handle = fopen('php://output', 'w');

            // CSV header row
            fputcsv($handle, [
                'recruiter_name',
                'organization',
                'total_internships',
                'total_applications',
                'approval_rate',
                'avg_response_time',
                'account_status',
            ]);

            foreach ($analyticsData as $row) {
                fputcsv($handle, [
                    $row['recruiter_name'],
                    $row['organization'] ?? '',
                    $row['total_internships'],
                    $row['total_applications'],
                    $row['approval_rate'] . '%',
                    $row['avg_response_time'] !== null ? $row['avg_response_time'] . ' days' : 'N/A',
                    'approved',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Parse date range from request parameters
     */
    private function parseDateRange(Request $request): ?array
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            return [
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay(),
            ];
        }

        return null;
    }
}
