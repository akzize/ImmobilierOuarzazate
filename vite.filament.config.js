import { defineConfig } from 'vite';

/**
 * Builds the Filament Alpine components that bundle npm libraries (the Leaflet map) into resources/js/dist.
 * Filament publishes those files to public/ with `php artisan filament:assets` (run by `filament:upgrade` after composer installs),
 * so the admin panel does not depend on the Vite dev server or manifest.
 */
export default defineConfig({
    publicDir: false,
    build: {
        outDir: 'resources/js/dist',
        emptyOutDir: false,
        // Library builds keep whitespace in ES output by default; these files are served to browsers as-is.
        rolldownOptions: {
            output: { minify: true },
        },
        lib: {
            entry: { 'map-picker': 'resources/js/filament/map-picker.js' },
            formats: ['es'],
            fileName: (format, entryName) => `${entryName}.js`,
            cssFileName: 'map-picker',
        },
    },
});
