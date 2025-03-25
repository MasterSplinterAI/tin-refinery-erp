import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appUrl = env.APP_URL || 'http://localhost:8001';
    
    // Extract host from APP_URL (for ngrok)
    let hmrHost = 'localhost';
    try {
        const url = new URL(appUrl);
        hmrHost = url.host;
    } catch (e) {
        // Invalid URL, use default
    }
    
    // Determine if we're using ngrok
    const isNgrok = hmrHost.includes('ngrok');
    
    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
                // Generate a proper manifest to help with asset loading
                buildDirectory: 'build',
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        server: {
            host: '0.0.0.0',
            port: 5176,
            strictPort: true,
            hmr: {
                host: hmrHost,
                protocol: isNgrok ? 'https' : 'http',
                clientPort: isNgrok ? 443 : 5176
            },
            cors: {
                origin: '*', // Allow all origins for window.opener communication
                methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                credentials: true,
            },
            headers: {
                'Access-Control-Allow-Origin': '*',
                'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers': 'Content-Type, X-Requested-With'
            }
        },
        build: {
            // Always generate a manifest
            manifest: true,
            outDir: 'public/build',
            assetsDir: 'assets',
            rollupOptions: {
                output: {
                    // Ensure consistent asset names between builds
                    manualChunks: undefined
                }
            }
        },
        optimizeDeps: {
            include: ['vue']
        },
        // Ensure all relative paths are resolved properly
        resolve: {
            alias: {
                '@': '/resources/js'
            }
        }
    };
});
