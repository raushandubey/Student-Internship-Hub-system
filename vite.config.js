import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/mobile-components.css',
                'resources/js/app.js',
                'resources/js/form-wizard.js',
                'public/css/chatbot.css',
                'public/js/chatbot.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        minify: 'esbuild', // Use esbuild for minification (faster and no extra dependencies)
        rollupOptions: {
            output: {
                manualChunks: undefined,
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name === 'chatbot.css') {
                        return 'css/chatbot.min.css';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
                entryFileNames: (chunkInfo) => {
                    if (chunkInfo.name === 'chatbot') {
                        return 'js/chatbot.min.js';
                    }
                    return 'assets/[name]-[hash].js';
                }
            }
        }
    }
});
