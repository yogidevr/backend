<?php

namespace App\Http\Controllers\Api\TransaksiPenjualan\Penjualan\PenjualanItem;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPembelian\OrderPenawaranItem;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\PenjualanItem;
use App\Support\CacheInvalidation;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class PenjualanItemController extends Controller
{
    public function index(Request $request, Penjualan $penjualan): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['nama_barang', 'qty', 'satuan', 'harga_satuan', 'total_harga', 'stok_tersedia', 'status_stok'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_barang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;
        $currentPage = max((int) $request->query('page', 1), 1);

        $items = $penjualan->items()
            ->with(['gudang', 'perusahaan:id,nama_perusahaan,alamat,nama_pic,tema_invoice,logo_path'])
            ->when($search, function ($query, string $keyword): void {
                $query->where('nama_barang', 'like', '%'.$keyword.'%');
            })
            ->orderBy('id')
            ->get()
            ->map(fn (PenjualanItem $item): array => $this->serializeItem($item))
            ->values();

        if ($items->isEmpty() && $penjualan->order_penawaran_id !== null) {
            $items = OrderPenawaranItem::query()
                ->where('order_penawaran_id', $penjualan->order_penawaran_id)
                ->when($search, function ($query, string $keyword): void {
                    $query->where('nama_barang', 'like', '%'.$keyword.'%');
                })
                ->orderBy('id')
                ->get()
                ->map(function (OrderPenawaranItem $item): array {
                    return [
                        'id' => $item->id,
                        'penjualan_id' => null,
                        'order_penawaran_item_id' => $item->id,
                        'produk_id' => $item->produk_id,
                        'gudang_id' => null,
                        'perusahaan_id' => null,
                        'perusahaan' => null,
                        'gudang' => null,
                        'nama_barang' => $item->nama_barang,
                        'qty' => $item->qty,
                        'satuan' => $item->satuan,
                        'harga_satuan' => $item->harga_satuan,
                        'total_harga' => number_format((float) $item->qty * (float) $item->harga_satuan, 2, '.', ''),
                        'keterangan' => $item->keterangan,
                        'stok_tersedia' => number_format(
                            $this->resolveAvailableStock($item->nama_barang, null),
                            2,
                            '.',
                            ''
                        ),
                        'status_stok' => $this->resolveAvailableStock($item->nama_barang, null) >= (float) $item->qty
                            ? 'berhasil'
                            : 'pending',
                    ];
                })
                ->values();
        }

        $items = $items
            ->sort(function (array $first, array $second) use ($sortField, $sortOrder): int {
                $firstValue = $first[$sortField] ?? null;
                $secondValue = $second[$sortField] ?? null;

                if (in_array($sortField, ['qty', 'harga_satuan', 'total_harga', 'stok_tersedia'], true)) {
                    $comparison = (float) $firstValue <=> (float) $secondValue;

                    return $sortOrder === 'asc' ? $comparison : -$comparison;
                }

                $comparison = strnatcasecmp((string) $firstValue, (string) $secondValue);

                return $sortOrder === 'asc' ? $comparison : -$comparison;
            })
            ->values();

        $paginatedItems = new LengthAwarePaginator(
            $items->forPage($currentPage, $perPage)->values()->all(),
            $items->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return response()->json([
            'message' => 'Data item penjualan berhasil diambil.',
            'data' => $paginatedItems->items(),
            'meta' => [
                'current_page' => $paginatedItems->currentPage(),
                'last_page' => $paginatedItems->lastPage(),
                'per_page' => $paginatedItems->perPage(),
                'total' => $paginatedItems->total(),
                'from' => $paginatedItems->firstItem(),
                'to' => $paginatedItems->lastItem(),
            ],
        ]);
    }

    public function store(Request $request, Penjualan $penjualan): JsonResponse
    {
        $payload = $this->validatePayload($request, $penjualan);

        $item = DB::transaction(function () use ($penjualan, $payload): PenjualanItem {
            $item = $this->persistItem(new PenjualanItem(), $penjualan, $payload);
            $this->refreshParentTotal($penjualan);

            return $item;
        });
        CacheInvalidation::flushFinancialCaches();

        return response()->json([
            'message' => 'Item penjualan berhasil ditambahkan.',
            'data' => $this->serializeItem($item->load(['gudang', 'perusahaan:id,nama_perusahaan,alamat,nama_pic,tema_invoice,logo_path'])),
        ], 201);
    }

    public function show(Penjualan $penjualan, PenjualanItem $item): JsonResponse
    {
        $this->ensureItemBelongsToPenjualan($penjualan, $item);

        return response()->json([
            'message' => 'Detail item penjualan berhasil diambil.',
            'data' => $this->serializeItem($item->load(['gudang', 'perusahaan:id,nama_perusahaan,alamat,nama_pic,tema_invoice,logo_path'])),
        ]);
    }

    public function update(Request $request, Penjualan $penjualan, PenjualanItem $item): JsonResponse
    {
        $this->ensureItemBelongsToPenjualan($penjualan, $item);
        $payload = $this->validatePayload($request, $penjualan);

        $item = DB::transaction(function () use ($item, $penjualan, $payload): PenjualanItem {
            $updatedItem = $this->persistItem($item, $penjualan, $payload);
            $this->refreshParentTotal($penjualan);

            return $updatedItem;
        });
        CacheInvalidation::flushFinancialCaches();

        return response()->json([
            'message' => 'Item penjualan berhasil diperbarui.',
            'data' => $this->serializeItem($item->load(['gudang', 'perusahaan:id,nama_perusahaan,alamat,nama_pic,tema_invoice,logo_path'])),
        ]);
    }

    public function destroy(Penjualan $penjualan, PenjualanItem $item): JsonResponse
    {
        $this->ensureItemBelongsToPenjualan($penjualan, $item);

        DB::transaction(function () use ($penjualan, $item): void {
            $item->delete();
            $this->refreshParentTotal($penjualan);
        });
        CacheInvalidation::flushFinancialCaches();

        return response()->json([
            'message' => 'Item penjualan berhasil dihapus.',
        ]);
    }

    public function opsiBarang(Penjualan $penjualan): JsonResponse
    {
        $items = OrderPenawaranItem::query()
            ->with('orderPenawaran:id,tanggal_dikirim')
            ->when($penjualan->order_penawaran_id !== null, function ($query) use ($penjualan): void {
                $query->where('order_penawaran_id', $penjualan->order_penawaran_id);
            }, function ($query) use ($penjualan): void {
                $query->whereHas('orderPenawaran', function ($orderQuery) use ($penjualan): void {
                    $orderQuery->whereDate('tanggal_dikirim', $penjualan->tanggal);
                });
            })
            ->orderBy('nama_barang')
            ->get();

        $options = $items
            ->groupBy(function (OrderPenawaranItem $item): string {
                return implode('|', [
                    $item->produk_id ?? 'null',
                    mb_strtolower(trim($item->nama_barang)),
                    (string) $item->harga_satuan,
                    mb_strtolower(trim((string) $item->satuan)),
                ]);
            })
            ->map(function (Collection $group): array {
                /** @var OrderPenawaranItem $item */
                $item = $group->first();

                return [
                    'order_penawaran_item_id' => $item->id,
                    'produk_id' => $item->produk_id,
                    'nama_barang' => $item->nama_barang,
                    'harga_satuan' => $item->harga_satuan,
                    'satuan' => $item->satuan,
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Opsi barang penjualan berhasil diambil.',
            'data' => $options,
        ]);
    }

    private function validatePayload(Request $request, Penjualan $penjualan): array
    {
        $payload = $request->validate([
            'order_penawaran_item_id' => ['required', 'integer', 'exists:order_penawaran_items,id'],
            'gudang_id' => ['required', 'integer', 'exists:gudang,id'],
            'perusahaan_id' => ['required', 'integer', 'exists:perusahaan,id'],
            'qty' => ['required', 'numeric', 'gt:0'],
        ]);

        $sourceItem = OrderPenawaranItem::query()
            ->with('orderPenawaran:id,tanggal_dikirim')
            ->findOrFail($payload['order_penawaran_item_id']);

        $belongsToSameSource = $penjualan->order_penawaran_id !== null
            ? $sourceItem->order_penawaran_id === $penjualan->order_penawaran_id
            : $sourceItem->orderPenawaran !== null && $sourceItem->orderPenawaran->tanggal_dikirim === $penjualan->tanggal->format('Y-m-d');

        if (! $belongsToSameSource) {
            throw ValidationException::withMessages([
                'order_penawaran_item_id' => 'Barang hanya boleh diambil dari order penawaran sumber penjualan yang sama.',
            ]);
        }

        $payload['_source_item'] = $sourceItem;

        return $payload;
    }

    private function persistItem(PenjualanItem $item, Penjualan $penjualan, array $payload): PenjualanItem
    {
        /** @var OrderPenawaranItem $sourceItem */
        $sourceItem = $payload['_source_item'];

        $item->fill([
            'penjualan_id' => $penjualan->id,
            'order_penawaran_item_id' => $sourceItem->id,
            'produk_id' => $sourceItem->produk_id,
            'gudang_id' => $payload['gudang_id'],
            'perusahaan_id' => $payload['perusahaan_id'],
            'nama_barang' => $sourceItem->nama_barang,
            'qty' => $payload['qty'],
            'satuan' => $sourceItem->satuan,
            'harga_satuan' => $sourceItem->harga_satuan,
            'total_harga' => (float) $payload['qty'] * (float) $sourceItem->harga_satuan,
        ]);

        $item->save();

        return $item->fresh([
            'gudang',
            'perusahaan:id,nama_perusahaan,alamat,nama_pic,tema_invoice,logo_path',
        ]);
    }

    private function refreshParentTotal(Penjualan $penjualan): void
    {
        $penjualan->update([
            'total_harga' => $penjualan->items()->sum('total_harga'),
        ]);
    }

    private function ensureItemBelongsToPenjualan(Penjualan $penjualan, PenjualanItem $item): void
    {
        abort_if($item->penjualan_id !== $penjualan->id, 404);
    }

    private function serializeItem(PenjualanItem $item): array
    {
        $stokTersedia = $this->resolveAvailableStock($item->nama_barang, $item->gudang_id);

        return [
            'id' => $item->id,
            'penjualan_id' => $item->penjualan_id,
            'order_penawaran_item_id' => $item->order_penawaran_item_id,
            'produk_id' => $item->produk_id,
            'gudang_id' => $item->gudang_id,
            'gudang' => $item->gudang
                ? [
                    'id' => $item->gudang->id,
                    'nama_gudang' => $item->gudang->nama_gudang,
                ]
                : null,
            'perusahaan_id' => $item->perusahaan_id,
            'perusahaan' => $item->perusahaan
                ? [
                    'id' => $item->perusahaan->id,
                    'nama_perusahaan' => $item->perusahaan->nama_perusahaan,
                    'alamat' => $item->perusahaan->alamat,
                    'nama_pic' => $item->perusahaan->nama_pic,
                    'tema_invoice' => $item->perusahaan->tema_invoice ?? 'theme_01',
                    'logo_url' => $item->perusahaan->logo_url,
                ]
                : null,
            'nama_barang' => $item->nama_barang,
            'qty' => number_format((float) $item->qty, 2, '.', ''),
            'satuan' => $item->satuan,
            'harga_satuan' => number_format((float) $item->harga_satuan, 2, '.', ''),
            'total_harga' => number_format((float) $item->total_harga, 2, '.', ''),
            'stok_tersedia' => number_format($stokTersedia, 2, '.', ''),
            'status_stok' => $stokTersedia >= (float) $item->qty ? 'berhasil' : 'pending',
        ];
    }

    private function resolveAvailableStock(string $namaBarang, ?int $gudangId): float
    {
        $normalizedName = mb_strtolower(trim($namaBarang));

        $stokBasah = WarehouseStokBasah::query()
            ->when($gudangId !== null, fn ($query) => $query->where('gudang_id', $gudangId))
            ->whereRaw('LOWER(TRIM(nama_barang)) = ?', [$normalizedName])
            ->sum('qty');

        $stokKering = WarehouseStokKering::query()
            ->when($gudangId !== null, fn ($query) => $query->where('gudang_id', $gudangId))
            ->whereRaw('LOWER(TRIM(nama_barang)) = ?', [$normalizedName])
            ->sum('qty');

        return (float) $stokBasah + (float) $stokKering;
    }
}
