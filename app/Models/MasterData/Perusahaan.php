<?php

namespace App\Models\MasterData;

use Database\Factories\PerusahaanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

        $baseUrl = $this->publicBaseUrl();

        return rtrim($baseUrl, '/').'/api/perusahaan/'.$this->attributes['id'].'/logo';
    }

    private function publicBaseUrl(): string
    {
        $appUrl = rtrim((string) config('app.url', 'http://localhost'), '/');
        $request = request();

        $forwardedProto = $request?->header('x-forwarded-proto');
        $forwardedHost = $request?->header('x-forwarded-host');

        if ($forwardedHost) {
            $scheme = $forwardedProto ?: parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';

            return $scheme.'://'.$forwardedHost;
        }

        return $appUrl;
    }

    protected static function newFactory(): PerusahaanFactory
    {
        return PerusahaanFactory::new();
    }
}
