<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_employee_from_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('employees.create'))
            ->assertOk()
            ->assertSee('Tambah Karyawan');

        $response = $this->actingAs($user)->post(route('employees.store'), [
            'name' => 'Andi Syahrul Saputra',
            'email' => 'wermap41@gmail.com',
            'jabatan' => 'Staff Desain',
            'alamat' => 'Samarinda',
            'tgl_lahir' => '2007-11-05',
            'jenis_kelamin' => 'LAKI-LAKI',
            'tgl_masuk' => '2026-10-27',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('employees.index'));

        $employee = Employee::query()->where('email', 'wermap41@gmail.com')->first();
        $this->assertNotNull($employee);
        $this->assertSame(1, $employee->nomor);
        $this->assertStringStartsWith('TSG-15-', (string) $employee->nip);
    }
}
