<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_tunjangan_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->json('rates');
            $table->timestamps();

            $table->unique(['bulan', 'tahun']);
        });

        Schema::table('salary_slips', function (Blueprint $table) {
            $table->json('tunjangan_bulanan')->nullable()->after('tunjangan');
            $table->json('fasilitas')->nullable()->after('pensiun');
        });
    }

    public function down(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->dropColumn(['tunjangan_bulanan', 'fasilitas']);
        });

        Schema::dropIfExists('monthly_tunjangan_rates');
    }
};
