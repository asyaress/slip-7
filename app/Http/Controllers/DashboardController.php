<?php

namespace App\Http\Controllers;

use App\Services\DashboardAnalytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $analytics = DashboardAnalytics::forPeriod($bulan, $tahun);

        return view('dashboard.index', $analytics);
    }
}
