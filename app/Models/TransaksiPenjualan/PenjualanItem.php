<?php

namespace App\Models\TransaksiPenjualan;

use App\Models\MasterData\Gudang;
use App\Models\MasterData\Perusahaan;
use App\Models\MasterData\Produk;
use App\Models\TransaksiPembelian\OrderPenawaranItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenjualanItem extends Model
{
    use HasFactory;

    protected $table = 'penjualan_items';

    protected $fillable = [
        'penjualan_id',
        'order_penawaran_item_id',
        'produk_id',
        'gudang_id',
        'perusahaan_id',
        'nama_barang',
        'qty',
        'satuan',
        'harga_satuan',
        'total_harga',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    public function orderPenawaranItem(): BelongsTo
    {
        return $this->belongsTo(OrderPenawaranItem::class, 'order_penawaran_item_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id');
    }
}
