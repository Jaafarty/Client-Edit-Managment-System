import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig, lazyPlugins } from 'vite-plus';

const routeFiles = [
    '../backend/routes/**/*.php',
    '../backend/app/Http/**/*.php',
];

export default defineConfig({
    envDir: '../backend',
    build: {
        emptyOutDir: true,
    },
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            publicDirectory: '../backend/public',
            refresh: [
                '../backend/resources/views/**/*.blade.php',
                ...routeFiles,
            ],
        }),
        inertia(),
        react(),
        tailwindcss(),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        sortTailwindcss: {
            stylesheet: 'resources/css/app.css',
        },
    },
});
