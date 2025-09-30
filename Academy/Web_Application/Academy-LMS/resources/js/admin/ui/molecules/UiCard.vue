<template>
    <section
        class="rounded-xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md focus-within:shadow-md"
    >
        <header v-if="$slots.header" class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <slot name="header" />
        </header>
        <div class="px-5 py-4">
            <slot />
        </div>
        <footer v-if="$slots.footer" class="border-t border-slate-200 bg-slate-50 px-5 py-4">
            <slot name="footer" />
        </footer>
    </section>
</template>
