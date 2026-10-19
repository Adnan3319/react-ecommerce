import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import babel from '@rolldown/plugin-babel';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import svgr from 'vite-plugin-svgr';
import { defineConfig, lazyPlugins } from 'vite-plus';
import { fileURLToPath } from 'url';
import path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    resolve: {
        alias: {
            '@admin': path.resolve(__dirname, 'resources/js/admin'),
            '@/admin': path.resolve(__dirname, 'resources/js/admin'),
            '@/storefront': path.resolve(__dirname, 'resources/js/storefront'),
            '@/shared': path.resolve(__dirname, 'resources/js/shared'),
        },
    },
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/storefront/app.tsx',
                'resources/css/admin.css',
                'resources/js/admin/app.tsx',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        react(),
        babel({
            presets: [reactCompilerPreset()],
        }),
        tailwindcss(),
        svgr({
            svgrOptions: {
                icon: true,
                exportType: 'named',
                namedExport: 'ReactComponent',
            },
            include: '**/*.svg?react',
        }),
        wayfinder({
            formVariants: true,
        }),
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
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
        overrides: [
            {
                files: ['resources/js/shared/**'],
                rules: {
                    'no-restricted-imports': [
                        'error',
                        {
                            patterns: [
                                {
                                    group: [
                                        '@/storefront/**',
                                        '@/admin/**',
                                        '@admin/**',
                                    ],
                                    message:
                                        'shared must not import from storefront or admin.',
                                },
                            ],
                        },
                    ],
                },
            },
            {
                files: ['resources/js/storefront/**'],
                rules: {
                    'no-restricted-imports': [
                        'error',
                        {
                            patterns: [
                                {
                                    group: ['@/admin/**', '@admin/**'],
                                    message:
                                        'storefront must not import from admin.',
                                },
                            ],
                        },
                    ],
                },
            },
            {
                files: ['resources/js/admin/**'],
                rules: {
                    'no-restricted-imports': [
                        'error',
                        {
                            patterns: [
                                {
                                    group: ['@/storefront/**'],
                                    message:
                                        'admin must not import from storefront.',
                                },
                            ],
                        },
                    ],
                },
            },
        ],
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
