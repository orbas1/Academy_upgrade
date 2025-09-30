<template>
    <section class="ds-card">
        <header v-if="$slots.header" class="ds-card__header">
            <slot name="header" />
        </header>
        <div class="ds-card__body">
            <slot />
        </div>
        <footer v-if="$slots.footer" class="ds-card__footer">
            <slot name="footer" />
        </footer>
    </section>
</template>
