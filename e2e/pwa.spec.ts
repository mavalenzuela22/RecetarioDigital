import { expect, test } from '@playwright/test';
import { login } from './auth';

test('provides an installable shell without caching business data', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/login');

    const manifestHref = await page.locator('link[rel="manifest"]').getAttribute('href');
    const iconLinks = await page.locator('link[rel="icon"], link[rel="apple-touch-icon"]').evaluateAll((links) =>
        links.map((link) => ({
            rel: link.getAttribute('rel'),
            path: new URL(link.getAttribute('href') ?? '', window.location.href).pathname,
        })),
    );
    const manifest = await page.evaluate(async (href) => {
        const response = await fetch(new URL(href ?? '', window.location.href));
        return response.json();
    }, manifestHref);

    expect(manifestHref).toBeTruthy();
    expect(manifest).toMatchObject({
        name: 'EmprendimientoOS',
        short_name: 'EmprendimientoOS',
        lang: 'es-MX',
        start_url: '/',
        scope: '/',
        display: 'standalone',
        orientation: 'portrait-primary',
        background_color: '#FFF8ED',
        theme_color: '#FFF8ED',
    });
    expect(manifest.icons).toEqual([
        { src: '/icons/app-icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
        { src: '/icons/app-icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
        { src: '/icons/maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
    ]);
    expect(iconLinks).toEqual([
        { rel: 'icon', path: '/favicon.ico' },
        { rel: 'apple-touch-icon', path: '/icons/app-icon-180.png' },
    ]);

    const registration = await page.evaluate(async () => {
        const value = await navigator.serviceWorker.ready;
        const active = value.active;

        if (!active) {
            throw new Error('Service worker registration has no active worker.');
        }

        if (active.state === 'redundant') {
            throw new Error('Service worker became redundant before activation.');
        }

        if (active.state !== 'activated') {
            await new Promise<void>((resolve, reject) => {
                const timeout = window.setTimeout(() => {
                    cleanup();
                    reject(new Error(`Timed out waiting for service worker activation; final state: ${active.state}.`));
                }, 10_000);

                const cleanup = () => {
                    window.clearTimeout(timeout);
                    active.removeEventListener('statechange', onStateChange);
                };

                const onStateChange = () => {
                    if (active.state === 'activated') {
                        cleanup();
                        resolve();
                    } else if (active.state === 'redundant') {
                        cleanup();
                        reject(new Error('Service worker became redundant before activation.'));
                    }
                };

                active.addEventListener('statechange', onStateChange);
                onStateChange();
            });
        }

        return {
            activeScript: active.scriptURL,
            scope: value.scope,
            state: active.state,
        };
    });
    expect(registration.activeScript).toMatch(/\/sw\.js$/);
    expect(new URL(registration.scope).pathname).toBe('/');
    expect(registration.state).toBe('activated');

    await login(page, '/recetas');
    for (const path of ['/ingredientes', '/recetas', '/productos', '/pedidos', '/produccion']) {
        const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
        expect(response?.status()).toBe(200);
        await expect(page).not.toHaveURL(/\/login$/);
    }

    const cachedUrls = await page.evaluate(async () => {
        const cacheNames = await caches.keys();
        const entries = await Promise.all(
            cacheNames.map(async (cacheName) => {
                const cache = await caches.open(cacheName);
                return (await cache.keys()).map((request) => request.url);
            }),
        );

        return entries.flat();
    });
    const approvedStaticPath = /^https?:\/\/[^/]+\/(manifest\.webmanifest|favicon\.ico|icons\/|build\/)/;
    const forbiddenBusinessPath = /\/(login|ingredientes|recetas\/|productos|pedidos|produccion)(?:[/?]|$)/;

    expect(cachedUrls.every((url) => approvedStaticPath.test(url))).toBe(true);
    expect(cachedUrls.some((url) => forbiddenBusinessPath.test(url))).toBe(false);
});
