import { onMounted, onUnmounted, ref } from 'vue';

export interface NetworkStatus {
    isOnline: boolean;
    changedAt: Date;
}

export function useNetworkStatus() {
    const isOnline = ref(typeof navigator === 'undefined' ? true : navigator.onLine);
    const lastChangedAt = ref<Date>(new Date());

    function updateStatus(online: boolean) {
        isOnline.value = online;
        lastChangedAt.value = new Date();
    }

    function handleOnline() {
        updateStatus(true);
    }

    function handleOffline() {
        updateStatus(false);
    }

    onMounted(() => {
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
    });

    onUnmounted(() => {
        window.removeEventListener('online', handleOnline);
        window.removeEventListener('offline', handleOffline);
    });

    return {
        isOnline,
        lastChangedAt,
        updateStatus,
    };
}
