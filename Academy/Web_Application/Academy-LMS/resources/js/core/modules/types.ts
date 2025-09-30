import type { RouteRecordRaw } from 'vue-router';
import type { AxiosInstance } from 'axios';
import type { AppContext } from '@/core/context/app-context';

export interface NavigationItem {
    label: string;
    route: string;
    icon?: string | null;
}

export interface ModuleRouteConfig {
    name: string;
    path: string;
    title: string;
}

export interface ModuleManifestEntry {
    key: string;
    name: string;
    description?: string | null;
    featureFlag?: string | null;
    permissions: string[];
    navigation: NavigationItem[];
    routes: ModuleRouteConfig[];
    endpoints: Record<string, string>;
    capabilities: Record<string, unknown>;
}

export interface ModuleManifest {
    version: string;
    generatedAt: string;
    modules: ModuleManifestEntry[];
}

export interface RegisteredModule {
    key: string;
    navigation: NavigationItem[];
    routes: RouteRecordRaw[];
}

export interface ModuleRegistrationContext {
    manifest: ModuleManifestEntry;
    appContext: AppContext;
    httpClient: AxiosInstance;
}

export type ModuleFactory = (context: ModuleRegistrationContext) => RegisteredModule;
