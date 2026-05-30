<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = Employee::orderBy('nomor')->get();

        return view('employees.index', compact('employees'));
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'jabatan' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'tgl_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:LAKI-LAKI,PEREMPUAN',
            'tgl_masuk' => 'required|date',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $tglLahir = $validated['tgl_lahir'] ?? $employee->tgl_lahir?->format('Y-m-d');
        if ($tglLahir) {
            $validated['nip'] = \App\Services\NipService::generate(
                $employee->nomor,
                $validated['tgl_masuk'],
                $tglLahir,
            );
        }

        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', "Data karyawan {$employee->name} berhasil diperbarui.");
    }
}
