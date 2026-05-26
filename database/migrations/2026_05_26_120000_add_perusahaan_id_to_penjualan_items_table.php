<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('penjualan_items', 'perusahaan_id')) {
            Schema::table('penjualan_items', function (Blueprint $table): void {
                $table->foreignId('perusahaan_id')
                    ->nullable()
                    ->after('gudang_id')
                    ->constrained('perusahaan')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('penjualan_items', 'perusahaan_id')) {
            Schema::table('penjualan_items', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('perusahaan_id');
            });
        }
    }
};
