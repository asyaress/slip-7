<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalarySlip;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardAnalytics
{
    private const BULAN = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const TUNJANGAN_LABELS = [
        'transport' => 'Transport',
        'kehadiran' => 'Kehadiran',
        'kinerja' => 'Kinerja',
        'jabatan' => 'Jabatan',
        'perawatan' => 'Perawatan',
        'operator' => 'Operator',
        'konsumsi' => 'Konsumsi',
    ];

    private const POTONGAN_LABELS = [
        'angsuran' => 'Angsuran',
        'kasbon' => 'Kasbon',
        'lain_lain' => 'Lain-Lain',
    ];

    public static function forPeriod(int $bulan, int $tahun): array
    {
        $slips = SalarySlip::with('employee')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->join('employees', 'salary_slips.employee_id', '=', 'employees.id')
            ->orderBy('employees.nomor')
            ->select('salary_slips.*')
            ->get();

        $totalKaryawan = Employee::where('is_active', true)->count();
        $count = $slips->count();

        $financials = [
            'total_gaji_pokok' => $slips->sum('gaji_pokok'),
            'total_tunjangan' => $slips->sum('total_tunjangan'),
            'total_potongan' => $slips->sum('total_potongan'),
            'total_fasilitas' => $slips->sum('total_fasilitas'),
            'total_thp' => $slips->sum('take_home_pay'),
            'total_pendapatan' => $slips->sum(fn ($s) => $s->resolvedTotalPendapatan()),
            'rata_thp' => $count > 0 ? $slips->avg('take_home_pay') : 0,
            'rata_pendapatan' => $count > 0 ? $slips->avg(fn ($s) => $s->resolvedTotalPendapatan()) : 0,
        ];

        $emailStats = [
            'terkirim' => $slips->filter(fn ($s) => $s->isEmailSent())->count(),
            'belum' => $slips->filter(fn ($s) => ! $s->isEmailSent() && ! self::isFailed($s))->count(),
            'gagal' => $slips->filter(fn ($s) => self::isFailed($s))->count(),
        ];

        $attendance = [
            'hadir' => $slips->sum('hadir'),
            'sakit_izin' => $slips->sum('sakit_izin'),
            'tidak_hadir' => $slips->sum('tidak_hadir'),
        ];

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'periode_label' => strtoupper(Carbon::create($tahun, $bulan, 1)->locale('id')->translatedFormat('F')).' '.$tahun,
            'stats' => [
                'total_karyawan' => $totalKaryawan,
                'slip_periode' => $count,
                'belum_input' => max(0, $totalKaryawan - $count),
                'persen_input' => $totalKaryawan > 0 ? round(($count / $totalKaryawan) * 100) : 0,
                'terkirim' => $emailStats['terkirim'],
                'belum_kirim' => $emailStats['belum'],
                'gagal_kirim' => $emailStats['gagal'],
                'persen_kirim' => $count > 0 ? round(($emailStats['terkirim'] / $count) * 100) : 0,
            ],
            'financials' => $financials,
            'email_stats' => $emailStats,
            'attendance' => $attendance,
            'charts' => [
                'composition' => self::compositionChart($slips),
                'thp_by_employee' => self::thpByEmployeeChart($slips),
                'email_status' => self::emailStatusChart($emailStats),
                'tunjangan_breakdown' => self::tunjanganBreakdown($slips),
                'potongan_breakdown' => self::potonganBreakdown($slips),
                'thp_trend' => self::thpTrend($bulan, $tahun),
            ],
            'recent_slips' => SalarySlip::with('employee')->latest()->limit(5)->get(),
        ];
    }

    private static function isFailed(SalarySlip $slip): bool
    {
        return $slip->email_status !== null
            && str_starts_with($slip->email_status, 'failed');
    }

    private static function compositionChart(Collection $slips): array
    {
        return [
            'labels' => ['Gaji Pokok', 'Tunjangan', 'Fasilitas'],
            'values' => [
                round($slips->sum('gaji_pokok')),
                round($slips->sum('total_tunjangan')),
                round($slips->sum('total_fasilitas')),
            ],
            'potongan' => round($slips->sum('total_potongan')),
        ];
    }

    private static function thpByEmployeeChart(Collection $slips): array
    {
        return [
            'labels' => $slips->map(fn ($s) => self::shortName($s->employee->name))->values()->all(),
            'values' => $slips->map(fn ($s) => round($s->take_home_pay))->values()->all(),
            'pendapatan' => $slips->map(fn ($s) => round($s->resolvedTotalPendapatan()))->values()->all(),
        ];
    }

    private static function emailStatusChart(array $emailStats): array
    {
        return [
            'labels' => ['Terkirim', 'Belum Kirim', 'Gagal'],
            'values' => [
                $emailStats['terkirim'],
                $emailStats['belum'],
                $emailStats['gagal'],
            ],
        ];
    }

    private static function tunjanganBreakdown(Collection $slips): array
    {
        $totals = array_fill_keys(array_keys(self::TUNJANGAN_LABELS), 0.0);

        foreach ($slips as $slip) {
            foreach ($slip->tunjangan ?? [] as $key => $amount) {
                if (isset($totals[$key])) {
                    $totals[$key] += (float) $amount;
                }
            }
        }

        arsort($totals);

        return [
            'labels' => array_map(fn ($k) => self::TUNJANGAN_LABELS[$k], array_keys($totals)),
            'values' => array_map(fn ($v) => round($v), array_values($totals)),
        ];
    }

    private static function potonganBreakdown(Collection $slips): array
    {
        $totals = array_fill_keys(array_keys(self::POTONGAN_LABELS), 0.0);

        foreach ($slips as $slip) {
            foreach ($slip->potongan ?? [] as $key => $amount) {
                if (isset($totals[$key])) {
                    $totals[$key] += (float) $amount;
                }
            }
        }

        return [
            'labels' => array_map(fn ($k) => self::POTONGAN_LABELS[$k], array_keys($totals)),
            'values' => array_map(fn ($v) => round($v), array_values($totals)),
        ];
    }

    private static function thpTrend(int $bulan, int $tahun): array
    {
        $labels = [];
        $thpValues = [];
        $pendapatanValues = [];
        $slipCounts = [];

        $cursor = Carbon::create($tahun, $bulan, 1)->subMonths(5);

        for ($i = 0; $i < 6; $i++) {
            $labels[] = self::BULAN[$cursor->month].' '.$cursor->year;

            $monthSlips = SalarySlip::where('bulan', $cursor->month)
                ->where('tahun', $cursor->year)
                ->get();

            $thpValues[] = round($monthSlips->sum('take_home_pay'));
            $pendapatanValues[] = round($monthSlips->sum(fn ($s) => $s->resolvedTotalPendapatan()));
            $slipCounts[] = $monthSlips->count();

            $cursor->addMonth();
        }

        return compact('labels', 'thpValues', 'pendapatanValues', 'slipCounts');
    }

    private static function shortName(string $name): string
    {
        $parts = explode(' ', trim($name));

        if (count($parts) <= 2) {
            return $name;
        }

        return $parts[0].' '.end($parts);
    }
}
