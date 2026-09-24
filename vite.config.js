import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import inertia from '@inertiajs/vite';
import { bunny } from 'laravel-vite-plugin/fonts';
import { portalDocsMdx } from './app-modules/portal-docs/resources/js/vite/mdx.js';

/** Packages whose own code-splitting must be preserved; see manualChunks below. */
const LAZY_PACKAGES = new Set(['@mintlify', 'mermaid', 'shiki', '@shikijs', 'lucide-react']);

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/filament/admin/theme.css',
                'app-modules/portal-docs/resources/css/portal-docs.css',
                'app-modules/portal-docs/resources/js/portal-docs.jsx',
            ],
            refresh: true,
            fonts: [
                bunny('JetBrains Mono', {
                    weights: [400, 500, 600],
                    fallbacks: ['ui-monospace', 'monospace'],
                }),
            ],
        }),
        tailwindcss(),
        // portalDocsMdx carries enforce: 'pre' and has to compile .mdx before react()
        // sees it, otherwise the React plugin treats MDX as plain JavaScript.
        portalDocsMdx(),
        react({ include: /\.(jsx|js|mdx)$/ }),
        inertia(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
    build: {
        minify: 'oxc',
        cssMinify: true,
        chunkSizeWarningLimit: 1600,
        reportCompressedSize: false,
        rolldownOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return undefined;
                    }

                    const pkg = id.split('node_modules/')[1].split('/')[0];

                    // These ship their own dynamic imports (mermaid diagrams, per-language
                    // Shiki grammars). Forcing them into one manual chunk would undo that
                    // and make every reader download the whole thing up front.
                    if (LAZY_PACKAGES.has(pkg)) {
                        return undefined;
                    }

                    return pkg;
                },
            },
        },
    },
});
