<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('surat_jalan')) {
            return;
        }

        if (! Schema::hasColumn('surat_jalan', 'perusahaan_id')) {
            Schema::table('surat_jalan', function (Blueprint $table): void {
                $table->foreignId('perusahaan_id')
                    ->nullable()
                    ->after('driver_id')
                    ->constrained('perusahaan')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('surat_jalan') || ! Schema::hasColumn('surat_jalan', 'perusahaan_id')) {
            return;
        }

        Schema::table('surat_jalan', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('perusahaan_id');
        });
    }
};

