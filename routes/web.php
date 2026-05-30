<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ReviewSlipController;
use App\Http\Controllers\SlipGajiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/karyawan', [EmployeeController::class, 'index'])->name('employees.index');
Route::get('/karyawan/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
Route::put('/karyawan/{employee}', [EmployeeController::class, 'update'])->name('employees.update');

Route::get('/slip-gaji', [SlipGajiController::class, 'create'])->name('slip.create');
Route::post('/slip-gaji/preview', [SlipGajiController::class, 'preview'])->name('slip.preview');
Route::post('/slip-gaji', [SlipGajiController::class, 'store'])->name('slip.store');

Route::get('/review', [ReviewSlipController::class, 'index'])->name('review.index');
Route::get('/review/{slip}', [ReviewSlipController::class, 'show'])->name('review.show');
Route::get('/review/{slip}/print', [ReviewSlipController::class, 'print'])->name('review.print');
Route::post('/review/blast', [ReviewSlipController::class, 'blast'])->name('review.blast');
Route::post('/review/{slip}/send', [ReviewSlipController::class, 'sendOne'])->name('review.send');
