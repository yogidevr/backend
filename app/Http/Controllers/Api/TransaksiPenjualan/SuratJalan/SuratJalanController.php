<?php

namespace App\Http\Controllers\Api\TransaksiPenjualan\SuratJalan;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Karyawan;
use App\Models\TransaksiPembelian\OrderPenawaranItem;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\SuratJalan;
use App\Support\CacheInvalidation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratJalanController extends Controller
{
    public function opsiDriver(): JsonResponse
    {
        $drivers = Karyawan::query()
            ->where('jabatan', 'like', '%Driver%')
            ->whereRaw('LOWER(status) = ?', ['aktif'])
            ->orderBy('nama')
            ->get(['id', 'nama', 'jabatan', 'status'])
            ->map(fn (Karyawan $driver): array => [
                'id' => $driver->id,
                'nama' => $driver->nama,
                'jabatan' => $driver->jabatan,
                'status' => Str::lower($driver->status ?? ''),
            ])
            ->values();

        return response()->json([
            'message' => 'Opsi driver surat jalan berhasil diambil.',
            'data' => $drivers,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nomor_surat_jalan', 'no_po', 'tanggal', 'status'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'tanggal';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 10;

        $records = SuratJalan::query()
            ->with([
                'sppg:id,nama_sppg',
                'armadaRef:id,nama_unit,no_pol',
                'driver:id,nama',
            ])
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nomor_surat_jalan', 'like', '%'.$keyword.'%')
                        ->orWhere('no_po', 'like', '%'.$keyword.'%')
                        ->orWhereHas('sppg', fn ($sppgQuery) => $sppgQuery->where('nama_sppg', 'like', '%'.$keyword.'%'))
                        ->orWhereHas('driver', fn ($driverQuery) => $driverQuery->where('nama', 'like', '%'.$keyword.'%'));
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data surat jalan berhasil diambil.',
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
        $record = DB::transaction(function () use ($request): SuratJalan {
            $record = SuratJalan::query()->create($this->validatePayload($request));
            $this->syncItemsFromPenjualan($record);

            return $record;
        });
        CacheInvalidation::flushDashboardSalesBySppg();

        return response()->json([
            'message' => 'Data surat jalan berhasil ditambahkan.',
            'data' => $record->fresh([
                'sppg:id,nama_sppg',
                'armadaRef:id,nama_unit,no_pol',
                'driver:id,nama',
                'perusahaanRef:id,nama_perusahaan,alamat,nama_pic,tema_invoice,logo_path',
                'items.penjualanItem',
            ]),
        ], 201);
    }

    public function show(SuratJalan $suratJalan): JsonResponse
    {
        $this->syncItemsFromPenjualan($suratJalan);

        $suratJalan->load([
            'sppg:id,nama_sppg',
            'armadaRef:id,nama_unit,no_pol',
            'driver:id,nama',
            'items.penjualanItem',
        ]);

        return response()->json([
            'message' => 'Detail surat jalan berhasil diambil.',
            'data' => $suratJalan->toArray(),
        ]);
    }

    public function update(Request $request, SuratJalan $suratJalan): JsonResponse
    {
        DB::transaction(function () use ($request, $suratJalan): void {
            $suratJalan->update($this->validatePayload($request, $suratJalan));
            $this->syncItemsFromPenjualan($suratJalan);
        });
        CacheInvalidation::flushDashboardSalesBySppg();

        return response()->json([
            'message' => 'Data surat jalan berhasil diperbarui.',
            'data' => $suratJalan->fresh([
                'sppg:id,nama_sppg',
                'armadaRef:id,nama_unit,no_pol',
                'driver:id,nama',
                'items.penjualanItem',
            ]),
        ]);
    }

    public function destroy(SuratJalan $suratJalan): JsonResponse
    {
        $suratJalan->delete();
        CacheInvalidation::flushDashboardSalesBySppg();

        return response()->json([
            'message' => 'Data surat jalan berhasil dihapus.',
        ]);
    }

    private function validatePayload(Request $request, ?SuratJalan $suratJalan = null): array
    {
        $payload = $request->validate([
            'nomor_surat_jalan' => [
                'required',
                'string',
                'max:50',
                Rule::unique('surat_jalan', 'nomor_surat_jalan')->ignore($suratJalan?->id),
            ],
            'no_po' => ['nullable', 'string', 'max:50'],
            'tanggal' => ['required', 'date'],
            'sppg_id' => ['nullable', 'integer', 'exists:sppg,id'],
            'armada_id' => ['nullable', 'integer', 'exists:armada,id'],
            'driver_id' => ['nullable', 'integer', 'exists:karyawan,id'],
            'status' => ['required', Rule::in(['draft', 'selesai', 'batal'])],
        ]);

        if ($payload['driver_id'] !== null) {
            $driver = Karyawan::query()->findOrFail($payload['driver_id']);
            $jabatan = Str::lower($driver->jabatan ?? '');
            $status = Str::lower($driver->status ?? '');

            if (! Str::contains($jabatan, 'driver') || $status !== 'aktif') {
                throw ValidationException::withMessages([
                    'driver_id' => 'Driver yang dipilih harus karyawan aktif dengan jabatan Driver.',
                ]);
            }
        }

        return $payload;
    }

    private function syncItemsFromPenjualan(SuratJalan $suratJalan): void
    {
        if ($suratJalan->tanggal === null) {
            $suratJalan->items()->delete();

            return;
        }

        $sourceItems = $this->queryMatchingPenjualan($suratJalan)
            ->with('items')
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
