<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalarySlip;
use App\Services\LemburWeekService;
use App\Services\MonthlyTunjanganService;
use App\Services\SlipGajiBuilder;
use App\Services\SlipGajiCalculator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlipGajiController extends Controller
{
    private const RUPIAH_FIELDS = [
        'gaji_pokok',
        'bonus',
        'pot_angsuran', 'pot_kasbon', 'pot_lain_lain',
    ];

    public function create(): View
    {
        $employees = Employee::where('is_active', true)->orderBy('nomor')->get();
        $preserveForm = ! empty(session()->getOldInput());
        $lemburWeeks = $this->resolveLemburWeeks();

        return view('slip.create', compact('employees', 'preserveForm', 'lemburWeeks'));
    }

    public function edit(SalarySlip $slip): View
    {
        $slip->load('employee');
        $employees = Employee::where('is_active', true)->orderBy('nomor')->get();
        $editingSlip = $slip;
        $formData = $slip->toFormInputs();
        $preserveForm = ! empty(session()->getOldInput());
        $lemburWeeks = $this->resolveLemburWeeks($slip);

        return view('slip.create', compact('employees', 'editingSlip', 'formData', 'preserveForm', 'lemburWeeks'));
    }

    public function existing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ]);

        $slip = SalarySlip::where('employee_id', $validated['employee_id'])
            ->where('bulan', $validated['bulan'])
            ->where('tahun', $validated['tahun'])
            ->first();

        if (! $slip) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'slip_id' => $slip->id,
            'data' => $slip->toFormInputs(),
            'lembur_weeks' => LemburWeekService::weeksForForm(
                $slip->bulan,
                $slip->tahun,
                $slip->lembur
            ),
        ]);
    }

    public function lemburWeeks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ]);

        return response()->json([
            'weeks' => LemburWeekService::weeksForForm(
                (int) $validated['bulan'],
                (int) $validated['tahun']
            ),
        ]);
    }

    public function monthlyTunjangan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ]);

        $rates = MonthlyTunjanganService::forPeriod(
            (int) $validated['bulan'],
            (int) $validated['tahun']
        );

        $jumlahKehadiran = max(1, (int) ($request->input('jumlah_kehadiran', 26)));

        return response()->json([
            'rates' => $rates,
            'fields' => MonthlyTunjanganService::toFormFields($rates, $jumlahKehadiran),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSlip($request);
        $employee = Employee::findOrFail($validated['employee_id']);

        $slip = SlipGajiBuilder::buildFromValidated($validated, $employee);
        $saved = SlipGajiBuilder::saveSlip($slip, $employee);

        $message = $saved->wasRecentlyCreated
            ? "Slip gaji {$employee->name} berhasil dibuat."
            : "Slip gaji {$employee->name} berhasil diperbarui.";

        return redirect()->route('review.show', $saved)->with('success', $message);
    }

    public function autoSave(Request $request): JsonResponse
    {
        $validated = $this->validateSlip($request);
        $employee = Employee::findOrFail($validated['employee_id']);

        $slip = SlipGajiBuilder::buildFromValidated($validated, $employee);
        $saved = SlipGajiBuilder::saveSlip($slip, $employee);

        return response()->json([
            'saved' => true,
            'slip_id' => $saved->id,
            'was_created' => $saved->wasRecentlyCreated,
            'message' => $saved->wasRecentlyCreated
                ? "Slip gaji {$employee->name} tersimpan otomatis."
                : "Perubahan slip {$employee->name} tersimpan otomatis.",
            'review_url' => route('review.show', $saved),
            'edit_url' => route('slip.edit', $saved),
            'updated_at' => optional($saved->updated_at)->format('H:i:s'),
        ]);
    }

    public function copyPreviousMonth(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2100',
            'redirect_to' => 'nullable|in:slip,review',
        ]);

        $targetPeriod = Carbon::create((int) $validated['tahun'], (int) $validated['bulan'], 1);
        $sourcePeriod = $targetPeriod->copy()->subMonthNoOverflow();
        $sourceSlips = SalarySlip::with('employee')
            ->where('bulan', $sourcePeriod->month)
            ->where('tahun', $sourcePeriod->year)
            ->orderBy('employee_id')
            ->get();

        $redirect = $this->copyPreviousMonthRedirect(
            $validated['redirect_to'] ?? 'slip',
            $targetPeriod->month,
            $targetPeriod->year
        );

        if ($sourceSlips->isEmpty()) {
            return $redirect->with(
                'warning',
                'Tidak ada slip '.$sourcePeriod->locale('id')->translatedFormat('F Y').' yang bisa disalin.'
            );
        }

        $created = 0;
        $updated = 0;

        foreach ($sourceSlips as $sourceSlip) {
            if (! $sourceSlip->employee) {
                continue;
            }

            $saved = SlipGajiBuilder::copySlipToPeriod(
                $sourceSlip,
                $targetPeriod->month,
                $targetPeriod->year
            );

            if ($saved->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $targetLabel = $targetPeriod->locale('id')->translatedFormat('F Y');
        $sourceLabel = $sourcePeriod->locale('id')->translatedFormat('F Y');

        return $redirect->with(
            'success',
            "Copy slip dari {$sourceLabel} ke {$targetLabel} selesai. {$created} slip dibuat, {$updated} slip diperbarui."
        );
    }

    public function preview(Request $request): View
    {
        $validated = $this->validateSlip($request);
        $employee = Employee::findOrFail($validated['employee_id']);

        $slip = SlipGajiBuilder::buildFromValidated($validated, $employee);
        $slip = SlipGajiBuilder::attachQrSignature($slip);

        $flashData = $validated;
        $flashData['lembur'] = $request->input('lembur');
        if ($request->filled('_editing_slip_id')) {
            $flashData['_editing_slip_id'] = $request->input('_editing_slip_id');
        }
        $request->session()->flashInput($flashData);

        $returnUrl = $request->filled('_editing_slip_id')
            ? route('slip.edit', $request->input('_editing_slip_id'))
            : route('slip.create');

        return view('slip.preview', compact('slip', 'returnUrl'));
    }

    private function validateSlip(Request $request): array
    {
        $this->normalizeRupiahFields($request);
        $this->normalizeLemburFields($request);

        $tunjRules = [];
        foreach (MonthlyTunjanganService::keys() as $key) {
            $tunjRules["tunj_harian_{$key}"] = 'nullable|numeric|min:0';
            $tunjRules["tunj_bulanan_{$key}"] = 'nullable|numeric|min:0';
            $tunjRules["tunj_mode_{$key}"] = 'nullable|in:harian,bulanan';
        }

        $validated = $request->validate(array_merge([
            'employee_id' => 'required|exists:employees,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2100',
            'gaji_pokok' => 'required|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'pot_angsuran' => 'nullable|numeric|min:0',
            'pot_kasbon' => 'nullable|numeric|min:0',
            'pot_lain_lain' => 'nullable|numeric|min:0',
            'jumlah_kehadiran' => 'required|integer|min:1',
            'hadir' => 'required|integer|min:0',
            'sakit_izin' => 'nullable|integer|min:0',
            'tidak_hadir' => 'nullable|integer|min:0',
            'fasilitas' => 'nullable|array',
            'fasilitas.*' => 'in:'.implode(',', SlipGajiCalculator::fasilitasKeys()),
            'lembur' => 'nullable|array',
            'lembur.*.nominal' => 'nullable',
            'lembur.*.minggu' => 'nullable|integer|min:1',
            'lembur.*.periode' => 'nullable|string|max:50',
            'lembur.*.date_start' => 'nullable|date_format:Y-m-d',
            'lembur.*.date_end' => 'nullable|date_format:Y-m-d',
            'lembur.*.status' => 'nullable|in:belum_dibayar,sudah_dibayar',
        ], $tunjRules));

        foreach (MonthlyTunjanganService::keys() as $key) {
            $validated["tunj_harian_{$key}"] = SlipGajiCalculator::parseRupiah(
                $validated["tunj_harian_{$key}"] ?? 0
            );
            $validated["tunj_bulanan_{$key}"] = SlipGajiCalculator::parseRupiah(
                $validated["tunj_bulanan_{$key}"] ?? 0
            );
            $validated["tunj_mode_{$key}"] = SlipGajiCalculator::isTunjanganBulananOnly($key)
                ? 'bulanan'
                : ($validated["tunj_mode_{$key}"] ?? null);
        }

        $validated['fasilitas'] = SlipGajiCalculator::normalizeFasilitas(
            $validated['fasilitas'] ?? []
        );

        $validated['lembur'] = LemburWeekService::fromRequest(
            $request->input('lembur'),
            (int) $validated['bulan'],
            (int) $validated['tahun']
        );

        return $validated;
    }

    private function resolveLemburWeeks(?SalarySlip $editingSlip = null): array
    {
        $bulan = (int) old('bulan', $editingSlip?->bulan ?? request()->integer('bulan', now()->month));
        $tahun = (int) old('tahun', $editingSlip?->tahun ?? request()->integer('tahun', now()->year));

        return LemburWeekService::weeksForForm($bulan, $tahun, $editingSlip?->lembur);
    }

    private function normalizeLemburFields(Request $request): void
    {
        $lembur = $request->input('lembur');

        if (! is_array($lembur)) {
            return;
        }

        $normalized = [];

        foreach ($lembur as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized[$index] = $row;

            if (isset($row['nominal'])) {
                $normalized[$index]['nominal'] = SlipGajiCalculator::parseRupiah($row['nominal']);
            }
        }

        $request->merge(['lembur' => $normalized]);
    }

    private function normalizeRupiahFields(Request $request): void
    {
        $normalized = [];

        foreach (self::RUPIAH_FIELDS as $field) {
            if ($request->has($field)) {
                $normalized[$field] = SlipGajiCalculator::parseRupiah($request->input($field));
            }
        }

        foreach (MonthlyTunjanganService::keys() as $key) {
            $field = "tunj_bulanan_{$key}";
            if ($request->has($field)) {
                $normalized[$field] = SlipGajiCalculator::parseRupiah($request->input($field));
            }
            $fieldHarian = "tunj_harian_{$key}";
            if ($request->has($fieldHarian)) {
                $normalized[$fieldHarian] = SlipGajiCalculator::parseRupiah($request->input($fieldHarian));
            }
        }

        $request->merge($normalized);
    }

    private function copyPreviousMonthRedirect(string $target, int $bulan, int $tahun): RedirectResponse
    {
        $params = [
            'bulan' => $bulan,
            'tahun' => $tahun,
        ];

        return $target === 'review'
            ? redirect()->route('review.index', $params)
            : redirect()->route('slip.create', $params);
    }
}
