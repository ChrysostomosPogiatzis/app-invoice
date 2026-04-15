<script setup lang="ts">
import { useForm, Head, Link, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

interface PageProps {
    auth: { user: any };
    workspace: {
        features: Record<string, boolean>;
        name?: string;
    };
    [key: string]: any;
}

const props = defineProps<{
    contact: any
}>();

const page = usePage<PageProps>();

const callForm = useForm({
    contact_id: props.contact.id,
    call_type: 'outbound',
    call_notes: '',
    call_duration_seconds: 0,
    invoice_id: null as number | null,
});

const infoForm = useForm({
    general_info: props.contact.general_info,
});

const reminderForm = useForm({
    contact_id: props.contact.id,
    title: '',
    remind_at: '',
});

const isEditingInfo = ref(false);
const isLoggingCall = ref(false);
const editingCallLogId = ref<number | null>(null);

const submitCallLog = () => {
    const request = editingCallLogId.value
        ? callForm.put(route('communications.update', editingCallLogId.value), {
            onSuccess: () => resetCallForm(),
        })
        : callForm.post(route('communications.store'), {
            onSuccess: () => resetCallForm(),
        });

    return request;
};

const resetCallForm = () => {
    callForm.reset('call_notes', 'call_duration_seconds', 'invoice_id');
    callForm.call_type = 'outbound';
    editingCallLogId.value = null;
    isLoggingCall.value = false;
};

const startEditingCallLog = (log: any) => {
    editingCallLogId.value = log.id;
    callForm.contact_id = props.contact.id;
    callForm.call_type = log.call_type;
    callForm.call_notes = log.call_notes;
    callForm.call_duration_seconds = log.call_duration_seconds || 0;
    callForm.invoice_id = log.invoice_id ?? null;
    isLoggingCall.value = true;
};

const deleteCallLog = (id: number) => {
    if (confirm('Delete this call log?')) {
        router.delete(route('communications.destroy', id), {
            preserveScroll: true,
        });
    }
};

const submitReminder = () => {
    reminderForm.post(route('reminders.store'), {
        onSuccess: () => reminderForm.reset('title', 'remind_at'),
    });
};

const deleteReminder = (id: number) => {
    if (confirm('Delete this reminder?')) {
        router.delete(route('reminders.destroy', id));
    }
};

const deleteContact = (id: number) => {
    if (confirm('Delete contact and exclude from sync?')) {
        router.delete(route('contacts.destroy', id));
    }
};

const updateGeneralInfo = () => {
    infoForm.put(route('contacts.update', props.contact.id), {
        onSuccess: () => {
            isEditingInfo.value = false;
        },
        preserveScroll: true
    });
};

const formatDate = (date: string) => date ? new Date(date).toLocaleString('en-GB') : '—';
const formatShortDate = (date: string) => date ? new Date(date).toLocaleDateString('en-GB') : '—';

const hasFeature = (feature: string) => page.props.workspace?.features?.[feature] ?? false;
</script>

<template>
    <Head :title="contact.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-4">
                    <Link :href="route('contacts.index')" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </Link>
                    <h2 class="text-xl font-bold text-slate-800">{{ contact.name }}</h2>
                </div>
                <div class="flex items-center gap-3">
                    <button v-if="hasFeature('call_intelligence')" @click="isLoggingCall = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                        Log Activity
                    </button>
                    <button @click="deleteContact(contact.id)" class="text-sm font-medium text-rose-500 hover:text-rose-700 px-4">
                        Delete Profile
                    </button>
                    <Link :href="route('contacts.edit', contact.id)" class="text-sm font-medium text-slate-500 hover:text-slate-800 px-4">
                        Edit Profile
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Summary Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                    <div class="flex items-start gap-8">
                        <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center text-2xl font-bold text-slate-400 border border-slate-200">
                            {{ contact.name.charAt(0) }}
                        </div>
                        <div class="space-y-4 pt-1">
                            <div>
                                <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider mb-2 inline-block">{{ contact.contact_type }}</span>
                                <h1 class="text-3xl font-bold text-slate-900 leading-none">{{ contact.company_name || 'Individual' }}</h1>
                            </div>
                            <div class="flex flex-wrap gap-6 text-sm text-slate-500">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2"></path></svg>
                                    {{ contact.email || 'No email' }}
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-width="2"></path></svg>
                                    {{ contact.mobile_number || 'No phone' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-indigo-600 rounded-xl p-8 text-white shadow-lg overflow-hidden relative">
                    <div class="relative z-10">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-indigo-200 mb-2">Total Fiscal Value</div>
                        <div class="text-3xl font-bold tabular-nums">€{{ contact.invoices.reduce((a: number, b: any) => a + parseFloat(b.grand_total_gross), 0).toFixed(2) }}</div>
                        <div class="mt-8 pt-4 border-t border-white/10 flex justify-between text-xs font-medium">
                            <span class="text-indigo-200">Activity Level</span>
                            <span>Standard Reliability</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Activity Timeline -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-8">Activity History</h3>
                        
                        <div class="space-y-8 relative before:absolute before:left-[19px] before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100">
                            <!-- Invoices -->
                            <div v-for="inv in contact.invoices" :key="inv.id" class="relative pl-12">
                                <div class="absolute left-0 top-1.5 w-10 h-10 bg-white border-4 border-white shadow-sm rounded-full flex items-center justify-center">
                                    <div class="w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
                                </div>
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="text-[10px] font-bold text-slate-400">{{ formatShortDate(inv.date) }}</div>
                                        <Link :href="route('invoices.show', inv.id)" class="text-sm font-semibold text-slate-900 hover:text-indigo-600">Invoice #{{ inv.invoice_number }} issued</Link>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-bold text-slate-900">€{{ parseFloat(inv.grand_total_gross).toFixed(2) }}</div>
                                        <div class="text-[9px] font-bold uppercase" :class="inv.status === 'paid' ? 'text-emerald-500' : 'text-amber-500'">{{ inv.status }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Communications -->
                            <div v-if="hasFeature('call_intelligence')" v-for="log in contact.communications" :key="log.id" class="relative pl-12">
                                <div class="absolute left-0 top-1.5 w-10 h-10 bg-white border-4 border-white shadow-sm rounded-full flex items-center justify-center">
                                    <div class="w-2.5 h-2.5 rounded-full bg-slate-400"></div>
                                </div>
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="text-[10px] font-bold text-slate-400">{{ formatDate(log.call_date) }}</div>
                                        <div class="text-sm font-semibold text-slate-900 capitalize">{{ log.call_type }} Communication</div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div v-if="log.call_duration_seconds > 0" class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">
                                            {{ Math.floor(log.call_duration_seconds / 60) }}m {{ log.call_duration_seconds % 60 }}s
                                        </div>
                                        <button @click="startEditingCallLog(log)" class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 hover:text-indigo-800">
                                            Edit
                                        </button>
                                        <button @click="deleteCallLog(log.id)" class="text-[10px] font-bold uppercase tracking-wider text-rose-500 hover:text-rose-700">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <p class="mt-2 p-3 bg-slate-50 rounded-lg text-xs text-slate-600 leading-relaxed border border-slate-100">{{ log.call_notes }}</p>
                            </div>

                            <div v-if="contact.invoices.length === 0 && contact.communications.length === 0" class="py-12 text-center text-sm text-slate-400 italic">
                                No activity recorded yet.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Sidebar -->
                <div class="space-y-6">
                    <!-- Bio -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Background</h3>
                            <button @click="isEditingInfo = !isEditingInfo" class="text-[10px] font-bold text-indigo-600 uppercase hover:underline">
                                {{ isEditingInfo ? 'Cancel' : 'Update' }}
                            </button>
                        </div>
                        <div v-if="!isEditingInfo" class="text-sm text-slate-600 leading-relaxed">
                            {{ contact.general_info || 'No profile notes available.' }}
                        </div>
                        <form v-else @submit.prevent="updateGeneralInfo" class="space-y-4">
                             <textarea v-model="infoForm.general_info" rows="4" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-3 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none" placeholder="Notes..."></textarea>
                             <button type="submit" :disabled="infoForm.processing" class="w-full bg-slate-900 text-white py-2 rounded-lg text-xs font-bold uppercase tracking-widest">Save Notes</button>
                        </form>
                    </div>

                    <!-- Reminders -->
                    <div v-if="hasFeature('crm_reminders')" class="bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-6">Upcoming Reminders</h3>
                        
                        <div class="space-y-3 mb-6">
                            <div v-for="rem in contact.reminders" :key="rem.id" class="p-3 bg-slate-50 rounded-lg border border-slate-100 flex justify-between items-center group">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-800 truncate">{{ rem.title }}</div>
                                    <div class="text-[10px] text-slate-400">{{ formatShortDate(rem.remind_at) }}</div>
                                </div>
                                <button @click="deleteReminder(rem.id)" class="text-slate-300 hover:text-rose-500 p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"></path></svg>
                                </button>
                            </div>
                            <div v-if="contact.reminders?.length === 0" class="text-center py-4 text-xs text-slate-300 italic border-2 border-dashed border-slate-100 rounded-lg">No active reminders</div>
                        </div>

                        <form @submit.prevent="submitReminder" class="space-y-4 pt-4 border-t border-slate-100">
                             <input v-model="reminderForm.title" type="text" placeholder="Add new task..." class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:ring-1 focus:ring-amber-500" required />
                             <input v-model="reminderForm.remind_at" type="datetime-local" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:ring-1 focus:ring-amber-500" required />
                             <button type="submit" :disabled="reminderForm.processing" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-colors">Add Reminder</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call Log Modal -->
        <div v-if="isLoggingCall" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-md p-8 shadow-2xl border border-slate-200">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-slate-900">{{ editingCallLogId ? 'Edit Call Log' : 'Log Correspondence' }}</h3>
                    <p class="text-xs text-slate-400 mt-1">Documented activity for CRM record-keeping.</p>
                </div>
                
                <form @submit.prevent="submitCallLog" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                         <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Type</label>
                            <select v-model="callForm.call_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="outbound">Outbound</option>
                                <option value="inbound">Inbound</option>
                                <option value="missed">Missed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Seconds</label>
                            <input v-model="callForm.call_duration_seconds" type="number" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500/20" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Notes</label>
                        <textarea v-model="callForm.call_notes" rows="4" placeholder="Brief summary of interaction..." class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 resize-none font-medium" required></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Related Invoice</label>
                        <select v-model="callForm.invoice_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <option :value="null">No linked invoice</option>
                            <option v-for="inv in contact.invoices" :key="inv.id" :value="inv.id">
                                {{ inv.invoice_number }}
                            </option>
                        </select>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="resetCallForm" class="flex-grow bg-white border border-slate-200 text-slate-600 font-bold py-2.5 rounded-lg text-xs uppercase tracking-wider hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="callForm.processing" class="flex-grow bg-indigo-600 text-white font-bold py-2.5 rounded-lg text-xs uppercase tracking-wider hover:bg-indigo-700 shadow-lg">
                            {{ editingCallLogId ? 'Update Activity' : 'Save Activity' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
