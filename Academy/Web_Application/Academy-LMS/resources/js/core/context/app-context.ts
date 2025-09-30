import type { ModuleManifest, ModuleManifestEntry } from '@/core/modules/types';

type RawContext = {
    user?: {
        id?: number | string;
        name?: string;
        email?: string;
        role?: string;
        permissions?: string[];
    };
    csrfToken?: string;
    locale?: string;
    timezone?: string;
    featureFlags?: Record<string, unknown>;
    spaBasePath?: string;
    endpoints?: {
        apiBaseUrl?: string;
        manifestUrl?: string;
    };
    manifest?: {
        version?: string;
        generatedAt?: string;
        modules?: ModuleManifestEntry[];
    };
};

export interface UserContext {
    id: number;
    name: string;
    email: string;
    role: string;
    permissions: string[];
}

export interface AppContext {
    user: UserContext;
    csrfToken: string;
    locale: string;
    timezone: string;
    featureFlags: Record<string, boolean>;
    spaBasePath: string;
    endpoints: {
        apiBaseUrl: string;
        manifestUrl: string;
    };
    manifest: ModuleManifest;
}

declare global {
    interface Window {
        __COMMUNITY_ADMIN_APP__?: RawContext;
    }
}

function toBooleanMap(values: Record<string, unknown> | undefined): Record<string, boolean> {
    const flags: Record<string, boolean> = {};

    Object.entries(values ?? {}).forEach(([key, value]) => {
        flags[key] = value === true || value === 'true' || value === 1 || value === '1';
    });

    return flags;
}

function normalizeModules(modules: ModuleManifestEntry[] | undefined): ModuleManifestEntry[] {
    if (!Array.isArray(modules)) {
        return [];
    }

    return modules.map((module) => ({
        ...module,
        permissions: Array.isArray(module.permissions) ? module.permissions : [],
        navigation: Array.isArray(module.navigation) ? module.navigation : [],
        routes: Array.isArray(module.routes) ? module.routes : [],
        endpoints: module.endpoints ?? {},
        capabilities: module.capabilities ?? {},
    }));
}

function normalizeManifest(rawManifest: RawContext['manifest']): ModuleManifest {
    return {
        version: rawManifest?.version ?? '0.0.0',
        generatedAt: rawManifest?.generatedAt ?? new Date().toISOString(),
        modules: normalizeModules(rawManifest?.modules),
    };
}

function resolveContext(): AppContext {
    const raw: RawContext | undefined = window.__COMMUNITY_ADMIN_APP__;

    if (!raw) {
        throw new Error('Community admin application context is missing.');
    }

    const user = raw.user ?? {};
    const locale = (raw.locale ?? 'en').toString();
    const timezone = (raw.timezone ?? 'UTC').toString();

    if (document?.documentElement) {
        document.documentElement.lang = locale;
    }

    return Object.freeze({
        user: {
            id: Number(user.id ?? 0),
            name: (user.name ?? '').toString(),
            email: (user.email ?? '').toString(),
            role: (user.role ?? 'guest').toString(),
            permissions: Array.isArray(user.permissions) ? user.permissions : [],
        },
        csrfToken: (raw.csrfToken ?? '').toString(),
        locale,
        timezone,
        featureFlags: toBooleanMap(raw.featureFlags),
        spaBasePath: (raw.spaBasePath ?? '/admin/communities/app').toString(),
        endpoints: {
            apiBaseUrl: (raw.endpoints?.apiBaseUrl ?? '').toString(),
            manifestUrl: (raw.endpoints?.manifestUrl ?? '').toString(),
        },
        manifest: normalizeManifest(raw.manifest),
    });
}

export const appContext: AppContext = resolveContext();
