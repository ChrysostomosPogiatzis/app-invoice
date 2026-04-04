<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    errors?: Record<string, string | string[] | undefined>;
}>();

const messages = computed(() =>
    Object.values(props.errors ?? {}).flatMap((value) => {
        if (!value) return [];
        return Array.isArray(value) ? value : [value];
    }),
);
</script>

<template>
    <div v-if="messages.length" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <div class="font-semibold">Please fix the highlighted fields.</div>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li v-for="message in messages" :key="message">{{ message }}</li>
        </ul>
    </div>
</template>
