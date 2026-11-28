<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Manifest PWA: nama dan ikon ikut Pengaturan Aplikasi, supaya bisa dipasang ke layar utama iOS/Android.
 */
class PwaController extends Controller
{
    public function manifest(Request $request): JsonResponse
    {
        $setting = AppSetting::first();
        $name = trim((string) ($setting?->app_name ?: 'Kasira'));
        $icons = self::icons($setting);
        $start = (string) $request->query('start', '/admin');
        $start = '/'.ltrim($start, '/');

        return response()->json([
            'name' => $name,
            'short_name' => mb_substr($name, 0, 12),
            'description' => 'Kasir, meja, dapur, dan laporan '.$name,
            'start_url' => $start,
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#ffffff',
            'theme_color' => '#ef4444',
            'lang' => 'id',
            'icons' => [
                ['src' => $icons[192], 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => $icons[512], 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=300']);
    }

    /**
     * Ikon dari logo aplikasi yang diunggah (dibuat sekali per logo), kalau tidak ada pakai ikon bawaan.
     *
     * @return array{180: string, 192: string, 512: string}
     */
    public static function icons(?AppSetting $setting): array
    {
        $default = [180 => asset('pwa/apple-touch-icon-180.png'), 192 => asset('pwa/icon-192.png'), 512 => asset('pwa/icon-512.png')];
        $logo = $setting?->app_logo;

        if (is_string($logo) && is_array($decoded = json_decode($logo, true)) && $decoded !== []) {
            $logo = array_key_first($decoded);
        }

        if (! is_string($logo) || $logo === '' || ! Storage::disk('public')->exists($logo) || ! function_exists('imagecreatefromstring')) {
            return $default;
        }

        $key = 'pwa/'.md5($logo.Storage::disk('public')->lastModified($logo));
        $paths = [180 => "{$key}-180.png", 192 => "{$key}-192.png", 512 => "{$key}-512.png"];

        if (! Storage::disk('public')->exists($paths[512])) {
            $source = @imagecreatefromstring((string) Storage::disk('public')->get($logo));

            // Logo lebar (wordmark) tidak terbaca sebagai ikon kecil; pakai ikon bawaan (tanda K)
            if ($source === false || imagesx($source) > imagesy($source) * 1.25 || imagesy($source) > imagesx($source) * 1.25) {
                return $default;
            }

            foreach ($paths as $size => $path) {
                $canvas = imagecreatetruecolor($size, $size);
                imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
                $inner = (int) round($size * 0.72);
                $scale = min($inner / imagesx($source), $inner / imagesy($source));
                $w = (int) round(imagesx($source) * $scale);
                $h = (int) round(imagesy($source) * $scale);
                imagecopyresampled($canvas, $source, (int) (($size - $w) / 2), (int) (($size - $h) / 2), 0, 0, $w, $h, imagesx($source), imagesy($source));
                ob_start();
                imagepng($canvas, null, 6);
                Storage::disk('public')->put($path, (string) ob_get_clean());
            }
        }

        return array_map(fn (string $path): string => Storage::disk('public')->url($path), $paths);
    }
}
