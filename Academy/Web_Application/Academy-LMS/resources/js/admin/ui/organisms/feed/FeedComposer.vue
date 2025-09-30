<template>
    <form class="space-y-4" @submit.prevent="submit">
        <UiTextarea
            v-model="body"
            label="Share an update"
            :rows="5"
            placeholder="What should members know today?"
        />
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <label class="inline-flex cursor-pointer items-center gap-2">
                <input type="file" class="hidden" multiple accept="image/*,video/*" @change="onFilesSelected" />
                <UiButton type="button" variant="secondary" size="sm">Add media</UiButton>
            </label>
            <button
                type="button"
                class="text-sm font-semibold text-indigo-600 hover:text-indigo-500"
                @click="toggleScheduling"
            >
                {{ scheduledAt ? 'Scheduled' : 'Schedule' }}
            </button>
            <select
                v-model="visibility"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
                <option value="community">Members</option>
                <option value="public">Public</option>
                <option value="paid">Paid tier</option>
            </select>
        </div>
        <div v-if="scheduledAt" class="flex items-center gap-3 text-sm text-slate-500">
            <label class="flex items-center gap-2">
                <span class="font-medium text-slate-600">Publish at</span>
                <input
                    v-model="scheduledAt"
                    type="datetime-local"
                    class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                />
            </label>
            <button type="button" class="text-xs font-semibold text-red-500" @click="scheduledAt = null">Clear</button>
        </div>
        <div v-if="attachments.length" class="flex flex-wrap gap-3">
            <div
                v-for="file in attachments"
                :key="file.id"
                class="group relative h-20 w-32 overflow-hidden rounded-lg border border-slate-200"
            >
                <img v-if="file.preview" :src="file.preview" :alt="file.name" class="h-full w-full object-cover" />
                <div v-else class="flex h-full items-center justify-center bg-slate-100 text-xs text-slate-500">
                    {{ file.name }}
                </div>
                <button
                    type="button"
                    class="absolute right-1 top-1 hidden rounded-full bg-white/90 p-1 text-xs font-semibold text-red-500 shadow group-hover:block"
                    @click="removeAttachment(file.id)"
                >
                    ✕
                </button>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3">
            <slot name="secondary-actions" />
            <UiButton type="submit" :loading="submitting" :disabled="!canSubmit">Publish update</UiButton>
        </div>
    </form>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import UiButton from '../../atoms/UiButton.vue';
import UiTextarea from '../../atoms/UiTextarea.vue';

type ComposerAttachment = {
    id: string;
    file: File;
    name: string;
    preview: string | null;
};

const props = defineProps<{
    submitting?: boolean;
}>();

const emit = defineEmits<{
    (e: 'submit', payload: {
        body: string;
        visibility: 'public' | 'community' | 'paid';
        attachments: File[];
        scheduledAt: string | null;
    }): void;
}>();

const body = ref('');
const visibility = ref<'public' | 'community' | 'paid'>('community');
const scheduledAt = ref<string | null>(null);
const attachments = reactive<ComposerAttachment[]>([]);

const canSubmit = computed(() => body.value.trim().length > 0 || attachments.length > 0);

function onFilesSelected(event: Event) {
    const target = event.target as HTMLInputElement;
    if (!target.files) {
        return;
    }

    for (const file of Array.from(target.files)) {
        const id = `${file.name}-${file.size}-${file.lastModified}-${crypto.randomUUID()}`;
        const attachment: ComposerAttachment = {
            id,
            file,
            name: file.name,
            preview: null,
        };

        attachments.push(attachment);

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = () => {
                attachment.preview = typeof reader.result === 'string' ? reader.result : null;
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            attachment.preview = URL.createObjectURL(file);
        }
    }

    target.value = '';
}

function removeAttachment(id: string) {
    const index = attachments.findIndex((attachment) => attachment.id === id);
    if (index >= 0) {
        const preview = attachments[index].preview;
        if (preview && preview.startsWith('blob:')) {
            URL.revokeObjectURL(preview);
        }
        attachments.splice(index, 1);
    }
}

function toggleScheduling() {
    scheduledAt.value = scheduledAt.value ? null : new Date().toISOString().slice(0, 16);
}

function resetForm() {
    body.value = '';
    scheduledAt.value = null;
    attachments.splice(0, attachments.length);
}

function submit() {
    if (!canSubmit.value || props.submitting) {
        return;
    }

    const files = attachments.map((attachment) => attachment.file);
    emit('submit', {
        body: body.value.trim(),
        visibility: visibility.value,
        attachments: files,
        scheduledAt: scheduledAt.value,
    });
    resetForm();
}

onBeforeUnmount(() => {
    attachments.forEach((attachment) => {
        if (attachment.preview && attachment.preview.startsWith('blob:')) {
            URL.revokeObjectURL(attachment.preview);
        }
    });
});
</script>
