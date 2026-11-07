import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { fileURLToPath } from 'url';
import path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/index.js"],
            // Disable Herd/Valet TLS autodetection; use plain HTTP locally
            detectTls: false,
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    // Copy Font Awesome webfonts to build/webfonts
                    if (assetInfo.name && /\.(woff2?|eot|ttf|otf)$/.test(assetInfo.name)) {
                        return 'webfonts/[name][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
    resolve: {
        alias: {
            '@fortawesome': path.resolve(__dirname, 'node_modules/@fortawesome'),
        },
    },
});
