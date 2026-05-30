<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->string('nomor_surat');
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->json('tunjangan');
            $table->json('potongan');
            $table->decimal('bpjs_kesehatan', 15, 2)->default(0);
            $table->decimal('makan_siang_malam', 15, 2)->default(0);
            $table->decimal('pensiun', 15, 2)->default(0);
            $table->unsignedTinyInteger('jumlah_kehadiran')->default(0);
            $table->unsignedTinyInteger('hadir')->default(0);
            $table->unsignedTinyInteger('sakit_izin')->default(0);
            $table->unsignedTinyInteger('tidak_hadir')->default(0);
            $table->decimal('total_tunjangan', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('take_home_pay', 15, 2)->default(0);
            $table->decimal('total_fasilitas', 15, 2)->default(0);
            $table->decimal('total_pendapatan', 15, 2)->default(0);
            $table->timestamp('email_sent_at')->nullable();
            $table->string('email_status')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
    }
};
