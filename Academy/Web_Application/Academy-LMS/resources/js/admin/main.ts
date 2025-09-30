import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from '@/admin/App.vue';
import router from '@/admin/router';
import { setupRouteComponentPrefetch } from '@/core/performance/route-prefetcher';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

setupRouteComponentPrefetch(router);

app.mount('#community-admin-app');
