<?php

use App\Http\Controllers\Api\ActivityLog\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Dashboard\DashboardCashflowTrend\DashboardCashflowTrendController;
use App\Http\Controllers\Api\Dashboard\DashboardExpenseAnalysis\DashboardExpenseAnalysisController;
use App\Http\Controllers\Api\Dashboard\DashboardInventorySummary\DashboardInventorySummaryController;
use App\Http\Controllers\Api\Dashboard\DashboardSalesBySppg\DashboardSalesBySppgController;
use App\Http\Controllers\Api\Dashboard\DashboardSummary\DashboardSummaryController;
use App\Http\Controllers\Api\KeuanganAkuntansi\Pemasukan\PemasukanController;
use App\Http\Controllers\Api\KeuanganAkuntansi\Pengeluaran\PengeluaranController;
use App\Http\Controllers\Api\LaporanAnalisa\LabaRugiTransaksional\LabaRugiTransaksionalController;
use App\Http\Controllers\Api\LaporanAnalisa\LaporanStokBarang\LaporanStokBarangController;
use App\Http\Controllers\Api\LaporanAnalisa\PenjualanPerSppg\PenjualanPerSppgController;
use App\Http\Controllers\Api\MasterData\Armada\ArmadaController;
use App\Http\Controllers\Api\MasterData\BankRekening\BankRekeningController;
use App\Http\Controllers\Api\MasterData\Gudang\GudangController;
use App\Http\Controllers\Api\MasterData\Karyawan\KaryawanController;
use App\Http\Controllers\Api\MasterData\Kategori\KategoriController;
use App\Http\Controllers\Api\MasterData\Mitra\MitraController;
use App\Http\Controllers\Api\MasterData\Perusahaan\PerusahaanController;
use App\Http\Controllers\Api\MasterData\Produk\ProdukController;
use App\Http\Controllers\Api\MasterData\Sppg\SppgController;
use App\Http\Controllers\Api\MasterData\Supplier\SupplierController;
use App\Http\Controllers\Api\MasterData\Wilayah\WilayahController;
use App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaan\DaftarPembelanjaanController;
use App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaan\DaftarPembelanjaanItem\DaftarPembelanjaanItemController;
use App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaanSupplier\DaftarPembelanjaanSupplierController;
use App\Http\Controllers\Api\TransaksiPembelian\OrderPenawaran\OrderPenawaranController;
use App\Http\Controllers\Api\TransaksiPembelian\OrderPenawaran\OrderPenawaranItem\OrderPenawaranItemController;
use App\Http\Controllers\Api\TransaksiPenjualan\InvoicePenjualan\InvoicePenjualanController;
use App\Http\Controllers\Api\TransaksiPenjualan\Penjualan\PenjualanController;
use App\Http\Controllers\Api\TransaksiPenjualan\Penjualan\PenjualanItem\PenjualanItemController;
use App\Http\Controllers\Api\TransaksiPenjualan\SuratJalan\SuratJalanController;
use App\Http\Controllers\Api\TransaksiPenjualan\SuratJalan\SuratJalanItem\SuratJalanItemController;
use App\Http\Controllers\Api\TransaksiPenjualan\TandaTerima\TandaTerimaController;
use App\Http\Controllers\Api\TransaksiPenjualan\TandaTerima\TandaTerimaItem\TandaTerimaItemController;
use App\Http\Controllers\Api\UserManagement\Permission\PermissionController;
use App\Http\Controllers\Api\UserManagement\User\UserController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseInbound\WarehouseInboundController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseRetur\WarehouseReturController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseStokBasah\WarehouseStokBasahController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseStokKering\WarehouseStokKeringController;
use Illuminate\Support\Facades\Route;

//Autenthication
Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth.api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth.api', 'activity.log'])->group(function (): void {
    Route::get('activity-logs', [ActivityLogController::class, 'index']);

    Route::middleware('permission:users.view')->group(function (): void {
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::get('permissions', [PermissionController::class, 'index']);
    });
    Route::middleware('permission:users.manage')->group(function (): void {
        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
    });

    //============================== Dashboard =============================
    Route::middleware('permission:dashboard.view')->group(function (): void {
        Route::get('dashboard/summary', DashboardSummaryController::class);
        Route::get('dashboard/penjualan-per-sppg', DashboardSalesBySppgController::class);
        Route::get('dashboard/cashflow-trend', DashboardCashflowTrendController::class);
        Route::get('dashboard/beban-operasional', DashboardExpenseAnalysisController::class);
        Route::get('dashboard/persediaan', DashboardInventorySummaryController::class);
    });

    //============================== Laporan dan Analisa =====================
    Route::middleware('permission:laporan.view')->group(function (): void {
        Route::get('laporan/stok-barang', LaporanStokBarangController::class);
        Route::get('laporan/laba-rugi-transaksional', LabaRugiTransaksionalController::class);
        Route::get('laporan/penjualan-per-sppg', PenjualanPerSppgController::class);
    });

    //============================== Keuangan dan Akuntansi =====================
    Route::middleware('permission:keuangan.view')->group(function (): void {
        Route::apiResource('pemasukan', PemasukanController::class)->only(['index', 'show']);
        Route::apiResource('pengeluaran', PengeluaranController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:keuangan.manage')->group(function (): void {
        Route::apiResource('pemasukan', PemasukanController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('pengeluaran', PengeluaranController::class)->only(['store', 'update', 'destroy']);
    });

    // ============================== Master Data ===========================
    Route::middleware('permission:master.view')->group(function (): void {
        Route::apiResource('wilayah', WilayahController::class)->only(['index', 'show']);
        Route::apiResource('supplier', SupplierController::class)->only(['index', 'show']);
        Route::apiResource('mitra', MitraController::class)->only(['index', 'show']);
        Route::apiResource('sppg', SppgController::class)->only(['index', 'show']);
        Route::apiResource('produk', ProdukController::class)->only(['index', 'show']);
        Route::apiResource('perusahaan', PerusahaanController::class)->only(['index', 'show']);
        Route::apiResource('gudang', GudangController::class)->only(['index', 'show']);
        Route::apiResource('armada', ArmadaController::class)->only(['index', 'show']);
        Route::apiResource('karyawan', KaryawanController::class)->only(['index', 'show']);
        Route::apiResource('bank-rekening', BankRekeningController::class)->only(['index', 'show']);
        Route::apiResource('kategori', KategoriController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.manage')->group(function (): void {
        Route::apiResource('wilayah', WilayahController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('supplier', SupplierController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('mitra', MitraController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('sppg', SppgController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('produk', ProdukController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('perusahaan', PerusahaanController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('gudang', GudangController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('armada', ArmadaController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('karyawan', KaryawanController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('bank-rekening', BankRekeningController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('kategori', KategoriController::class)->only(['store', 'update', 'destroy']);
    });

    // ============================ Transaksi Pembelian ===========================
    Route::middleware('permission:pembelian.view')->group(function (): void {
        // Order Penawaran
        Route::apiResource('order-penawaran', OrderPenawaranController::class)->only(['index', 'show']);
        Route::get('order-penawaran/filter/by-tanggal', [OrderPenawaranController::class, 'byTanggal']);
        Route::get('order-penawaran/{orderPenawaran}/items', [OrderPenawaranItemController::class, 'index']);
        Route::get('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'show']);
        // daftar pembelanjaan
        Route::apiResource('daftar-pembelanjaan', DaftarPembelanjaanController::class)->only(['index', 'show']);
        Route::get('daftar-pembelanjaan/{daftarPembelanjaan}/items', [DaftarPembelanjaanItemController::class, 'index']);
        Route::get('daftar-pembelanjaan/{daftarPembelanjaan}/items/{item}', [DaftarPembelanjaanItemController::class, 'show']);
        // daftar pembelanjaan supplier
        Route::get('daftar-pembelanjaan-supplier', [DaftarPembelanjaanSupplierController::class, 'index']);
        Route::get('daftar-pembelanjaan-supplier/{daftarPembelanjaan}', [DaftarPembelanjaanSupplierController::class, 'show']);
    });
    Route::middleware('permission:pembelian.manage')->group(function (): void {
        // Order Penawaran
        Route::apiResource('order-penawaran', OrderPenawaranController::class)->only(['store', 'update', 'destroy']);
        Route::post('order-penawaran/{orderPenawaran}/items', [OrderPenawaranItemController::class, 'store']);
        Route::put('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'update']);
        Route::delete('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'destroy']);
        // daftar pembelanjaan
        Route::apiResource('daftar-pembelanjaan', DaftarPembelanjaanController::class)->only(['store', 'update', 'destroy']);
        Route::post('daftar-pembelanjaan/{daftarPembelanjaan}/items', [DaftarPembelanjaanItemController::class, 'store']);
        Route::put('daftar-pembelanjaan/{daftarPembelanjaan}/items/{item}', [DaftarPembelanjaanItemController::class, 'update']);
        Route::delete('daftar-pembelanjaan/{daftarPembelanjaan}/items/{item}', [DaftarPembelanjaanItemController::class, 'destroy']);
    });

    // ============================ Transaksi Penjualan ===========================
    Route::middleware('permission:penjualan.view')->group(function (): void {
        Route::apiResource('penjualan', PenjualanController::class)->only(['index', 'show']);
        Route::get('penjualan/{penjualan}/opsi-barang', [PenjualanItemController::class, 'opsiBarang']);
        Route::get('penjualan/{penjualan}/items', [PenjualanItemController::class, 'index']);
        Route::get('penjualan/{penjualan}/items/{item}', [PenjualanItemController::class, 'show']);

        Route::get('surat-jalan/opsi-perusahaan', [SuratJalanController::class, 'opsiPerusahaan']);
        Route::apiResource('surat-jalan', SuratJalanController::class)->only(['index', 'show']);
        Route::get('surat-jalan/{suratJalan}/opsi-barang', [SuratJalanItemController::class, 'opsiBarang']);
        Route::get('surat-jalan/{suratJalan}/items', [SuratJalanItemController::class, 'index']);
        Route::get('surat-jalan/{suratJalan}/items/{item}', [SuratJalanItemController::class, 'show']);

        Route::get('tanda-terima/opsi-perusahaan', [TandaTerimaController::class, 'opsiPerusahaan']);
        Route::apiResource('tanda-terima', TandaTerimaController::class)->only(['index', 'show']);
        Route::get('tanda-terima/{tandaTerima}/opsi-barang', [TandaTerimaItemController::class, 'opsiBarang']);
        Route::get('tanda-terima/{tandaTerima}/items', [TandaTerimaItemController::class, 'index']);
        Route::get('tanda-terima/{tandaTerima}/items/{item}', [TandaTerimaItemController::class, 'show']);

        Route::get('invoice-penjualan/opsi-sppg', [InvoicePenjualanController::class, 'opsiSppgByTanggalKirim']);
        Route::get('invoice-penjualan/opsi-accounting', [InvoicePenjualanController::class, 'opsiAccounting']);
        Route::get('invoice-penjualan/opsi-bank-rekening', [InvoicePenjualanController::class, 'opsiBankRekening']);
        Route::get('invoice-penjualan/opsi-perusahaan', [InvoicePenjualanController::class, 'opsiPerusahaan']);
        Route::apiResource('invoice-penjualan', InvoicePenjualanController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:penjualan.manage')->group(function (): void {
        Route::apiResource('penjualan', PenjualanController::class)->only(['store', 'update', 'destroy']);
        Route::post('penjualan/{penjualan}/items', [PenjualanItemController::class, 'store']);
        Route::put('penjualan/{penjualan}/items/{item}', [PenjualanItemController::class, 'update']);
        Route::delete('penjualan/{penjualan}/items/{item}', [PenjualanItemController::class, 'destroy']);

        Route::apiResource('surat-jalan', SuratJalanController::class)->only(['store', 'update', 'destroy']);
        Route::post('surat-jalan/{suratJalan}/items', [SuratJalanItemController::class, 'store']);
        Route::put('surat-jalan/{suratJalan}/items/{item}', [SuratJalanItemController::class, 'update']);
        Route::delete('surat-jalan/{suratJalan}/items/{item}', [SuratJalanItemController::class, 'destroy']);

        Route::apiResource('tanda-terima', TandaTerimaController::class)->only(['store', 'update', 'destroy']);
        Route::post('tanda-terima/{tandaTerima}/items', [TandaTerimaItemController::class, 'store']);
        Route::put('tanda-terima/{tandaTerima}/items/{item}', [TandaTerimaItemController::class, 'update']);
        Route::delete('tanda-terima/{tandaTerima}/items/{item}', [TandaTerimaItemController::class, 'destroy']);

        Route::apiResource('invoice-penjualan', InvoicePenjualanController::class)->only(['store', 'update', 'destroy']);
    });

    // ============================= Warehouse System ===========================
    Route::middleware('permission:warehouse.view')->group(function (): void {
        Route::apiResource('inbound', WarehouseInboundController::class)->only(['index', 'show']);
        Route::apiResource('stok-kering', WarehouseStokKeringController::class)->only(['index', 'show']);
        Route::apiResource('stok-basah', WarehouseStokBasahController::class)->only(['index', 'show']);
        Route::apiResource('retur-rusak', WarehouseReturController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:warehouse.manage')->group(function (): void {
        Route::apiResource('inbound', WarehouseInboundController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('stok-kering', WarehouseStokKeringController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('stok-basah', WarehouseStokBasahController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('retur-rusak', WarehouseReturController::class)->only(['store', 'update', 'destroy']);
    });
});
// ============================ TEST API ===========================
Route::get('/test', function () {
    return response()->json([
        'message' => 'ok'
    ]);
});
