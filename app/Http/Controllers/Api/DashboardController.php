<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): JsonResponse
    {
        $s = $dashboard->summary($request->user());
        unset($s['recent'], $s['map_points']);

        return response()->json(['data' => $s]);
    }
}
