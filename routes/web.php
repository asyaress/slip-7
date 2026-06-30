<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorSetupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebugController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ReviewSlipController;
use App\Http\Controllers\SlipGajiController;
use App\Http\Controllers\TwoFactorDeviceController;
use Illuminate\Support\Facades\Route;

Route::get('/debug/status', [DebugController::class, 'status'])->name('debug.status');
Route::get('/debug/last-error', [DebugController::class, 'lastError'])->name('debug.last-error');
Route::get('/debug/test-slip', [DebugController::class, 'testSlipBuild'])->name('debug.test-slip');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('pending.auth')->group(function () {
    Route::get('/two-factor/challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('/two-factor/challenge', [TwoFactorChallengeController::class, 'verify']);
});

Route::middleware('two-factor.setup')->group(function () {
    Route::get('/two-factor/setup', [TwoFactorSetupController::class, 'show'])->name('two-factor.setup');
    Route::post('/two-factor/setup/confirm', [TwoFactorSetupController::class, 'confirm'])->name('two-factor.setup.confirm');
    Route::get('/two-factor/recovery-codes', [TwoFactorSetupController::class, 'recoveryCodes'])->name('two-factor.recovery-codes');
    Route::post('/two-factor/recovery-codes', [TwoFactorSetupController::class, 'acknowledgeRecovery'])->name('two-factor.recovery-codes.acknowledge');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/karyawan', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/karyawan/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/karyawan/{employee}', [EmployeeController::class, 'update'])->name('employees.update');

    Route::get('/slip-gaji', [SlipGajiController::class, 'create'])->name('slip.create');
    Route::get('/slip-gaji/existing', [SlipGajiController::class, 'existing'])->name('slip.existing');
    Route::get('/slip-gaji/lembur-weeks', [SlipGajiController::class, 'lemburWeeks'])->name('slip.lembur-weeks');
    Route::get('/slip-gaji/monthly-tunjangan', [SlipGajiController::class, 'monthlyTunjangan'])->name('slip.monthly-tunjangan');
    Route::get('/slip-gaji/{slip}/edit', [SlipGajiController::class, 'edit'])->name('slip.edit');
    Route::post('/slip-gaji/copy-previous', [SlipGajiController::class, 'copyPreviousMonth'])->name('slip.copy-previous');
    Route::post('/slip-gaji/autosave', [SlipGajiController::class, 'autoSave'])->name('slip.autosave');
    Route::post('/slip-gaji/preview', [SlipGajiController::class, 'preview'])->name('slip.preview');
    Route::post('/slip-gaji', [SlipGajiController::class, 'store'])->name('slip.store');

    Route::get('/review', [ReviewSlipController::class, 'index'])->name('review.index');
    Route::get('/review/{slip}', [ReviewSlipController::class, 'show'])->name('review.show');
    Route::get('/review/{slip}/print', [ReviewSlipController::class, 'print'])->name('review.print');
    Route::post('/review/blast', [ReviewSlipController::class, 'blast'])->name('review.blast');
    Route::post('/review/{slip}/send', [ReviewSlipController::class, 'sendOne'])->name('review.send');

    Route::get('/security/two-factor', [TwoFactorDeviceController::class, 'index'])->name('security.two-factor');
    Route::post('/security/two-factor/devices', [TwoFactorDeviceController::class, 'store'])->name('security.two-factor.devices.store');
    Route::delete('/security/two-factor/devices/{device}', [TwoFactorDeviceController::class, 'destroy'])->name('security.two-factor.devices.destroy');
});
