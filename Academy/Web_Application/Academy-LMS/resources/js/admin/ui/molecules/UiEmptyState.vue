<template>
    <div class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-slate-500">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
            <slot name="icon">
                <span class="text-2xl">ℹ️</span>
            </slot>
        </div>
        <div class="space-y-1">
            <h3 class="text-base font-semibold text-slate-800">
                <slot name="title">Nothing to show yet</slot>
            </h3>
            <p class="text-sm text-slate-500">
                <slot>No records match your filters. Adjust filters or try again later.</slot>
            </p>
        </div>
        <div v-if="$slots.actions" class="mt-3 flex flex-wrap items-center justify-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
