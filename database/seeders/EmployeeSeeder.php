<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $list = config('employees.list', []);

        if ($list === []) {
            $this->command?->error('Data karyawan kosong. Isi config/employees.php terlebih dahulu.');

            return;
        }

        $seeded = 0;

        foreach ($list as $nomor => $data) {
            Employee::updateOrCreate(
                ['nomor' => (int) $nomor],
                [
                    'nip' => $data['nip'] ?? null,
                    'name' => $data['name'],
                    'email' => strtolower(trim($data['email'])),
                    'jabatan' => $data['jabatan'],
                    'alamat' => $data['alamat'] ?? 'Samarinda',
                    'tgl_lahir' => $data['tgl_lahir'] ?? null,
                    'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                    'tgl_masuk' => $data['tgl_masuk'],
                    'is_active' => $data['is_active'] ?? true,
                ]
            );

            $seeded++;
        }

        $this->command?->info("Seeder karyawan selesai: {$seeded} data (nomor 1–{$seeded}).");
    }
}
