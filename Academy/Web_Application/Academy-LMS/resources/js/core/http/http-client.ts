import axios, { AxiosError, AxiosInstance } from 'axios';
import { appContext } from '@/core/context/app-context';

export const httpClient: AxiosInstance = axios.create({
    baseURL: appContext.endpoints.apiBaseUrl,
    headers: {
        'X-CSRF-TOKEN': appContext.csrfToken,
        Accept: 'application/json',
    },
    withCredentials: true,
});

httpClient.interceptors.response.use(
    (response) => response,
    (error: AxiosError) => {
        if (error.response?.status === 401) {
            window.location.href = '/login';
        }

        return Promise.reject(error);
    },
);

export function buildUrl(path: string): string {
    if (!path.startsWith('/')) {
        return `${appContext.endpoints.apiBaseUrl.replace(/\/$/, '')}/${path}`;
    }

    return `${appContext.endpoints.apiBaseUrl.replace(/\/$/, '')}${path}`;
}
