import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import axios from 'axios';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Configure Inertia to use the correct base URL
document.addEventListener('DOMContentLoaded', () => {
    const appUrl = document.querySelector('meta[name="app-url"]').getAttribute('content');
    window.appUrl = appUrl;
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob(['./pages/**/*.vue', './Pages/**/*.vue']),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        // Make appUrl available to components
        app.config.globalProperties.$appUrl = window.appUrl;
        
        return app
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
