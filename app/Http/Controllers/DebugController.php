<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\SlipGajiBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DebugController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $this->authorizeDebug($request);

        $logPath = storage_path('logs/laravel.log');
        $logTail = File::exists($logPath)
            ? collect(preg_split('/\r\n|\r|\n/', (string) File::get($logPath)))->take(-40)->implode("\n")
            : null;

        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));

        $checks = [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'app_key_set' => ! empty(config('app.key')),
            'php_version' => PHP_VERSION,
            'proc_open' => function_exists('proc_open') && ! in_array('proc_open', $disabled, true),
            'storage_writable' => is_writable(storage_path()),
            'bootstrap_cache_writable' => is_writable(base_path('bootstrap/cache')),
            'public_storage_link' => is_link(public_path('storage')),
            'kop_png' => file_exists(public_path('images/kop.png')),
            'logo_png' => file_exists(public_path('images/logo_m.png')),
            'qr_script' => file_exists(base_path('scripts/generate_qr_signature.py')),
        ];

        $db = ['connected' => false, 'error' => null];
        try {
            DB::connection()->getPdo();
            $db['connected'] = true;
            $db['database'] = DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            $db['error'] = $e->getMessage();
        }

        $tables = [
            'users' => Schema::hasTable('users'),
            'sessions' => Schema::hasTable('sessions'),
            'employees' => Schema::hasTable('employees'),
            'salary_slips' => Schema::hasTable('salary_slips'),
            'salary_slips.lembur' => Schema::hasTable('salary_slips') && Schema::hasColumn('salary_slips', 'lembur'),
            'two_factor_devices' => Schema::hasTable('two_factor_devices'),
        ];

        $pendingMigrations = [];
        try {
            $ran = collect(DB::table('migrations')->pluck('migration'));
            $files = collect(File::files(database_path('migrations')))->map(fn ($f) => pathinfo($f->getFilename(), PATHINFO_FILENAME));
            $pendingMigrations = $files->diff($ran)->values()->all();
        } catch (\Throwable $e) {
            $pendingMigrations = ['error' => $e->getMessage()];
        }

        return response()->json([
            'ok' => $db['connected'] && empty($pendingMigrations['error'] ?? null),
            'checks' => $checks,
            'database' => $db,
            'tables' => $tables,
            'pending_migrations' => $pendingMigrations,
            'employee_count' => Schema::hasTable('employees') ? Employee::count() : null,
            'log_tail' => $logTail,
            'hint' => 'Matikan debug setelah selesai: bash scripts/vps-disable-debug.sh',
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function lastError(Request $request): View
    {
        $this->authorizeDebug($request);

        $logPath = storage_path('logs/laravel.log');
        $content = File::exists($logPath) ? File::get($logPath) : 'Log file tidak ditemukan.';

        $blocks = preg_split('/\n(?=\[\d{4}-\d{2}-\d{2})/', $content) ?: [];
        $last = array_slice($blocks, -3);

        return view('debug.last-error', [
            'entries' => $last,
            'appDebug' => config('app.debug'),
        ]);
    }

    public function testSlipBuild(Request $request): JsonResponse
    {
        $this->authorizeDebug($request);

        try {
            $employee = Employee::where('is_active', true)->orderBy('nomor')->first();

            if (! $employee) {
                return response()->json(['ok' => false, 'error' => 'Tidak ada karyawan aktif. Jalankan EmployeeSeeder.'], 422);
            }

            $validated = [
                'employee_id' => $employee->id,
                'bulan' => (int) now()->format('n'),
                'tahun' => (int) now()->format('Y'),
                'gaji_pokok' => 3000000,
                'tunj_transport' => 0,
                'tunj_kehadiran' => 0,
                'tunj_kinerja' => 0,
                'tunj_jabatan' => 0,
                'tunj_perawatan' => 0,
                'tunj_operator' => 0,
                'tunj_konsumsi' => 0,
                'pot_angsuran' => 0,
                'pot_kasbon' => 0,
                'pot_lain_lain' => 0,
                'jumlah_kehadiran' => 25,
                'hadir' => 25,
                'sakit_izin' => 0,
                'tidak_hadir' => 0,
                'bpjs_kesehatan' => 186222,
                'makan_siang_malam' => 0,
                'pensiun' => 0,
                'lembur' => ['weeks' => [], 'total' => 0],
            ];

            $slip = SlipGajiBuilder::buildFromValidated($validated, $employee);
            $slip = SlipGajiBuilder::attachQrSignature($slip);

            return response()->json([
                'ok' => true,
                'employee' => $employee->name,
                'nomor_surat' => $slip['nomor_surat'],
                'take_home_pay' => $slip['take_home_pay'],
                'qr_path' => $slip['qr_signature_path'] ?? null,
                'qr_url' => $slip['qr_signature_url'] ?? null,
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect(explode("\n", $e->getTraceAsString()))->take(15)->values(),
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    private function authorizeDebug(Request $request): void
    {
        if (config('app.debug')) {
            return;
        }

        $key = (string) env('DEBUG_KEY', '');
        $provided = (string) $request->query('key', '');

        if ($key !== '' && hash_equals($key, $provided)) {
            return;
        }

        abort(404);
    }
}
