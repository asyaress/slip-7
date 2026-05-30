<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('employees.list') as $nomor => $data) {
            Employee::updateOrCreate(
                ['nomor' => $nomor],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'jabatan' => $data['jabatan'],
                    'alamat' => $data['alamat'],
                    'tgl_masuk' => $data['tgl_masuk'],
                    'is_active' => true,
                ]
            );
        }
    }
}
