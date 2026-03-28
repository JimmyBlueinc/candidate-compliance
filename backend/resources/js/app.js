import './bootstrap';
import '../css/app.css';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Aura from '@primevue/themes/aura';
import 'primeicons/primeicons.css';
import { MotionPlugin } from '@vueuse/motion';
import App from './App.vue';
import router from './router';

const rootEl = document.documentElement;
const markIconsReady = () => rootEl.classList.add('icons-ready');

if (document.fonts?.check?.('24px "Material Symbols Outlined"')) {
    markIconsReady();
} else if (document.fonts?.load) {
    document.fonts
        .load('24px "Material Symbols Outlined"')
        .then(markIconsReady)
        .catch(markIconsReady);
} else {
    markIconsReady();
}

const app = createApp(App);

app.use(createPinia());
app.use(MotionPlugin);
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.theme-dark',
            cssLayer: false,
        },
    },
});
app.use(ToastService);
app.use(router);

router.onError((err) => {
    console.error('Vue Router error:', err);
});

router.isReady().then(() => {
    app.mount('#app');
});
