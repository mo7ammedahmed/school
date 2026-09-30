import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import { fileURLToPath, URL } from 'node:url';

/**
 * The pages under `resources/js` had no test runner at all, so a UI defect like a
 * form posting the same field name twice could only be found by a human clicking.
 *
 * Kept separate from `vite.config.js` so the production build keeps its Laravel
 * plugin (which expects to run inside the app) and the tests stay free of it.
 */
export default defineConfig({
    plugins: [react()],

    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },

    test: {
        environment: 'happy-dom',
        include: ['resources/js/**/*.test.{ts,tsx}'],
        setupFiles: ['resources/js/test/setup.ts'],
    },
});
