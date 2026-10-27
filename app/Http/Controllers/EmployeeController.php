<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\NipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = Employee::orderBy('nomor')->get();

        return view('employees.index', compact('employees'));
    }

    public function create(): View
    {
        return view('employees.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEmployee($request);

        $nomor = $this->nextNomor();
        $nip = $this->resolveNip($nomor, $validated);

        $employee = Employee::create([
            'nomor' => $nomor,
            'nip' => $nip,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'jabatan' => $validated['jabatan'],
            'alamat' => $validated['alamat'],
            'tgl_lahir' => $validated['tgl_lahir'] ?? null,
            'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
            'tgl_masuk' => $validated['tgl_masuk'],
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->route('employees.index')
            ->with('success', "Karyawan {$employee->name} berhasil ditambahkan (No. {$employee->nomor}).");
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $this->validateEmployee($request, $employee);

        $tglLahir = $validated['tgl_lahir'] ?? $employee->tgl_lahir?->format('Y-m-d');
        if ($tglLahir) {
            $validated['nip'] = NipService::generate(
                $employee->nomor,
                $validated['tgl_masuk'],
                $tglLahir,
            );
        }

        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', "Data karyawan {$employee->name} berhasil diperbarui.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employee?->id),
            ],
            'jabatan' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string', 'max:255'],
            'tgl_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:LAKI-LAKI,PEREMPUAN'],
            'tgl_masuk' => ['required', 'date'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['email'] = strtolower(trim((string) $validated['email']));

        return $validated;
    }

    private function nextNomor(): int
    {
        $max = (int) Employee::query()->max('nomor');

        return $max + 1;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveNip(int $nomor, array $validated): ?string
    {
        $tglLahir = $validated['tgl_lahir'] ?? null;
        if (! $tglLahir) {
            return null;
        }

        return NipService::generate(
            $nomor,
            (string) $validated['tgl_masuk'],
            (string) $tglLahir,
        );
    }
}
