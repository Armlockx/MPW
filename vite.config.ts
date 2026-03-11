import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { visualizer } from 'rollup-plugin-visualizer';
import { defineConfig } from 'vite';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';
import { readdirSync, statSync } from 'fs';

const __dirname = dirname(fileURLToPath(import.meta.url));

function getPageEntries(dir: string, base = 'resources/js/pages'): string[] {
    const results: string[] = [];
    for (const file of readdirSync(dir)) {
        const full = resolve(dir, file);
        if (statSync(full).isDirectory()) {
            results.push(...getPageEntries(full, `${base}/${file}`));
        } else if (file.endsWith('.vue')) {
            results.push(`${base}/${file}`);
        }
    }
    return results;
}

const pageEntries = getPageEntries(resolve(__dirname, 'resources/js/pages'));

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts', ...pageEntries],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
        ...(process.env.ANALYZE === 'true'
            ? [
                  visualizer({
                      filename: 'build-stats.html',
                      gzipSize: true,
                      brotliSize: true,
                  }),
              ]
            : []),
    ],
});
