import { defineConfig } from 'vite';

// A separate classic bundle runs inside the opaque, sandboxed resource document.
export default defineConfig({
    publicDir: false,
    build: {
        outDir: 'public/build', emptyOutDir: false, manifest: 'office-manifest.json',
        lib: { entry: 'resources/js/office-preview.js', name: 'KodyOfficePreview', formats: ['iife'] },
        rollupOptions: { output: { entryFileNames: 'office-preview-[hash].js' } },
    },
});
