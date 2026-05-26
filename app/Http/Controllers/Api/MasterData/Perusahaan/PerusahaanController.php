<?php

namespace App\Http\Controllers\Api\MasterData\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Perusahaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PerusahaanController extends Controller
{
    private const DEFAULT_TEMA_INVOICE = 'theme_01';
    private const TEMA_INVOICE_OPTIONS = [
        'theme_01', 'theme_02', 'theme_03', 'theme_04', 'theme_05',
        'theme_06', 'theme_07', 'theme_08', 'theme_09', 'theme_10',
        'theme_11', 'theme_12', 'theme_13', 'theme_14', 'theme_15',
    ];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_perusahaan', 'alamat', 'nama_pic', 'tema_invoice'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_perusahaan';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $perusahaan = Perusahaan::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_perusahaan', 'like', '%'.$keyword.'%')
                        ->orWhere('alamat', 'like', '%'.$keyword.'%')
                        ->orWhere('nama_pic', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data perusahaan berhasil diambil.',
            'data' => $perusahaan->items(),
            'meta' => [
                'current_page' => $perusahaan->currentPage(),
                'last_page' => $perusahaan->lastPage(),
                'per_page' => $perusahaan->perPage(),
                'total' => $perusahaan->total(),
                'from' => $perusahaan->firstItem(),
                'to' => $perusahaan->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);
        $payload['logo_path'] = $this->storeLogo($request);

        $perusahaan = Perusahaan::query()->create($payload);

        return response()->json([
            'message' => 'Perusahaan berhasil ditambahkan.',
            'data' => $perusahaan,
        ], 201);
    }

    public function show(Perusahaan $perusahaan): JsonResponse
    {
        return response()->json([
            'message' => 'Detail perusahaan berhasil diambil.',
            'data' => $perusahaan,
        ]);
    }

    public function update(Request $request, Perusahaan $perusahaan): JsonResponse
    {
        $payload = $this->validatePayload($request, $perusahaan);
        $newLogoPath = $this->storeLogo($request);

        if ($newLogoPath) {
            if ($perusahaan->getRawOriginal('logo_path')) {
                Storage::disk('public')->delete($perusahaan->getRawOriginal('logo_path'));
            }

            $payload['logo_path'] = $newLogoPath;
        }

        $perusahaan->update($payload);

        return response()->json([
            'message' => 'Perusahaan berhasil diperbarui.',
            'data' => $perusahaan->fresh(),
        ]);
    }

    public function destroy(Perusahaan $perusahaan): JsonResponse
    {
        if ($perusahaan->getRawOriginal('logo_path')) {
            Storage::disk('public')->delete($perusahaan->getRawOriginal('logo_path'));
        }

        $perusahaan->delete();

        return response()->json([
            'message' => 'Perusahaan berhasil dihapus.',
        ]);
    }

    public function logo(Perusahaan $perusahaan): HttpResponse|JsonResponse
    {
        $path = $perusahaan->getRawOriginal('logo_path');
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return response()->json([
                'message' => 'Logo tidak ditemukan.',
            ], 404);
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';
        $stream = Storage::disk('public')->readStream($path);

        if ($stream === false) {
            return response()->json([
                'message' => 'Gagal membaca logo.',
            ], 500);
        }

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * @return array{nama_perusahaan: string, alamat: string, nama_pic: string, tema_invoice: string}
     */
    private function validatePayload(Request $request, ?Perusahaan $ignorePerusahaan = null): array
    {
        $payload = $request->validate([
            'nama_perusahaan' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'nama_pic' => ['required', 'string', 'max:100'],
            'tema_invoice' => ['nullable', 'string', Rule::in(self::TEMA_INVOICE_OPTIONS)],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);
        unset($payload['logo']);
        $payload['tema_invoice'] = $payload['tema_invoice'] ?? self::DEFAULT_TEMA_INVOICE;

        $normalizedNamaPerusahaan = mb_strtolower(trim($payload['nama_perusahaan']));
        $normalizedAlamat = mb_strtolower(trim($payload['alamat']));
        $normalizedNamaPic = mb_strtolower(trim($payload['nama_pic']));

        $duplicateExists = Perusahaan::query()
            ->when($ignorePerusahaan !== null, fn ($query) => $query->whereKeyNot($ignorePerusahaan->id))
            ->whereRaw('LOWER(TRIM(nama_perusahaan)) = ?', [$normalizedNamaPerusahaan])
            ->whereRaw('LOWER(TRIM(alamat)) = ?', [$normalizedAlamat])
            ->whereRaw('LOWER(TRIM(nama_pic)) = ?', [$normalizedNamaPic])
            ->exists();

        if ($duplicateExists) {
            abort(response()->json([
                'message' => 'Perusahaan dengan nama perusahaan, alamat, dan PIC yang sama sudah ada.',
                'errors' => [
                    'nama_perusahaan' => ['Perusahaan dengan nama perusahaan, alamat, dan PIC yang sama sudah ada.'],
                ],
            ], 422));
        }

        return $payload;
    }

    private function storeLogo(Request $request): ?string
    {
        if (! $request->hasFile('logo')) {
            return null;
        }

        $file = $request->file('logo');
        $extension = $file->getClientOriginalExtension();
        $filename = now()->format('YmdHis').'_'.Str::random(10).'.'.$extension;

        return $file->storeAs('perusahaan-logo', $filename, 'public');
    }
}
