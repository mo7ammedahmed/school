import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.tsx',
            ],
            // The Server-Side Rendering entry point. `vite build --ssr` compiles
            // it to bootstrap/ssr/ssr.js, which is what `inertia:start-ssr` runs
            // and what Inertia looks for before it tries the SSR server at all.
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),

        tailwindcss(),

        react(),
    ],

    resolve: {
        alias: {
            '@': fileURLToPath(
                new URL('./resources/js', import.meta.url)
            ),
        },
    },

    server: {
        host: 'localhost',
        hmr: {
            host: 'localhost',
        },
        watch: {
            ignored: [
                '**/storage/framework/views/**',
            ],
        },
    },
});