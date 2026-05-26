<?php

namespace App\Models\MasterData;

use Database\Factories\PerusahaanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class Perusahaan extends Model
{
    /** @use HasFactory<PerusahaanFactory> */
    use HasFactory;

    protected $table = 'perusahaan';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_perusahaan',
        'alamat',
        'nama_pic',
        'logo_path',
        'tema_invoice',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected $hidden = [
        'logo_path',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->attributes['logo_path']) || empty($this->attributes['id'])) {
            return null;
        }

        $request = Request::instance();
        $baseUrl = $request ? $request->getSchemeAndHttpHost() : rtrim((string) config('app.url'), '/');

        return rtrim($baseUrl, '/').'/api/perusahaan/'.$this->attributes['id'].'/logo';
    }

    protected static function newFactory(): PerusahaanFactory
    {
        return PerusahaanFactory::new();
    }
}
