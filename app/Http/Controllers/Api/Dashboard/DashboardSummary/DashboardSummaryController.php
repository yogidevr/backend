<?php

namespace App\Http\Controllers\Api\Dashboard\DashboardSummary;

use App\Http\Controllers\Controller;
use App\Models\KeuanganAkuntansi\Pengeluaran;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\InvoicePenjualan;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use App\Support\CacheInvalidation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardSummaryController extends Controller
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
            'dashboard_summary:%s:%s',
            $tanggalAwal,
            $tanggalAkhir
        );

$summary = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($tanggalAwal, $tanggalAkhir): array{
            $omsetPeriode = (float) Penjualan::query()
                ->whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
                ->where('status', 'selesai')
                ->sum('total_harga');

            $pengeluaranPeriode = (float) Pengeluaran::query()
                ->whereBetween('tanggal_keluar', [$tanggalAwal, $tanggalAkhir])
                ->selectRaw('COALESCE(SUM(qty * harga_satuan), 0) as total_pengeluaran')
                ->value('total_pengeluaran');

            $invoiceBelumLunas = (float) InvoicePenjualan::query()
                ->where('status_pembayaran', 'belum lunas')
                ->whereBetween('tanggal_invoice', [$tanggalAwal, $tanggalAkhir])
                ->sum('total_tagihan');

            $nilaiStok = (float) WarehouseStokKering::query()
                ->selectRaw('COALESCE(SUM(qty * harga_beli), 0) as total_nilai')
                ->value('total_nilai')
                + (float) WarehouseStokBasah::query()
                    ->selectRaw('COALESCE(SUM(qty * harga_beli), 0) as total_nilai')
                    ->value('total_nilai');

            return [
                'omset_periode' => $omsetPeriode,
                'pengeluaran_periode' => $pengeluaranPeriode,
                'keuntungan_periode' => $omsetPeriode - $pengeluaranPeriode,
                'invoice_belum_lunas' => $invoiceBelumLunas,
                'nilai_stok' => $nilaiStok,
            ];
        });

        return response()->json([
            'message' => 'Ringkasan dashboard berhasil diambil.',
            'data' => [
                'tanggal_awal' => $tanggalAwal,
                'tanggal_akhir' => $tanggalAkhir,
                ...$summary,
            ],
        ]);
    }
}
