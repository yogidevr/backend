<?php

namespace App\Support;

class PermissionCatalog
{
    /**
     * @return list<array{code:string,name:string,group_name:string}>
     */
    public static function all(): array
    {
        return [
            ['code' => 'dashboard.view', 'name' => 'Lihat Dashboard', 'group_name' => 'Dashboard'],

            ['code' => 'master.wilayah.view', 'name' => 'Lihat Wilayah & Lokasi', 'group_name' => 'Master'],
            ['code' => 'master.wilayah.create', 'name' => 'Tambah Wilayah & Lokasi', 'group_name' => 'Master'],
            ['code' => 'master.wilayah.update', 'name' => 'Edit Wilayah & Lokasi', 'group_name' => 'Master'],
            ['code' => 'master.wilayah.delete', 'name' => 'Hapus Wilayah & Lokasi', 'group_name' => 'Master'],
            ['code' => 'master.supplier.view', 'name' => 'Lihat Supplier', 'group_name' => 'Master'],
            ['code' => 'master.supplier.create', 'name' => 'Tambah Supplier', 'group_name' => 'Master'],
            ['code' => 'master.supplier.update', 'name' => 'Edit Supplier', 'group_name' => 'Master'],
            ['code' => 'master.supplier.delete', 'name' => 'Hapus Supplier', 'group_name' => 'Master'],
            ['code' => 'master.mitra.view', 'name' => 'Lihat Mitra', 'group_name' => 'Master'],
            ['code' => 'master.mitra.create', 'name' => 'Tambah Mitra', 'group_name' => 'Master'],
            ['code' => 'master.mitra.update', 'name' => 'Edit Mitra', 'group_name' => 'Master'],
            ['code' => 'master.mitra.delete', 'name' => 'Hapus Mitra', 'group_name' => 'Master'],
            ['code' => 'master.sppg.view', 'name' => 'Lihat SPPG', 'group_name' => 'Master'],
            ['code' => 'master.sppg.create', 'name' => 'Tambah SPPG', 'group_name' => 'Master'],
            ['code' => 'master.sppg.update', 'name' => 'Edit SPPG', 'group_name' => 'Master'],
            ['code' => 'master.sppg.delete', 'name' => 'Hapus SPPG', 'group_name' => 'Master'],
            ['code' => 'master.produk.view', 'name' => 'Lihat Produk & Barang', 'group_name' => 'Master'],
            ['code' => 'master.produk.create', 'name' => 'Tambah Produk & Barang', 'group_name' => 'Master'],
            ['code' => 'master.produk.update', 'name' => 'Edit Produk & Barang', 'group_name' => 'Master'],
            ['code' => 'master.produk.delete', 'name' => 'Hapus Produk & Barang', 'group_name' => 'Master'],
            ['code' => 'master.gudang.view', 'name' => 'Lihat Gudang', 'group_name' => 'Master'],
            ['code' => 'master.gudang.create', 'name' => 'Tambah Gudang', 'group_name' => 'Master'],
            ['code' => 'master.gudang.update', 'name' => 'Edit Gudang', 'group_name' => 'Master'],
            ['code' => 'master.gudang.delete', 'name' => 'Hapus Gudang', 'group_name' => 'Master'],
            ['code' => 'master.armada.view', 'name' => 'Lihat Armada', 'group_name' => 'Master'],
            ['code' => 'master.armada.create', 'name' => 'Tambah Armada', 'group_name' => 'Master'],
            ['code' => 'master.armada.update', 'name' => 'Edit Armada', 'group_name' => 'Master'],
            ['code' => 'master.armada.delete', 'name' => 'Hapus Armada', 'group_name' => 'Master'],
            ['code' => 'master.karyawan.view', 'name' => 'Lihat Karyawan', 'group_name' => 'Master'],
            ['code' => 'master.karyawan.create', 'name' => 'Tambah Karyawan', 'group_name' => 'Master'],
            ['code' => 'master.karyawan.update', 'name' => 'Edit Karyawan', 'group_name' => 'Master'],
            ['code' => 'master.karyawan.delete', 'name' => 'Hapus Karyawan', 'group_name' => 'Master'],
            ['code' => 'master.bank_rekening.view', 'name' => 'Lihat Bank & Rekening', 'group_name' => 'Master'],
            ['code' => 'master.bank_rekening.create', 'name' => 'Tambah Bank & Rekening', 'group_name' => 'Master'],
            ['code' => 'master.bank_rekening.update', 'name' => 'Edit Bank & Rekening', 'group_name' => 'Master'],
            ['code' => 'master.bank_rekening.delete', 'name' => 'Hapus Bank & Rekening', 'group_name' => 'Master'],
            ['code' => 'master.perusahaan.view', 'name' => 'Lihat Perusahaan', 'group_name' => 'Master'],
            ['code' => 'master.perusahaan.create', 'name' => 'Tambah Perusahaan', 'group_name' => 'Master'],
            ['code' => 'master.perusahaan.update', 'name' => 'Edit Perusahaan', 'group_name' => 'Master'],
            ['code' => 'master.perusahaan.delete', 'name' => 'Hapus Perusahaan', 'group_name' => 'Master'],
            ['code' => 'master.kategori.view', 'name' => 'Lihat Kategori & Satuan', 'group_name' => 'Master'],
            ['code' => 'master.kategori.create', 'name' => 'Tambah Kategori & Satuan', 'group_name' => 'Master'],
            ['code' => 'master.kategori.update', 'name' => 'Edit Kategori & Satuan', 'group_name' => 'Master'],
            ['code' => 'master.kategori.delete', 'name' => 'Hapus Kategori & Satuan', 'group_name' => 'Master'],
            ['code' => 'master.role.view', 'name' => 'Lihat Role', 'group_name' => 'Master'],
            ['code' => 'master.role.create', 'name' => 'Tambah Role', 'group_name' => 'Master'],
            ['code' => 'master.role.update', 'name' => 'Edit Role', 'group_name' => 'Master'],
            ['code' => 'master.role.delete', 'name' => 'Hapus Role', 'group_name' => 'Master'],

            ['code' => 'warehouse.inbound.view', 'name' => 'Lihat Inbound', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.inbound.create', 'name' => 'Tambah Inbound', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.inbound.update', 'name' => 'Edit Inbound', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.inbound.delete', 'name' => 'Hapus Inbound', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.stok_kering.view', 'name' => 'Lihat Cek Stok (Kering)', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.stok_kering.create', 'name' => 'Tambah Cek Stok (Kering)', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.stok_kering.update', 'name' => 'Edit Cek Stok (Kering)', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.stok_kering.delete', 'name' => 'Hapus Cek Stok (Kering)', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.stok_basah.view', 'name' => 'Lihat Cek Stok (Basah)', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.stok_basah.create', 'name' => 'Tambah Cek Stok (Basah)', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.stok_basah.update', 'name' => 'Edit Cek Stok (Basah)', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.stok_basah.delete', 'name' => 'Hapus Cek Stok (Basah)', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.retur.view', 'name' => 'Lihat Retur/Rusak', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.retur.create', 'name' => 'Tambah Retur/Rusak', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.retur.update', 'name' => 'Edit Retur/Rusak', 'group_name' => 'Warehouse System'],
            ['code' => 'warehouse.retur.delete', 'name' => 'Hapus Retur/Rusak', 'group_name' => 'Warehouse System'],

            ['code' => 'pembelian.order_penawaran.view', 'name' => 'Lihat List Order & Penawaran', 'group_name' => 'Transaksi Pembelian'],
            ['code' => 'pembelian.order_penawaran.create', 'name' => 'Tambah List Order & Penawaran', 'group_name' => 'Transaksi Pembelian'],
            ['code' => 'pembelian.order_penawaran.update', 'name' => 'Edit List Order & Penawaran', 'group_name' => 'Transaksi Pembelian'],
            ['code' => 'pembelian.order_penawaran.delete', 'name' => 'Hapus List Order & Penawaran', 'group_name' => 'Transaksi Pembelian'],
            ['code' => 'pembelian.daftar_pembelanjaan.view', 'name' => 'Lihat Daftar Pembelanjaan', 'group_name' => 'Transaksi Pembelian'],
            ['code' => 'pembelian.daftar_pembelanjaan.create', 'name' => 'Tambah Daftar Pembelanjaan', 'group_name' => 'Transaksi Pembelian'],
            ['code' => 'pembelian.daftar_pembelanjaan.update', 'name' => 'Edit Daftar Pembelanjaan', 'group_name' => 'Transaksi Pembelian'],
            ['code' => 'pembelian.daftar_pembelanjaan.delete', 'name' => 'Hapus Daftar Pembelanjaan', 'group_name' => 'Transaksi Pembelian'],
            ['code' => 'pembelian.daftar_pembelanjaan_supplier.view', 'name' => 'Lihat Daftar Pembelanjaan Supplier', 'group_name' => 'Transaksi Pembelian'],

            ['code' => 'penjualan.penjualan.view', 'name' => 'Lihat Penjualan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.penjualan.create', 'name' => 'Tambah Penjualan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.penjualan.update', 'name' => 'Edit Penjualan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.penjualan.delete', 'name' => 'Hapus Penjualan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.surat_jalan.view', 'name' => 'Lihat Surat Jalan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.surat_jalan.create', 'name' => 'Tambah Surat Jalan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.surat_jalan.update', 'name' => 'Edit Surat Jalan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.surat_jalan.delete', 'name' => 'Hapus Surat Jalan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.tanda_terima.view', 'name' => 'Lihat Tanda Terima', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.tanda_terima.create', 'name' => 'Tambah Tanda Terima', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.tanda_terima.update', 'name' => 'Edit Tanda Terima', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.tanda_terima.delete', 'name' => 'Hapus Tanda Terima', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.invoice_penjualan.view', 'name' => 'Lihat Invoice Penjualan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.invoice_penjualan.create', 'name' => 'Tambah Invoice Penjualan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.invoice_penjualan.update', 'name' => 'Edit Invoice Penjualan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.invoice_penjualan.delete', 'name' => 'Hapus Invoice Penjualan', 'group_name' => 'Transaksi Penjualan'],

            ['code' => 'keuangan.pemasukan.view', 'name' => 'Lihat Pemasukan', 'group_name' => 'Keuangan & Akuntansi'],
            ['code' => 'keuangan.pemasukan.create', 'name' => 'Tambah Pemasukan', 'group_name' => 'Keuangan & Akuntansi'],
            ['code' => 'keuangan.pemasukan.update', 'name' => 'Edit Pemasukan', 'group_name' => 'Keuangan & Akuntansi'],
            ['code' => 'keuangan.pemasukan.delete', 'name' => 'Hapus Pemasukan', 'group_name' => 'Keuangan & Akuntansi'],
            ['code' => 'keuangan.pengeluaran.view', 'name' => 'Lihat Pengeluaran', 'group_name' => 'Keuangan & Akuntansi'],
            ['code' => 'keuangan.pengeluaran.create', 'name' => 'Tambah Pengeluaran', 'group_name' => 'Keuangan & Akuntansi'],
            ['code' => 'keuangan.pengeluaran.update', 'name' => 'Edit Pengeluaran', 'group_name' => 'Keuangan & Akuntansi'],
            ['code' => 'keuangan.pengeluaran.delete', 'name' => 'Hapus Pengeluaran', 'group_name' => 'Keuangan & Akuntansi'],

            ['code' => 'laporan.stok_barang.view', 'name' => 'Lihat Laporan Stok Barang', 'group_name' => 'Laporan & Analisa'],
            ['code' => 'laporan.laba_rugi.view', 'name' => 'Lihat Laba Rugi Transaksional', 'group_name' => 'Laporan & Analisa'],
            ['code' => 'laporan.penjualan_sppg.view', 'name' => 'Lihat Laporan Penjualan per SPPG', 'group_name' => 'Laporan & Analisa'],

            ['code' => 'users.view', 'name' => 'Lihat Pengguna', 'group_name' => 'Pengguna'],
            ['code' => 'users.create', 'name' => 'Tambah Pengguna', 'group_name' => 'Pengguna'],
            ['code' => 'users.update', 'name' => 'Edit Pengguna', 'group_name' => 'Pengguna'],
            ['code' => 'users.delete', 'name' => 'Hapus Pengguna', 'group_name' => 'Pengguna'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaultAdminCodes(): array
    {
        return array_values(array_filter(
            array_map(
                static fn (array $permission): string => $permission['code'],
                self::all()
            ),
            static fn (string $code): bool => !str_starts_with($code, 'users.')
        ));
    }

    /**
     * @return list<string>
     */
    public static function legacyCodes(): array
    {
        return [
            'master.view',
            'master.manage',
            'warehouse.view',
            'warehouse.manage',
            'pembelian.view',
            'pembelian.manage',
            'penjualan.view',
            'penjualan.manage',
            'keuangan.view',
            'keuangan.manage',
            'laporan.view',
            'users.manage',
        ];
    }

    /**
     * Backward compatibility map for legacy permission codes.
     *
     * @return list<string>
     */
    public static function legacyFallback(string $permissionCode): array
    {
        if (str_starts_with($permissionCode, 'master.')) {
            if (str_ends_with($permissionCode, '.view')) {
                return ['master.view'];
            }
            if (str_ends_with($permissionCode, '.create') || str_ends_with($permissionCode, '.update') || str_ends_with($permissionCode, '.delete')) {
                return ['master.manage'];
            }
        }

        if (str_starts_with($permissionCode, 'warehouse.')) {
            if (str_ends_with($permissionCode, '.view')) {
                return ['warehouse.view'];
            }
            if (str_ends_with($permissionCode, '.create') || str_ends_with($permissionCode, '.update') || str_ends_with($permissionCode, '.delete')) {
                return ['warehouse.manage'];
            }
        }

        if (str_starts_with($permissionCode, 'pembelian.')) {
            if (str_ends_with($permissionCode, '.view')) {
                return ['pembelian.view'];
            }
            if (str_ends_with($permissionCode, '.create') || str_ends_with($permissionCode, '.update') || str_ends_with($permissionCode, '.delete')) {
                return ['pembelian.manage'];
            }
        }

        if (str_starts_with($permissionCode, 'penjualan.')) {
            if (str_ends_with($permissionCode, '.view')) {
                return ['penjualan.view'];
            }
            if (str_ends_with($permissionCode, '.create') || str_ends_with($permissionCode, '.update') || str_ends_with($permissionCode, '.delete')) {
                return ['penjualan.manage'];
            }
        }

        if (str_starts_with($permissionCode, 'keuangan.')) {
            if (str_ends_with($permissionCode, '.view')) {
                return ['keuangan.view'];
            }
            if (str_ends_with($permissionCode, '.create') || str_ends_with($permissionCode, '.update') || str_ends_with($permissionCode, '.delete')) {
                return ['keuangan.manage'];
            }
        }

        if (str_starts_with($permissionCode, 'laporan.')) {
            return ['laporan.view'];
        }

        if ($permissionCode === 'users.create' || $permissionCode === 'users.update' || $permissionCode === 'users.delete') {
            return ['users.manage'];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    public static function expandLegacyCode(string $legacyCode): array
    {
        $allCodes = array_map(static fn (array $permission): string => $permission['code'], self::all());

        $filterByPrefixAndSuffix = static function (string $prefix, array $suffixes) use ($allCodes): array {
            return array_values(array_filter(
                $allCodes,
                static function (string $code) use ($prefix, $suffixes): bool {
                    if (!str_starts_with($code, $prefix)) {
                        return false;
                    }

                    foreach ($suffixes as $suffix) {
                        if (str_ends_with($code, $suffix)) {
                            return true;
                        }
                    }

                    return false;
                }
            ));
        };

        return match ($legacyCode) {
            'master.view' => $filterByPrefixAndSuffix('master.', ['.view']),
            'master.manage' => $filterByPrefixAndSuffix('master.', ['.create', '.update', '.delete']),
            'warehouse.view' => $filterByPrefixAndSuffix('warehouse.', ['.view']),
            'warehouse.manage' => $filterByPrefixAndSuffix('warehouse.', ['.create', '.update', '.delete']),
            'pembelian.view' => $filterByPrefixAndSuffix('pembelian.', ['.view']),
            'pembelian.manage' => $filterByPrefixAndSuffix('pembelian.', ['.create', '.update', '.delete']),
            'penjualan.view' => $filterByPrefixAndSuffix('penjualan.', ['.view']),
            'penjualan.manage' => $filterByPrefixAndSuffix('penjualan.', ['.create', '.update', '.delete']),
            'keuangan.view' => $filterByPrefixAndSuffix('keuangan.', ['.view']),
            'keuangan.manage' => $filterByPrefixAndSuffix('keuangan.', ['.create', '.update', '.delete']),
            'laporan.view' => $filterByPrefixAndSuffix('laporan.', ['.view']),
            'users.manage' => ['users.create', 'users.update', 'users.delete'],
            default => [],
        };
    }
}
