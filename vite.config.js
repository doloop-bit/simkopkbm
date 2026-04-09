import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";
import {
    svelte
} from '@sveltejs/vite-plugin-svelte';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/paud.js', 'resources/js/paketa.js', 'resources/js/public-site.js'],
            refresh: true,
        }),
        tailwindcss(),
        svelte(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
