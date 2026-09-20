<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('publishes the complete installable PWA shell', function (): void {
    $manifestPath = public_path('manifest.webmanifest');
    $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);

    expect($manifest)->toMatchArray([
        'name' => 'EmprendimientoOS',
        'short_name' => 'EmprendimientoOS',
        'lang' => 'es-MX',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'portrait-primary',
        'background_color' => '#FFF8ED',
        'theme_color' => '#FFF8ED',
    ])->and($manifest['icons'])->toBe([
        [
            'src' => '/icons/app-icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => '/icons/app-icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => '/icons/maskable-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'maskable',
        ],
    ]);

    foreach ($manifest['icons'] as $icon) {
        $iconPath = public_path(ltrim($icon['src'], '/'));
        expect(File::exists($iconPath))->toBeTrue()
            ->and(File::size($iconPath))->toBeGreaterThan(0);
    }

    expect(File::exists(public_path('favicon.ico')))->toBeTrue()
        ->and(File::size(public_path('favicon.ico')))->toBeGreaterThan(0)
        ->and(File::exists(public_path('icons/app-icon-180.png')))->toBeTrue()
        ->and(File::size(public_path('icons/app-icon-180.png')))->toBeGreaterThan(0);
});

it('exposes bounded static PWA files and metadata', function (): void {
    $this->get('/')->assertOk()
        ->assertSee('rel="manifest"', false)
        ->assertSee('rel="icon"', false)
        ->assertSee('rel="apple-touch-icon"', false)
        ->assertSee('#FFF8ED', false);

    $serviceWorkerPath = public_path('sw.js');
    $serviceWorker = File::get($serviceWorkerPath);

    expect(File::exists($serviceWorkerPath))->toBeTrue()
        ->and(File::size($serviceWorkerPath))->toBeGreaterThan(0)
        ->and($serviceWorker)->toContain("'/manifest.webmanifest'")
        ->toContain("'/favicon.ico'")
        ->toContain("pathname.startsWith('/icons/')")
        ->toContain("pathname.startsWith('/build/')")
        ->toContain('self.skipWaiting()')
        ->toContain('self.clients.claim()')
        ->not->toContain("'/login'")
        ->not->toContain("'/ingredientes'")
        ->not->toContain("'/recetas/'")
        ->not->toContain("'/productos'")
        ->not->toContain("'/pedidos'")
        ->not->toContain("'/produccion'");
});
