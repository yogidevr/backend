<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('penjualan_items') && Schema::hasColumn('penjualan_items', 'gudang_id')) {
            Schema::table('penjualan_items', function (Blueprint $table): void {
                $table->integer('gudang_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('penjualan_items') && Schema::hasColumn('penjualan_items', 'gudang_id')) {
            Schema::table('penjualan_items', function (Blueprint $table): void {
                $table->integer('gudang_id')->nullable(false)->change();
            });
        }
    }
};
