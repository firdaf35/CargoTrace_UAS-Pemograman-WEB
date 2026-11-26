<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        return view('reports.index', [
            'rows' => $this->dashboard->carrierReport(),
            's' => $this->dashboard->summary($request->user()),
        ]);
    }
}
