<template>
    <div class="rounded-lg bg-slate-50 p-4 transition hover:bg-slate-100">
        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            <slot name="label" />
        </dt>
        <dd class="mt-2 text-xl font-semibold text-slate-900">
            <slot />
        </dd>
        <p v-if="$slots.description" class="mt-1 text-xs text-slate-500">
            <slot name="description" />
        </p>
    </div>
</template>
