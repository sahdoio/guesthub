import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: Number(process.env.FORWARD_VITE_PORT) || 5173,
        origin: `http://${process.env.VITE_HMR_HOST || 'localhost'}:${Number(process.env.FORWARD_VITE_PORT) || 5173}`,
        cors: {
            origin: `http://${process.env.VITE_HMR_HOST || 'localhost'}:${Number(process.env.FORWARD_NGINX_PORT) || 8080}`,
        },
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
            protocol: 'ws',
        },
        watch: {
            usePolling: true,
            interval: 1000,
            ignored: ['**/storage/framework/views/**'],
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});
