<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        return view('dashboard.index', ['s' => $this->dashboard->summary($request->user())]);
    }
}
