import '../../css/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from '@/admin/App.vue';
import router from '@/admin/router';
import { setupRouteComponentPrefetch } from '@/core/performance/route-prefetcher';
import { applyDesignTokens } from '@/admin/ui';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

setupRouteComponentPrefetch(router);

applyDesignTokens();

app.mount('#community-admin-app');
