<?php

namespace App\Http\Controllers\Api\WarehouseSystem\WarehouseInbound;

use App\Http\Controllers\Controller;
use App\Support\CacheInvalidation;
use App\Models\WarehouseSystem\WarehouseInbound;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WarehouseInboundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_barang', 'gudang_id', 'kategori', 'tanggal_masuk', 'qty', 'satuan', 'harga_satuan', 'total_harga', 'nama_supplier'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $records = WarehouseInbound::query()
            ->with('gudang')
            ->leftJoin('gudang', 'gudang.id', '=', 'warehouse_inbounds.gudang_id')
            ->select('warehouse_inbounds.*')
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('warehouse_inbounds.nama_barang', 'like', '%'.$keyword.'%')
                        ->orWhere('warehouse_inbounds.kategori', 'like', '%'.$keyword.'%')
                        ->orWhere('warehouse_inbounds.nama_supplier', 'like', '%'.$keyword.'%')
                        ->orWhere('gudang.nama_gudang', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy(
                match ($sortField) {
                    'gudang_id' => 'gudang.nama_gudang',
                    default => 'warehouse_inbounds.'.$sortField,
                },
                $sortOrder
            )
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data inbound berhasil diambil.',
            'data' => $records->items(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);
        $payload['total_harga'] = $this->calculateTotalHarga($payload['qty'], $payload['harga_satuan']);

        $record = DB::transaction(function () use ($payload): WarehouseInbound {
            $record = WarehouseInbound::query()->create($payload);

            $this->syncStockFromInbound($record);

            return $record;
        });
        CacheInvalidation::flushStockCaches();

        return response()->json([
            'message' => 'Data inbound berhasil ditambahkan.',
            'data' => $record->load('gudang'),
        ], 201);
    }

    public function show(WarehouseInbound $inbound): JsonResponse
    {
        return response()->json([
            'message' => 'Detail inbound berhasil diambil.',
            'data' => $inbound->load('gudang'),
        ]);
    }

    public function update(Request $request, WarehouseInbound $inbound): JsonResponse
    {
        $payload = $this->validatePayload($request);
        $payload['total_harga'] = $this->calculateTotalHarga($payload['qty'], $payload['harga_satuan']);

        DB::transaction(function () use ($inbound, $payload): void {
            $inbound->update($payload);
            $this->syncStockFromInbound($inbound->fresh());
        });
        CacheInvalidation::flushStockCaches();

        return response()->json([
            'message' => 'Data inbound berhasil diperbarui.',
            'data' => $inbound->fresh()->load('gudang'),
        ]);
    }

    public function destroy(WarehouseInbound $inbound): JsonResponse
    {
        DB::transaction(function () use ($inbound): void {
            $this->deleteSyncedStockRecords($inbound->id);
            $inbound->delete();
        });
        CacheInvalidation::flushStockCaches();

        return response()->json([
            'message' => 'Data inbound berhasil dihapus.',
        ]);
    }

    /**
     * @return array{gudang_id:int,nama_barang:string,kategori:string,tanggal_masuk:string,qty:numeric-string|float|int,satuan:string,harga_satuan:numeric-string|float|int,nama_supplier:string}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'gudang_id' => ['required', 'integer', 'exists:gudang,id'],
            'nama_barang' => ['required', 'string', 'max:100'],
            'kategori' => ['required', Rule::in(['basah', 'kering'])],
            'tanggal_masuk' => ['required', 'date'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'satuan' => ['required', 'string', 'max:50'],
            'harga_satuan' => ['required', 'numeric', 'min:0'],
            'nama_supplier' => ['required', 'string', 'max:100'],
        ]);
    }

    /**
     * @param numeric-string|float|int $qty
     * @param numeric-string|float|int $hargaSatuan
     */
    private function calculateTotalHarga($qty, $hargaSatuan): float
    {
        return (float) $qty * (float) $hargaSatuan;
    }

    private function syncStockFromInbound(WarehouseInbound $inbound): void
    {
        $this->deleteSyncedStockRecords($inbound->id);

        $attributes = [
            'warehouse_inbound_id' => $inbound->id,
            'gudang_id' => $inbound->gudang_id,
            'nama_barang' => $inbound->nama_barang,
            'qty' => $inbound->qty,
            'satuan_terkecil' => $inbound->satuan,
            'harga_beli' => $inbound->harga_satuan,
        ];

        if ($inbound->kategori === 'kering') {
            WarehouseStokKering::query()->create($attributes);

            return;
        }

        WarehouseStokBasah::query()->create($attributes);
    }

    private function deleteSyncedStockRecords(int $inboundId): void
    {
        WarehouseStokKering::query()
            ->where('warehouse_inbound_id', $inboundId)
            ->delete();

        WarehouseStokBasah::query()
            ->where('warehouse_inbound_id', $inboundId)
            ->delete();
    }
}
