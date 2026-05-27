<?php

namespace App\Http\Controllers\Api\TransaksiPenjualan\SuratJalan\SuratJalanItem;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPembelian\OrderPenawaranItem;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\PenjualanItem;
use App\Models\TransaksiPenjualan\SuratJalan;
use App\Models\TransaksiPenjualan\SuratJalanItem;
use App\Support\CacheInvalidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class SuratJalanItemController extends Controller
{
    public function index(Request $request, SuratJalan $suratJalan): JsonResponse
    {
        $this->syncItemsFromPenjualan($suratJalan);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['nama_barang', 'qty', 'satuan', 'keterangan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_barang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;
        $currentPage = max((int) $request->query('page', 1), 1);

        $items = $suratJalan->items()
            ->with('penjualanItem.perusahaan')
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_barang', 'like', '%'.$keyword.'%')
                        ->orWhere('keterangan', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy('id')
            ->get()
            ->sort(function ($first, $second) use ($sortField, $sortOrder): int {
                $firstValue = $first->{$sortField} ?? null;
                $secondValue = $second->{$sortField} ?? null;

                if ($sortField === 'qty') {
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
            'message' => 'Data item surat jalan berhasil diambil.',
            'data' => collect($paginatedItems->items())
                ->map(fn (SuratJalanItem $item): array => $this->serializeItem($item))
                ->values(),
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

    public function store(Request $request, SuratJalan $suratJalan): JsonResponse
    {
        $payload = $this->validatePayload($request);
        $item = $this->persistItem(new SuratJalanItem(), $suratJalan, $payload);
        CacheInvalidation::flushDashboardSalesBySppg();

        return response()->json([
            'message' => 'Item surat jalan berhasil ditambahkan.',
            'data' => $this->serializeItem($item),
        ], 201);
    }

    public function show(SuratJalan $suratJalan, SuratJalanItem $item): JsonResponse
    {
        $this->syncItemsFromPenjualan($suratJalan);
        $this->ensureItemBelongsToSuratJalan($suratJalan, $item);

        return response()->json([
            'message' => 'Detail item surat jalan berhasil diambil.',
            'data' => $this->serializeItem($item),
        ]);
    }

    public function update(Request $request, SuratJalan $suratJalan, SuratJalanItem $item): JsonResponse
    {
        $this->ensureItemBelongsToSuratJalan($suratJalan, $item);
        $payload = $this->validatePayload($request);
        $item = $this->persistItem($item, $suratJalan, $payload);
        CacheInvalidation::flushDashboardSalesBySppg();

        return response()->json([
            'message' => 'Item surat jalan berhasil diperbarui.',
            'data' => $this->serializeItem($item),
        ]);
    }

    public function destroy(SuratJalan $suratJalan, SuratJalanItem $item): JsonResponse
    {
        $this->ensureItemBelongsToSuratJalan($suratJalan, $item);
        $item->delete();
        CacheInvalidation::flushDashboardSalesBySppg();

        return response()->json([
            'message' => 'Item surat jalan berhasil dihapus.',
        ]);
    }

    public function opsiBarang(): JsonResponse
    {
        $options = PenjualanItem::query()
            ->orderBy('nama_barang')
            ->get([
                'id',
                'nama_barang',
                'qty',
                'satuan',
            ]);

        return response()->json([
            'message' => 'Opsi barang surat jalan berhasil diambil.',
            'data' => $options,
        ]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'penjualan_item_id' => ['nullable', 'integer', 'exists:penjualan_items,id'],
            'nama_barang' => ['required_without:penjualan_item_id', 'string', 'max:100'],
            'qty' => ['required_without:penjualan_item_id', 'numeric', 'gt:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string'],
        ]);
    }

    private function persistItem(SuratJalanItem $item, SuratJalan $suratJalan, array $payload): SuratJalanItem
    {
        $sourceItem = isset($payload['penjualan_item_id'])
            ? PenjualanItem::query()->findOrFail($payload['penjualan_item_id'])
            : null;

        if ($sourceItem !== null) {
            $this->ensureSourceItemMatchesCurrentSuratJalan($suratJalan, $sourceItem, $item);
        }

        $item->fill([
            'surat_jalan_id' => $suratJalan->id,
            'penjualan_item_id' => $sourceItem?->id,
            'nama_barang' => $sourceItem?->nama_barang ?? $payload['nama_barang'],
            'qty' => $sourceItem?->qty ?? $payload['qty'],
            'satuan' => $sourceItem?->satuan ?? ($payload['satuan'] ?? null),
            'keterangan' => $payload['keterangan'] ?? null,
        ]);

        $item->save();

        return $item->fresh(['penjualanItem.perusahaan']);
    }

    private function serializeItem(SuratJalanItem $item): array
    {
        $item->loadMissing('penjualanItem.perusahaan');
        $perusahaan = $item->penjualanItem?->perusahaan;

        return [
            'id' => $item->id,
            'surat_jalan_id' => $item->surat_jalan_id,
            'penjualan_item_id' => $item->penjualan_item_id,
            'nama_barang' => $item->nama_barang,
            'qty' => $item->qty,
            'satuan' => $item->satuan,
            'keterangan' => $item->keterangan,
            'perusahaan_id' => $perusahaan?->id,
            'perusahaan' => $perusahaan ? [
                'id' => $perusahaan->id,
                'nama_perusahaan' => $perusahaan->nama_perusahaan,
                'alamat' => $perusahaan->alamat,
                'nama_pic' => $perusahaan->nama_pic,
                'tema_invoice' => $perusahaan->tema_invoice,
                'logo_url' => $perusahaan->logo_url,
                'logo_data_url' => $this->resolvePerusahaanLogoDataUrl($perusahaan->getRawOriginal('logo_path')),
            ] : null,
        ];
    }

    private function resolvePerusahaanLogoDataUrl(?string $logoPath): ?string
    {
        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $extension = Str::lower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        $binary = Storage::disk('public')->get($logoPath);

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    private function ensureItemBelongsToSuratJalan(SuratJalan $suratJalan, SuratJalanItem $item): void
    {
        abort_if($item->surat_jalan_id !== $suratJalan->id, 404);
    }

    private function ensureSourceItemMatchesCurrentSuratJalan(
        SuratJalan $suratJalan,
        PenjualanItem $sourceItem,
        SuratJalanItem $currentItem
    ): void {
        $existingPenjualanId = $suratJalan->items()
            ->whereNotNull('surat_jalan_items.penjualan_item_id')
            ->when($currentItem->exists, fn ($query) => $query->where('surat_jalan_items.id', '!=', $currentItem->id))
            ->join('penjualan_items', 'penjualan_items.id', '=', 'surat_jalan_items.penjualan_item_id')
            ->value('penjualan_items.penjualan_id');

        if ($existingPenjualanId !== null && (int) $existingPenjualanId !== (int) $sourceItem->penjualan_id) {
            throw ValidationException::withMessages([
                'penjualan_item_id' => 'Item surat jalan harus berasal dari transaksi penjualan yang sama.',
            ]);
        }
    }

    private function syncItemsFromPenjualan(SuratJalan $suratJalan): void
    {
        if ($suratJalan->tanggal === null) {
            $suratJalan->items()->delete();

            return;
        }

        $sourceItems = $this->queryMatchingPenjualan($suratJalan)
            ->with('items.perusahaan')
            ->orderBy('id')
            ->get()
            ->flatMap(fn (Penjualan $penjualan) => $this->resolvePenjualanSourceItems($penjualan))
            ->values();

        $existingItems = $suratJalan->items()
            ->get()
            ->keyBy(fn ($item) => $this->buildSourceKey(
                $item->penjualan_item_id,
                $item->nama_barang,
                $item->qty,
                $item->satuan
            ));

        $suratJalan->items()->delete();

        foreach ($sourceItems as $sourceItem) {
            $currentItem = $existingItems->get($this->buildSourceKey(
                $sourceItem['penjualan_item_id'],
                $sourceItem['nama_barang'],
                $sourceItem['qty'],
                $sourceItem['satuan']
            ));

            $suratJalan->items()->create([
                'penjualan_item_id' => $sourceItem['penjualan_item_id'],
                'nama_barang' => $sourceItem['nama_barang'],
                'qty' => $sourceItem['qty'],
                'satuan' => $sourceItem['satuan'],
                'keterangan' => $currentItem?->keterangan,
            ]);
        }
    }

    private function resolvePenjualanSourceItems(Penjualan $penjualan): Collection
    {
        if ($penjualan->items->isNotEmpty()) {
            return $penjualan->items->map(fn ($item): array => [
                'penjualan_item_id' => $item->id,
                'nama_barang' => $item->nama_barang,
                'qty' => $item->qty,
                'satuan' => $item->satuan,
            ]);
        }

        if ($penjualan->order_penawaran_id === null) {
            return collect();
        }

        return OrderPenawaranItem::query()
            ->where('order_penawaran_id', $penjualan->order_penawaran_id)
            ->orderBy('id')
            ->get()
            ->map(fn (OrderPenawaranItem $item): array => [
                'penjualan_item_id' => null,
                'nama_barang' => $item->nama_barang,
                'qty' => $item->qty,
                'satuan' => $item->satuan,
            ]);
    }

    private function queryMatchingPenjualan(SuratJalan $suratJalan)
    {
        return Penjualan::query()
            ->whereDate('tanggal', $suratJalan->tanggal)
            ->when(
                $suratJalan->sppg_id !== null && $suratJalan->relationLoaded('sppg')
                    ? $suratJalan->sppg?->nama_sppg
                    : $suratJalan->sppg()->value('nama_sppg'),
                function ($query, string $namaSppg): void {
                    $query->whereHas('orderPenawaran', function ($orderQuery) use ($namaSppg): void {
                        $orderQuery->where('nama_pembeli', $namaSppg);
                    });
                }
            );
    }

    private function buildSourceKey(
        ?int $penjualanItemId,
        string $namaBarang,
        string|float|int $qty,
        ?string $satuan
    ): string {
        return implode('|', [
            $penjualanItemId ?? 'null',
            mb_strtolower(trim($namaBarang)),
            number_format((float) $qty, 2, '.', ''),
            mb_strtolower(trim((string) $satuan)),
        ]);
    }
}
