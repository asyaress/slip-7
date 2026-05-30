<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalarySlip;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_karyawan' => Employee::where('is_active', true)->count(),
            'slip_bulan_ini' => SalarySlip::where('bulan', now()->month)
                ->where('tahun', now()->year)->count(),
            'belum_kirim' => SalarySlip::where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->whereNull('email_sent_at')->count(),
            'sudah_kirim' => SalarySlip::where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->whereNotNull('email_sent_at')->count(),
        ];

        $recentSlips = SalarySlip::with('employee')
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard.index', compact('stats', 'recentSlips'));
    }
}
