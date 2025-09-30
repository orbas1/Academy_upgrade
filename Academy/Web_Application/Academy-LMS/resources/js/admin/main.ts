import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from '@/admin/App.vue';
import router from '@/admin/router';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

app.mount('#community-admin-app');
