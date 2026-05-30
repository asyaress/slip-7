<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('nip')->nullable()->unique()->after('nomor');
            $table->date('tgl_lahir')->nullable()->after('alamat');
            $table->string('jenis_kelamin', 20)->nullable()->after('tgl_lahir');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['nip', 'tgl_lahir', 'jenis_kelamin']);
        });
    }
};
