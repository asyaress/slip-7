<?php

namespace App\Http\Controllers;

use App\Mail\SlipGajiMail;
use App\Models\SalarySlip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ReviewSlipController extends Controller
{
    public function index(Request $request): View
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $slips = SalarySlip::with('employee')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->join('employees', 'salary_slips.employee_id', '=', 'employees.id')
            ->orderBy('employees.nomor')
            ->select('salary_slips.*')
            ->get();

        $summary = [
            'total' => $slips->count(),
            'belum_kirim' => $slips->whereNull('email_sent_at')->count(),
            'sudah_kirim' => $slips->whereNotNull('email_sent_at')->count(),
            'total_thp' => $slips->sum('take_home_pay'),
        ];

        return view('review.index', compact('slips', 'bulan', 'tahun', 'summary'));
    }

    public function show(SalarySlip $slip): View
    {
        $slip->load('employee');
        $slipData = $slip->toSlipArray();

        return view('slip.preview', [
            'slip' => $slipData,
            'savedSlip' => $slip,
        ]);
    }

    public function print(SalarySlip $slip): View
    {
        $slip->load('employee');

        return view('slip.print', ['slip' => $slip->toSlipArray()]);
    }

    public function blast(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slip_ids' => 'required|array|min:1',
            'slip_ids.*' => 'integer|exists:salary_slips,id',
            'bulan' => 'nullable|integer|min:1|max:12',
            'tahun' => 'nullable|integer|min:2020|max:2100',
        ]);

        if (config('mail.default') === 'smtp' && empty(config('mail.mailers.smtp.password'))) {
            return back()->with('error', 'Email belum dikonfigurasi. Isi MAIL_PASSWORD (App Password Gmail) di file .env, lalu jalankan: php artisan config:clear');
        }

        $slips = SalarySlip::with('employee')
            ->whereIn('id', $validated['slip_ids'])
            ->get();

        if ($slips->isEmpty()) {
            return back()->with('error', 'Tidak ada slip yang dipilih.');
        }

        $sent = 0;
        $failed = 0;

        foreach ($slips as $slip) {
            try {
                Mail::to($slip->employee->email)->send(new SlipGajiMail($slip));

                $slip->update([
                    'email_sent_at' => now(),
                    'email_status' => 'sent',
                ]);
                $sent++;
            } catch (\Throwable $e) {
                $slip->update(['email_status' => 'failed: '.$e->getMessage()]);
                $failed++;
            }
        }

        $message = "Blast email selesai: {$sent} terkirim";
        if ($failed > 0) {
            $message .= ", {$failed} gagal";
        }

        $redirectParams = array_filter([
            'bulan' => $validated['bulan'] ?? null,
            'tahun' => $validated['tahun'] ?? null,
        ]);

        return redirect()->route('review.index', $redirectParams)
            ->with($failed > 0 ? 'warning' : 'success', $message);
    }

    public function sendOne(SalarySlip $slip): RedirectResponse
    {
        if (config('mail.default') === 'smtp' && empty(config('mail.mailers.smtp.password'))) {
            return back()->with('error', 'Email belum dikonfigurasi. Isi MAIL_PASSWORD (App Password Gmail) di file .env');
        }

        try {
            Mail::to($slip->employee->email)->send(new SlipGajiMail($slip));

            $slip->update([
                'email_sent_at' => now(),
                'email_status' => 'sent',
            ]);

            return back()->with('success', "Slip berhasil dikirim ke {$slip->employee->email}");
        } catch (\Throwable $e) {
            $slip->update(['email_status' => 'failed: '.$e->getMessage()]);

            return back()->with('error', 'Gagal mengirim email: '.$e->getMessage());
        }
    }
}
