import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { MotionDirective } from '@vueuse/motion';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { setLocale } from '@/lib/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'CashIko';

// Keep copy in sync with `business.language` after every navigation.
router.on('navigate', (event) => setLocale(event.detail.page.props.business?.language));

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        setLocale(props.initialPage.props.business?.language);
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
        app.directive('motion', MotionDirective())
        return app.mount(el)
    },
    progress: {
        color: 'hsl(145 48% 26%)',
    },
});
