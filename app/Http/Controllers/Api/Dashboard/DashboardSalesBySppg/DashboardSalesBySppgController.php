<?php

namespace App\Http\Controllers\Api\Dashboard\DashboardSalesBySppg;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPenjualan\SuratJalanItem;
use App\Support\CacheInvalidation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardSalesBySppgController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal_awal' => ['nullable', 'date'],
            'tanggal_akhir' => ['nullable', 'date', 'after_or_equal:tanggal_awal'],
        ]);

        $today = Carbon::today('Asia/Jakarta');
        $tanggalAwal = isset($validated['tanggal_awal'])
            ? Carbon::parse($validated['tanggal_awal'], 'Asia/Jakarta')->toDateString()
            : $today->copy()->startOfMonth()->toDateString();
        $tanggalAkhir = isset($validated['tanggal_akhir'])
            ? Carbon::parse($validated['tanggal_akhir'], 'Asia/Jakarta')->toDateString()
            : $today->toDateString();

        $cacheKey = sprintf(
            'dashboard_sales_by_sppg:%s:%s',
            $tanggalAwal,
            $tanggalAkhir
        );

      $salesBySppg = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($tanggalAwal, $tanggalAkhir): array {
            $rows = SuratJalanItem::query()
                ->selectRaw('surat_jalan.sppg_id, sppg.nama_sppg, SUM(penjualan_items.total_harga) as total_penjualan')
                ->join('surat_jalan', 'surat_jalan.id', '=', 'surat_jalan_items.surat_jalan_id')
                ->join('penjualan_items', 'penjualan_items.id', '=', 'surat_jalan_items.penjualan_item_id')
                ->join('penjualan', 'penjualan.id', '=', 'penjualan_items.penjualan_id')
                ->join('sppg', 'sppg.id', '=', 'surat_jalan.sppg_id')
                ->whereNotNull('surat_jalan.sppg_id')
                ->where('surat_jalan.status', 'selesai')
                ->where('penjualan.status', 'selesai')
                ->whereBetween('surat_jalan.tanggal', [$tanggalAwal, $tanggalAkhir])
                ->groupBy('surat_jalan.sppg_id', 'sppg.nama_sppg')
                ->orderByDesc('total_penjualan')
                ->get();

            $totalGlobal = (float) $rows->sum('total_penjualan');

            $breakdown = $rows->map(function ($row) use ($totalGlobal): array {
                $totalPenjualan = (float) $row->total_penjualan;

                return [
                    'sppg_id' => $row->sppg_id,
                    'nama_sppg' => $row->nama_sppg,
                    'total_penjualan' => $totalPenjualan,
                    'persentase' => $totalGlobal > 0
                        ? round(($totalPenjualan / $totalGlobal) * 100, 2)
                        : 0,
                ];
            })->values()->all();

            return [
                'total_penjualan_global' => $totalGlobal,
                'breakdown' => $breakdown,
            ];
        });

        return response()->json([
            'message' => 'Data penjualan per SPPG berhasil diambil.',
            'data' => [
                'tanggal_awal' => $tanggalAwal,
                'tanggal_akhir' => $tanggalAkhir,
                ...$salesBySppg,
            ],
        ]);
    }
}
