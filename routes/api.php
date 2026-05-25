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
        Route::put('/profile', [AuthController::class, 'updateProfile']);
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
    Route::middleware('permission:users.create')->group(function (): void {
        Route::post('users', [UserController::class, 'store']);
    });
    Route::middleware('permission:users.update')->group(function (): void {
        Route::put('users/{user}', [UserController::class, 'update']);
    });
    Route::middleware('permission:users.delete')->group(function (): void {
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
    Route::middleware('permission:laporan.stok_barang.view')->group(function (): void {
        Route::get('laporan/stok-barang', LaporanStokBarangController::class);
    });
    Route::middleware('permission:laporan.laba_rugi.view')->group(function (): void {
        Route::get('laporan/laba-rugi-transaksional', LabaRugiTransaksionalController::class);
    });
    Route::middleware('permission:laporan.penjualan_sppg.view')->group(function (): void {
        Route::get('laporan/penjualan-per-sppg', PenjualanPerSppgController::class);
    });

    //============================== Keuangan dan Akuntansi =====================
    Route::middleware('permission:keuangan.pemasukan.view')->group(function (): void {
        Route::apiResource('pemasukan', PemasukanController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:keuangan.pemasukan.create')->group(function (): void {
        Route::apiResource('pemasukan', PemasukanController::class)->only(['store']);
    });
    Route::middleware('permission:keuangan.pemasukan.update')->group(function (): void {
        Route::apiResource('pemasukan', PemasukanController::class)->only(['update']);
    });
    Route::middleware('permission:keuangan.pemasukan.delete')->group(function (): void {
        Route::apiResource('pemasukan', PemasukanController::class)->only(['destroy']);
    });
    Route::middleware('permission:keuangan.pengeluaran.view')->group(function (): void {
        Route::apiResource('pengeluaran', PengeluaranController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:keuangan.pengeluaran.create')->group(function (): void {
        Route::apiResource('pengeluaran', PengeluaranController::class)->only(['store']);
    });
    Route::middleware('permission:keuangan.pengeluaran.update')->group(function (): void {
        Route::apiResource('pengeluaran', PengeluaranController::class)->only(['update']);
    });
    Route::middleware('permission:keuangan.pengeluaran.delete')->group(function (): void {
        Route::apiResource('pengeluaran', PengeluaranController::class)->only(['destroy']);
    });

    // ============================== Master Data ===========================
    Route::middleware('permission:master.wilayah.view')->group(function (): void {
        Route::apiResource('wilayah', WilayahController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.wilayah.create')->group(function (): void {
        Route::apiResource('wilayah', WilayahController::class)->only(['store']);
    });
    Route::middleware('permission:master.wilayah.update')->group(function (): void {
        Route::apiResource('wilayah', WilayahController::class)->only(['update']);
    });
    Route::middleware('permission:master.wilayah.delete')->group(function (): void {
        Route::apiResource('wilayah', WilayahController::class)->only(['destroy']);
    });

    Route::middleware('permission:master.supplier.view')->group(function (): void {
        Route::apiResource('supplier', SupplierController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.supplier.create')->group(function (): void {
        Route::apiResource('supplier', SupplierController::class)->only(['store']);
    });
    Route::middleware('permission:master.supplier.update')->group(function (): void {
        Route::apiResource('supplier', SupplierController::class)->only(['update']);
    });
    Route::middleware('permission:master.supplier.delete')->group(function (): void {
        Route::apiResource('supplier', SupplierController::class)->only(['destroy']);
    });

    Route::middleware('permission:master.mitra.view')->group(function (): void {
        Route::apiResource('mitra', MitraController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.mitra.create')->group(function (): void {
        Route::apiResource('mitra', MitraController::class)->only(['store']);
    });
    Route::middleware('permission:master.mitra.update')->group(function (): void {
        Route::apiResource('mitra', MitraController::class)->only(['update']);
    });
    Route::middleware('permission:master.mitra.delete')->group(function (): void {
        Route::apiResource('mitra', MitraController::class)->only(['destroy']);
    });

    Route::middleware('permission:master.sppg.view')->group(function (): void {
        Route::apiResource('sppg', SppgController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.sppg.create')->group(function (): void {
        Route::apiResource('sppg', SppgController::class)->only(['store']);
    });
    Route::middleware('permission:master.sppg.update')->group(function (): void {
        Route::apiResource('sppg', SppgController::class)->only(['update']);
    });
    Route::middleware('permission:master.sppg.delete')->group(function (): void {
        Route::apiResource('sppg', SppgController::class)->only(['destroy']);
    });

    Route::middleware('permission:master.produk.view')->group(function (): void {
        Route::apiResource('produk', ProdukController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.produk.create')->group(function (): void {
        Route::apiResource('produk', ProdukController::class)->only(['store']);
    });
    Route::middleware('permission:master.produk.update')->group(function (): void {
        Route::apiResource('produk', ProdukController::class)->only(['update']);
    });
    Route::middleware('permission:master.produk.delete')->group(function (): void {
        Route::apiResource('produk', ProdukController::class)->only(['destroy']);
    });

    Route::middleware('permission:master.perusahaan.view')->group(function (): void {
        Route::apiResource('perusahaan', PerusahaanController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.perusahaan.create')->group(function (): void {
        Route::apiResource('perusahaan', PerusahaanController::class)->only(['store']);
    });
    Route::middleware('permission:master.perusahaan.update')->group(function (): void {
        Route::apiResource('perusahaan', PerusahaanController::class)->only(['update']);
    });
    Route::middleware('permission:master.perusahaan.delete')->group(function (): void {
        Route::apiResource('perusahaan', PerusahaanController::class)->only(['destroy']);
    });

    Route::middleware('permission:master.gudang.view')->group(function (): void {
        Route::apiResource('gudang', GudangController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.gudang.create')->group(function (): void {
        Route::apiResource('gudang', GudangController::class)->only(['store']);
    });
    Route::middleware('permission:master.gudang.update')->group(function (): void {
        Route::apiResource('gudang', GudangController::class)->only(['update']);
    });
    Route::middleware('permission:master.gudang.delete')->group(function (): void {
        Route::apiResource('gudang', GudangController::class)->only(['destroy']);
    });

    Route::middleware('permission:master.armada.view')->group(function (): void {
        Route::apiResource('armada', ArmadaController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.armada.create')->group(function (): void {
        Route::apiResource('armada', ArmadaController::class)->only(['store']);
    });
    Route::middleware('permission:master.armada.update')->group(function (): void {
        Route::apiResource('armada', ArmadaController::class)->only(['update']);
    });
    Route::middleware('permission:master.armada.delete')->group(function (): void {
        Route::apiResource('armada', ArmadaController::class)->only(['destroy']);
    });

    Route::middleware('permission:master.karyawan.view')->group(function (): void {
        Route::apiResource('karyawan', KaryawanController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.karyawan.create')->group(function (): void {
        Route::apiResource('karyawan', KaryawanController::class)->only(['store']);
    });
    Route::middleware('permission:master.karyawan.update')->group(function (): void {
        Route::apiResource('karyawan', KaryawanController::class)->only(['update']);
    });
    Route::middleware('permission:master.karyawan.delete')->group(function (): void {
        Route::apiResource('karyawan', KaryawanController::class)->only(['destroy']);
    });

    Route::middleware('permission:master.bank_rekening.view')->group(function (): void {
        Route::apiResource('bank-rekening', BankRekeningController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.bank_rekening.create')->group(function (): void {
        Route::apiResource('bank-rekening', BankRekeningController::class)->only(['store']);
    });
    Route::middleware('permission:master.bank_rekening.update')->group(function (): void {
        Route::apiResource('bank-rekening', BankRekeningController::class)->only(['update']);
    });
    Route::middleware('permission:master.bank_rekening.delete')->group(function (): void {
        Route::apiResource('bank-rekening', BankRekeningController::class)->only(['destroy']);
    });

    Route::middleware('permission:master.kategori.view')->group(function (): void {
        Route::apiResource('kategori', KategoriController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:master.kategori.create')->group(function (): void {
        Route::apiResource('kategori', KategoriController::class)->only(['store']);
    });
    Route::middleware('permission:master.kategori.update')->group(function (): void {
        Route::apiResource('kategori', KategoriController::class)->only(['update']);
    });
    Route::middleware('permission:master.kategori.delete')->group(function (): void {
        Route::apiResource('kategori', KategoriController::class)->only(['destroy']);
    });

    // ============================ Transaksi Pembelian ===========================
    Route::middleware('permission:pembelian.order_penawaran.view')->group(function (): void {
        Route::apiResource('order-penawaran', OrderPenawaranController::class)->only(['index', 'show']);
        Route::get('order-penawaran/filter/by-tanggal', [OrderPenawaranController::class, 'byTanggal']);
        Route::get('order-penawaran/{orderPenawaran}/items', [OrderPenawaranItemController::class, 'index']);
        Route::get('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'show']);
    });
    Route::middleware('permission:pembelian.order_penawaran.create')->group(function (): void {
        Route::apiResource('order-penawaran', OrderPenawaranController::class)->only(['store']);
        Route::post('order-penawaran/{orderPenawaran}/items', [OrderPenawaranItemController::class, 'store']);
    });
    Route::middleware('permission:pembelian.order_penawaran.update')->group(function (): void {
        Route::apiResource('order-penawaran', OrderPenawaranController::class)->only(['update']);
        Route::put('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'update']);
    });
    Route::middleware('permission:pembelian.order_penawaran.delete')->group(function (): void {
        Route::apiResource('order-penawaran', OrderPenawaranController::class)->only(['destroy']);
        Route::delete('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'destroy']);
    });

    Route::middleware('permission:pembelian.daftar_pembelanjaan.view')->group(function (): void {
        Route::apiResource('daftar-pembelanjaan', DaftarPembelanjaanController::class)->only(['index', 'show']);
        Route::get('daftar-pembelanjaan/{daftarPembelanjaan}/items', [DaftarPembelanjaanItemController::class, 'index']);
        Route::get('daftar-pembelanjaan/{daftarPembelanjaan}/items/{item}', [DaftarPembelanjaanItemController::class, 'show']);
    });
    Route::middleware('permission:pembelian.daftar_pembelanjaan.create')->group(function (): void {
        Route::apiResource('daftar-pembelanjaan', DaftarPembelanjaanController::class)->only(['store']);
        Route::post('daftar-pembelanjaan/{daftarPembelanjaan}/items', [DaftarPembelanjaanItemController::class, 'store']);
    });
    Route::middleware('permission:pembelian.daftar_pembelanjaan.update')->group(function (): void {
        Route::apiResource('daftar-pembelanjaan', DaftarPembelanjaanController::class)->only(['update']);
        Route::put('daftar-pembelanjaan/{daftarPembelanjaan}/items/{item}', [DaftarPembelanjaanItemController::class, 'update']);
    });
    Route::middleware('permission:pembelian.daftar_pembelanjaan.delete')->group(function (): void {
        Route::apiResource('daftar-pembelanjaan', DaftarPembelanjaanController::class)->only(['destroy']);
        Route::delete('daftar-pembelanjaan/{daftarPembelanjaan}/items/{item}', [DaftarPembelanjaanItemController::class, 'destroy']);
    });

    Route::middleware('permission:pembelian.daftar_pembelanjaan_supplier.view')->group(function (): void {
        Route::get('daftar-pembelanjaan-supplier', [DaftarPembelanjaanSupplierController::class, 'index']);
        Route::get('daftar-pembelanjaan-supplier/{daftarPembelanjaan}', [DaftarPembelanjaanSupplierController::class, 'show']);
    });

    // ============================ Transaksi Penjualan ===========================
    Route::middleware('permission:penjualan.penjualan.view')->group(function (): void {
        Route::apiResource('penjualan', PenjualanController::class)->only(['index', 'show']);
        Route::get('penjualan/{penjualan}/opsi-barang', [PenjualanItemController::class, 'opsiBarang']);
        Route::get('penjualan/{penjualan}/items', [PenjualanItemController::class, 'index']);
        Route::get('penjualan/{penjualan}/items/{item}', [PenjualanItemController::class, 'show']);
    });
    Route::middleware('permission:penjualan.penjualan.create')->group(function (): void {
        Route::apiResource('penjualan', PenjualanController::class)->only(['store']);
        Route::post('penjualan/{penjualan}/items', [PenjualanItemController::class, 'store']);
    });
    Route::middleware('permission:penjualan.penjualan.update')->group(function (): void {
        Route::apiResource('penjualan', PenjualanController::class)->only(['update']);
        Route::put('penjualan/{penjualan}/items/{item}', [PenjualanItemController::class, 'update']);
    });
    Route::middleware('permission:penjualan.penjualan.delete')->group(function (): void {
        Route::apiResource('penjualan', PenjualanController::class)->only(['destroy']);
        Route::delete('penjualan/{penjualan}/items/{item}', [PenjualanItemController::class, 'destroy']);
    });

    Route::middleware('permission:penjualan.surat_jalan.view')->group(function (): void {
        Route::get('surat-jalan/opsi-perusahaan', [SuratJalanController::class, 'opsiPerusahaan']);
        Route::apiResource('surat-jalan', SuratJalanController::class)->only(['index', 'show']);
        Route::get('surat-jalan/{suratJalan}/opsi-barang', [SuratJalanItemController::class, 'opsiBarang']);
        Route::get('surat-jalan/{suratJalan}/items', [SuratJalanItemController::class, 'index']);
        Route::get('surat-jalan/{suratJalan}/items/{item}', [SuratJalanItemController::class, 'show']);
    });
    Route::middleware('permission:penjualan.surat_jalan.create')->group(function (): void {
        Route::apiResource('surat-jalan', SuratJalanController::class)->only(['store']);
        Route::post('surat-jalan/{suratJalan}/items', [SuratJalanItemController::class, 'store']);
    });
    Route::middleware('permission:penjualan.surat_jalan.update')->group(function (): void {
        Route::apiResource('surat-jalan', SuratJalanController::class)->only(['update']);
        Route::put('surat-jalan/{suratJalan}/items/{item}', [SuratJalanItemController::class, 'update']);
    });
    Route::middleware('permission:penjualan.surat_jalan.delete')->group(function (): void {
        Route::apiResource('surat-jalan', SuratJalanController::class)->only(['destroy']);
        Route::delete('surat-jalan/{suratJalan}/items/{item}', [SuratJalanItemController::class, 'destroy']);
    });

    Route::middleware('permission:penjualan.tanda_terima.view')->group(function (): void {
        Route::get('tanda-terima/opsi-perusahaan', [TandaTerimaController::class, 'opsiPerusahaan']);
        Route::apiResource('tanda-terima', TandaTerimaController::class)->only(['index', 'show']);
        Route::get('tanda-terima/{tandaTerima}/opsi-barang', [TandaTerimaItemController::class, 'opsiBarang']);
        Route::get('tanda-terima/{tandaTerima}/items', [TandaTerimaItemController::class, 'index']);
        Route::get('tanda-terima/{tandaTerima}/items/{item}', [TandaTerimaItemController::class, 'show']);
    });
    Route::middleware('permission:penjualan.tanda_terima.create')->group(function (): void {
        Route::apiResource('tanda-terima', TandaTerimaController::class)->only(['store']);
        Route::post('tanda-terima/{tandaTerima}/items', [TandaTerimaItemController::class, 'store']);
    });
    Route::middleware('permission:penjualan.tanda_terima.update')->group(function (): void {
        Route::apiResource('tanda-terima', TandaTerimaController::class)->only(['update']);
        Route::put('tanda-terima/{tandaTerima}/items/{item}', [TandaTerimaItemController::class, 'update']);
    });
    Route::middleware('permission:penjualan.tanda_terima.delete')->group(function (): void {
        Route::apiResource('tanda-terima', TandaTerimaController::class)->only(['destroy']);
        Route::delete('tanda-terima/{tandaTerima}/items/{item}', [TandaTerimaItemController::class, 'destroy']);
    });

    Route::middleware('permission:penjualan.invoice_penjualan.view')->group(function (): void {
        Route::get('invoice-penjualan/opsi-sppg', [InvoicePenjualanController::class, 'opsiSppgByTanggalKirim']);
        Route::get('invoice-penjualan/opsi-accounting', [InvoicePenjualanController::class, 'opsiAccounting']);
        Route::get('invoice-penjualan/opsi-bank-rekening', [InvoicePenjualanController::class, 'opsiBankRekening']);
        Route::get('invoice-penjualan/opsi-perusahaan', [InvoicePenjualanController::class, 'opsiPerusahaan']);
        Route::apiResource('invoice-penjualan', InvoicePenjualanController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:penjualan.invoice_penjualan.create')->group(function (): void {
        Route::apiResource('invoice-penjualan', InvoicePenjualanController::class)->only(['store']);
    });
    Route::middleware('permission:penjualan.invoice_penjualan.update')->group(function (): void {
        Route::apiResource('invoice-penjualan', InvoicePenjualanController::class)->only(['update']);
    });
    Route::middleware('permission:penjualan.invoice_penjualan.delete')->group(function (): void {
        Route::apiResource('invoice-penjualan', InvoicePenjualanController::class)->only(['destroy']);
    });

    // ============================= Warehouse System ===========================
    Route::middleware('permission:warehouse.inbound.view')->group(function (): void {
        Route::apiResource('inbound', WarehouseInboundController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:warehouse.inbound.create')->group(function (): void {
        Route::apiResource('inbound', WarehouseInboundController::class)->only(['store']);
    });
    Route::middleware('permission:warehouse.inbound.update')->group(function (): void {
        Route::apiResource('inbound', WarehouseInboundController::class)->only(['update']);
    });
    Route::middleware('permission:warehouse.inbound.delete')->group(function (): void {
        Route::apiResource('inbound', WarehouseInboundController::class)->only(['destroy']);
    });

    Route::middleware('permission:warehouse.stok_kering.view')->group(function (): void {
        Route::apiResource('stok-kering', WarehouseStokKeringController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:warehouse.stok_kering.create')->group(function (): void {
        Route::apiResource('stok-kering', WarehouseStokKeringController::class)->only(['store']);
    });
    Route::middleware('permission:warehouse.stok_kering.update')->group(function (): void {
        Route::apiResource('stok-kering', WarehouseStokKeringController::class)->only(['update']);
    });
    Route::middleware('permission:warehouse.stok_kering.delete')->group(function (): void {
        Route::apiResource('stok-kering', WarehouseStokKeringController::class)->only(['destroy']);
    });

    Route::middleware('permission:warehouse.stok_basah.view')->group(function (): void {
        Route::apiResource('stok-basah', WarehouseStokBasahController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:warehouse.stok_basah.create')->group(function (): void {
        Route::apiResource('stok-basah', WarehouseStokBasahController::class)->only(['store']);
    });
    Route::middleware('permission:warehouse.stok_basah.update')->group(function (): void {
        Route::apiResource('stok-basah', WarehouseStokBasahController::class)->only(['update']);
    });
    Route::middleware('permission:warehouse.stok_basah.delete')->group(function (): void {
        Route::apiResource('stok-basah', WarehouseStokBasahController::class)->only(['destroy']);
    });

    Route::middleware('permission:warehouse.retur.view')->group(function (): void {
        Route::apiResource('retur-rusak', WarehouseReturController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:warehouse.retur.create')->group(function (): void {
        Route::apiResource('retur-rusak', WarehouseReturController::class)->only(['store']);
    });
    Route::middleware('permission:warehouse.retur.update')->group(function (): void {
        Route::apiResource('retur-rusak', WarehouseReturController::class)->only(['update']);
    });
    Route::middleware('permission:warehouse.retur.delete')->group(function (): void {
        Route::apiResource('retur-rusak', WarehouseReturController::class)->only(['destroy']);
    });
});
// ============================ TEST API ===========================
Route::get('/test', function () {
    return response()->json([
        'message' => 'ok'
    ]);
});
