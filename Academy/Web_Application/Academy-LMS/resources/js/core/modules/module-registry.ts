import { appContext } from '@/core/context/app-context';
import { httpClient } from '@/core/http/http-client';
import type { ModuleFactory, RegisteredModule } from '@/core/modules/types';
import { createCommunitiesModule } from '@/modules/communities';
import { createModerationModule } from '@/modules/moderation';

const factories: Record<string, ModuleFactory> = {
    communities: createCommunitiesModule,
    moderation: createModerationModule,
};

function instantiateModules(): RegisteredModule[] {
    return appContext.manifest.modules
        .map((manifest) => {
            const factory = factories[manifest.key];

            if (!factory) {
                return null;
            }

            if (manifest.featureFlag && appContext.featureFlags[manifest.featureFlag] !== true) {
                return null;
            }

            const authorized = manifest.permissions.every((permission) =>
                appContext.user.permissions.includes(permission),
            );

            if (!authorized) {
                return null;
            }

            return factory({
                manifest,
                appContext,
                httpClient,
            });
        })
        .filter((module): module is RegisteredModule => module !== null);
}

export const registeredModules = Object.freeze(instantiateModules());
export const moduleNavigation = Object.freeze(
    registeredModules.flatMap((module) => module.navigation),
);
export const moduleRoutes = registeredModules.flatMap((module) => module.routes);
