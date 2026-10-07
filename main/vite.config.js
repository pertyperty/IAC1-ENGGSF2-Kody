import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                local('Instrument Sans', {
                    variants: [400, 500, 600, 700].map(weight => ({
                        src: `resources/fonts/instrument-sans-${weight}-normal.woff2`, weight,
                    })),
                    preload: [{ weight: 400 }],
                    optimizedFallbacks: false,
                }),
                local('Space Grotesk', {
                    variants: [500, 600, 700].map(weight => ({
                        src: `resources/fonts/space-grotesk-${weight}-normal.woff2`, weight,
                    })),
                    preload: [{ weight: 600 }],
                    optimizedFallbacks: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
