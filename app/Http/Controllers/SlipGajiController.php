<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalarySlip;
use App\Services\SlipGajiBuilder;
use App\Services\SlipGajiCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlipGajiController extends Controller
{
    private const RUPIAH_FIELDS = [
        'gaji_pokok',
        'tunj_transport', 'tunj_kehadiran', 'tunj_kinerja', 'tunj_jabatan',
        'tunj_perawatan', 'tunj_operator', 'tunj_konsumsi',
        'pot_angsuran', 'pot_kasbon',
        'bpjs_kesehatan', 'makan_siang_malam', 'pensiun',
    ];

    public function create(): View
    {
        $employees = Employee::where('is_active', true)->orderBy('nomor')->get();

        return view('slip.create', compact('employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSlip($request);
        $employee = Employee::findOrFail($validated['employee_id']);

        $slip = SlipGajiBuilder::buildFromValidated($validated, $employee);
        $saved = SlipGajiBuilder::saveSlip($slip, $employee);

        return redirect()->route('review.show', $saved)
            ->with('success', "Slip gaji {$employee->name} berhasil disimpan.");
    }

    public function preview(Request $request): View
    {
        $validated = $this->validateSlip($request);
        $employee = Employee::findOrFail($validated['employee_id']);

        $slip = SlipGajiBuilder::buildFromValidated($validated, $employee);
        $slip = SlipGajiBuilder::attachQrSignature($slip);

        return view('slip.preview', compact('slip'));
    }

    private function validateSlip(Request $request): array
    {
        $this->normalizeRupiahFields($request);

        return $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2100',
            'gaji_pokok' => 'required|numeric|min:0',
            'tunj_transport' => 'nullable|numeric|min:0',
            'tunj_kehadiran' => 'nullable|numeric|min:0',
            'tunj_kinerja' => 'nullable|numeric|min:0',
            'tunj_jabatan' => 'nullable|numeric|min:0',
            'tunj_perawatan' => 'nullable|numeric|min:0',
            'tunj_operator' => 'nullable|numeric|min:0',
            'tunj_konsumsi' => 'nullable|numeric|min:0',
            'pot_angsuran' => 'nullable|numeric|min:0',
            'pot_kasbon' => 'nullable|numeric|min:0',
            'jumlah_kehadiran' => 'required|integer|min:0',
            'hadir' => 'required|integer|min:0',
            'sakit_izin' => 'nullable|integer|min:0',
            'tidak_hadir' => 'nullable|integer|min:0',
            'bpjs_kesehatan' => 'nullable|numeric|min:0',
            'makan_siang_malam' => 'nullable|numeric|min:0',
            'pensiun' => 'nullable|numeric|min:0',
        ]);
    }

    private function normalizeRupiahFields(Request $request): void
    {
        $normalized = [];

        foreach (self::RUPIAH_FIELDS as $field) {
            if ($request->has($field)) {
                $normalized[$field] = SlipGajiCalculator::parseRupiah($request->input($field));
            }
        }

        $request->merge($normalized);
    }
}
