<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Resume\AiProductionDiagnosticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiDiagnosticsController extends Controller
{
    public function __construct(
        private readonly AiProductionDiagnosticsService $diagnostics,
    ) {}

    public function debug(Request $request): JsonResponse
    {
        $probe = $request->boolean('probe', true);

        return response()->json($this->diagnostics->run($probe));
    }
}
