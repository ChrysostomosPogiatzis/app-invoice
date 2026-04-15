<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    logs: {
        data: any[];
        links: any[];
        total: number;
    };
    filters: {
        search?: string;
        call_type?: string;
    };
}>();

const search = ref(props.filters.search || '');
const callTypeFilter = ref(props.filters.call_type || '');

watch([search, callTypeFilter], () => {
    router.get(route('communications.index'), {
        search: search.value,
        call_type: callTypeFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

const callTypeStyle = (callType: string) => {
    const styles: Record<string, string> = {
        inbound: 'bg-emerald-50 text-emerald-700 border-emerald-100',
        outbound: 'bg-indigo-50 text-indigo-700 border-indigo-100',
        missed: 'bg-rose-50 text-rose-700 border-rose-100',
    };

    return styles[callType] || 'bg-slate-50 text-slate-700 border-slate-100';
};

const formatDuration = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs.toString().padStart(2, '0')}s`;
};
</script>

<template>
    <Head title="Call Logs" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Call Logs</h2>
                    <p class="text-sm text-slate-500">Inbound, outbound, and missed communication records for the active workspace.</p>
                </div>

                <div class="flex w-full flex-wrap items-center gap-3 lg:w-auto">
                    <div class="relative flex-grow lg:flex-grow-0">
                        <input v-model="search" type="text" placeholder="Search notes or contact" class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-10 pr-4 text-sm outline-none transition-all focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 lg:w-72" />
                        <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round"></path></svg>
                    </div>

                    <select v-model="callTypeFilter" class="rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm outline-none transition-all focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">All Types</option>
                        <option value="inbound">Inbound</option>
                        <option value="outbound">Outbound</option>
                        <option value="missed">Missed</option>
                    </select>
                </div>
            </div>
        </template>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-left">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Date</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Contact</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-600 text-center">Type</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-600 text-center">Duration</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="log in logs.data" :key="log.id" class="transition-colors hover:bg-slate-50/50">
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ new Date(log.call_date).toLocaleString('en-GB') }}
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="log.contact">
                                <Link :href="route('contacts.show', log.contact_id)" class="text-sm font-semibold text-slate-900 hover:text-indigo-600">
                                    {{ log.contact.name }}
                                </Link>
                                <div class="text-[11px] font-medium text-slate-400">
                                    {{ log.contact.company_name || 'Individual' }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span :class="callTypeStyle(log.call_type)" class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize">
                                {{ log.call_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-semibold text-slate-700">
                            {{ formatDuration(log.call_duration_seconds || 0) }}
                        </td>
                        <td class="px-6 py-4 text-sm leading-6 text-slate-600">
                            {{ log.call_notes }}
                        </td>
                    </tr>
                    <tr v-if="logs.data.length === 0">
                        <td colspan="5" class="px-6 py-20 text-center text-sm italic text-slate-400">No call logs found.</td>
                    </tr>
                </tbody>
            </table>

            <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-6 py-4">
                <span class="text-xs font-medium text-slate-500">Showing {{ logs.data.length }} of {{ logs.total }} logs</span>
                <div class="flex gap-2">
                    <Link
                        v-for="link in logs.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-500 hover:text-slate-700',
                            !link.url ? 'cursor-not-allowed opacity-30' : ''
                        ]"
                        class="rounded-lg border px-3 py-1.5 text-xs font-medium shadow-sm transition-all"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
