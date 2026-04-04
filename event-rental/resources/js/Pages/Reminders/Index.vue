<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps<{
    reminders: Array<any>
}>();

const deleteReminder = (id: number) => {
    if (confirm('Abolish this scheduled directive?')) {
        router.delete(route('reminders.destroy', id), {
            preserveScroll: true
        });
    }
};

const formatDate = (dateString: string) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Reminders" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-4">
                <h2 class="text-xl font-bold text-slate-800">Operational Reminders</h2>
                <div class="flex items-center gap-3">
                    <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border border-indigo-100 shadow-sm">
                        {{ reminders.length }} Active Tasks
                    </span>
                    <Link :href="route('contacts.index')" class="text-xs font-bold text-indigo-600 hover:text-slate-900 uppercase tracking-widest transition-colors">
                        Add New in CRM →
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Reminder Cards -->
                <div v-for="rem in reminders" :key="rem.id" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all group relative">
                    <div class="flex justify-between items-start mb-6">
                        <div class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Scheduled</div>
                        <button @click="deleteReminder(rem.id)" class="text-slate-300 hover:text-rose-500 transition-colors opacity-0 group-hover:opacity-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <h3 class="text-lg font-bold text-slate-900 mb-2 truncate leading-tight transition-colors group-hover:text-amber-600">{{ rem.title }}</h3>
                    
                    <div class="flex items-center gap-2 text-slate-400 mb-6 font-medium">
                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                         <span class="text-xs tabular-nums">{{ formatDate(rem.remind_at) }}</span>
                    </div>

                    <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-lg bg-slate-900 flex items-center justify-center text-white text-sm font-bold shadow-sm">
                                {{ rem.contact.name.charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <label class="block text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-0.5 leading-none">Contact</label>
                                <div class="text-sm font-bold text-slate-800 truncate">{{ rem.contact.name }}</div>
                            </div>
                        </div>
                        <Link :href="route('contacts.show', rem.contact_id)" class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7-7 7M3 12h18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </Link>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="reminders.length === 0" class="lg:col-span-3 py-24 text-center bg-white rounded-xl border border-slate-100 shadow-sm">
                    <div class="w-16 h-16 bg-slate-50 rounded-xl flex items-center justify-center mx-auto mb-6 border border-slate-100">
                         <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="12" cy="12" r="9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></circle></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-2">No Active Reminders</h3>
                    <p class="text-xs text-slate-400 max-w-xs mx-auto font-medium">There are currently no tasks scheduled for follow-up across your operational matrix.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
