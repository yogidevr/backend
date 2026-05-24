<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('daftar_pembelanjaan_items')) {
            return;
        }

        Schema::table('daftar_pembelanjaan_items', function (Blueprint $table): void {
            if (!Schema::hasColumn('daftar_pembelanjaan_items', 'produk_id')) {
                $this->addMatchingIntegerColumn($table, 'produk', 'produk_id', 'nama_barang');
            }

            if (!Schema::hasColumn('daftar_pembelanjaan_items', 'kategori_id')) {
                $this->addMatchingIntegerColumn($table, 'kategori', 'kategori_id', 'satuan');
            }
        });

        $produkMap = DB::table('produk')
            ->select(['id', 'nama'])
            ->get()
            ->mapWithKeys(fn ($row) => [
                mb_strtolower((string) $row->nama) => $row->id
            ]);

        $kategoriMap = DB::table('kategori')
            ->select(['id', 'kode', 'nama_satuan'])
            ->get()
            ->flatMap(function ($row): array {
                $pairs = [];

                if ($row->kode !== null) {
                    $pairs[mb_strtolower((string) $row->kode)] = $row->id;
                }

                if ($row->nama_satuan !== null) {
                    $pairs[mb_strtolower((string) $row->nama_satuan)] = $row->id;
                }

                return $pairs;
            });

        DB::table('daftar_pembelanjaan_items')
            ->select([
                'id',
                'nama_barang',
                'satuan',
                'produk_id',
                'kategori_id'
            ])
            ->orderBy('id')
            ->get()
            ->each(function ($item) use ($produkMap, $kategoriMap): void {
                $updates = [];

                if ($item->produk_id === null) {
                    $produkId = $produkMap[
                        mb_strtolower((string) $item->nama_barang)
                    ] ?? null;

                    if ($produkId !== null) {
                        $updates['produk_id'] = $produkId;
                    }
                }

                if ($item->kategori_id === null) {
                    $kategoriId = $kategoriMap[
                        mb_strtolower((string) $item->satuan)
                    ] ?? null;

                    if ($kategoriId !== null) {
                        $updates['kategori_id'] = $kategoriId;
                    }
                }

                if ($updates !== []) {
                    DB::table('daftar_pembelanjaan_items')
                        ->where('id', $item->id)
                        ->update($updates);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('daftar_pembelanjaan_items')) {
            return;
        }

        Schema::table('daftar_pembelanjaan_items', function (Blueprint $table): void {
            foreach (['produk_id', 'kategori_id'] as $column) {
                if (Schema::hasColumn('daftar_pembelanjaan_items', $column)) {
                    $table->dropForeign([$column]);
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function addMatchingIntegerColumn(
        Blueprint $table,
        string $referenceTable,
        string $column,
        string $after
    ): void {
        $columnType = $this->getIntegerColumnType($referenceTable);
        $isUnsigned = $this->isIntegerColumnUnsigned($referenceTable);

        $definition = match ($columnType) {
            'bigint' => $isUnsigned
                ? $table->unsignedBigInteger($column)
                : $table->bigInteger($column),

            'mediumint' => $isUnsigned
                ? $table->unsignedMediumInteger($column)
                : $table->mediumInteger($column),

            'smallint' => $isUnsigned
                ? $table->unsignedSmallInteger($column)
                : $table->smallInteger($column),

            'tinyint' => $isUnsigned
                ? $table->unsignedTinyInteger($column)
                : $table->tinyInteger($column),

            default => $isUnsigned
                ? $table->unsignedInteger($column)
                : $table->integer($column),
        };

        $definition->nullable()->after($after);

        $table->foreign($column)
            ->references('id')
            ->on($referenceTable)
            ->nullOnDelete();
    }

    private function getIntegerColumnType(string $table): string
    {
        $driver = DB::getDriverName();

        // PostgreSQL & SQLite
        if (in_array($driver, ['sqlite', 'pgsql'])) {
            return 'integer';
        }

        $type = Schema::getColumnType($table, 'id');

        return is_string($type)
            ? strtolower($type)
            : 'int';
    }

    private function isIntegerColumnUnsigned(string $table): bool
    {
        $driver = DB::getDriverName();

        // PostgreSQL dan SQLite tidak punya unsigned integer
        if (in_array($driver, ['sqlite', 'pgsql'])) {
            return false;
        }

        $columnType = DB::table('information_schema.columns')
            ->select('COLUMN_TYPE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', 'id')
            ->value('COLUMN_TYPE');

        return is_string($columnType)
            && str_contains(strtolower($columnType), 'unsigned');
    }
};