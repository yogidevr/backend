<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tanda_terima')) {
            return;
        }

        if (! Schema::hasColumn('tanda_terima', 'perusahaan_id')) {
            Schema::table('tanda_terima', function (Blueprint $table): void {
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
        if (! Schema::hasTable('tanda_terima') || ! Schema::hasColumn('tanda_terima', 'perusahaan_id')) {
            return;
        }

        Schema::table('tanda_terima', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('perusahaan_id');
        });
    }
};

