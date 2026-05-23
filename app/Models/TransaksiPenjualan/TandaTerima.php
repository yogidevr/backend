<?php

namespace App\Models\TransaksiPenjualan;

use App\Models\MasterData\Armada;
use App\Models\MasterData\Karyawan;
use App\Models\MasterData\Perusahaan;
use App\Models\MasterData\Sppg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TandaTerima extends Model
{
    use HasFactory;

    protected $table = 'tanda_terima';

    protected $fillable = [
        'nomor_tanda_terima',
        'nomor_surat_jalan',
        'no_po',
        'tanggal',
        'sppg_id',
        'armada_id',
        'akuntan_id',
        'driver_id',
        'perusahaan_id',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
    ];

    protected $appends = [
        'nama_sppg',
        'armada',
        'no_pol',
        'nama_akuntan',
        'nama_driver',
        'nama_perusahaan',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TandaTerimaItem::class, 'tanda_terima_id');
    }

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class, 'sppg_id');
    }

    public function armadaRef(): BelongsTo
    {
        return $this->belongsTo(Armada::class, 'armada_id');
    }

    public function akuntan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'akuntan_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'driver_id');
    }

    public function suratJalan(): BelongsTo
    {
        return $this->belongsTo(SuratJalan::class, 'nomor_surat_jalan', 'nomor_surat_jalan');
    }

    public function perusahaanRef(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id');
    }

    public function getNamaSppgAttribute(): ?string
    {
        return $this->sppg?->nama_sppg;
    }

    public function getArmadaAttribute(): ?string
    {
        return $this->armadaRef?->nama_unit;
    }

    public function getNoPolAttribute(): ?string
    {
        return $this->armadaRef?->no_pol;
    }

    public function getNamaAkuntanAttribute(): ?string
    {
        return $this->akuntan?->nama;
    }

    public function getNamaDriverAttribute(): ?string
    {
        return $this->driver?->nama;
    }

    public function getNamaPerusahaanAttribute(): ?string
    {
        return $this->perusahaanRef?->nama_perusahaan;
    }
}
