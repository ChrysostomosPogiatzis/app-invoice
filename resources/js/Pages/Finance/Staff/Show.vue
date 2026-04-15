<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/utils/helpers';

const props = defineProps<{
    member: any
}>();

const activeTab = ref('overview');

const leaveForm = useForm({
    start_date: '',
    end_date: '',
    type: 'annual',
    reason: ''
});

const submitLeave = () => {
    leaveForm.post(route('staff.leave.store', props.member.id), {
        onSuccess: () => { leaveForm.reset(); }
    });
};

const approveLeave = (leaveId: number) => {
    router.patch(route('staff.leave.approve', { staffId: props.member.id, leaveId }), {}, { preserveScroll: true });
};

const rejectLeave = (leaveId: number) => {
    if (!confirm('Reject this leave request?')) return;
    router.patch(route('staff.leave.reject', { staffId: props.member.id, leaveId }), {}, { preserveScroll: true });
};

const deleteDocument = (docId: number) => {
    if (!confirm('Permanently delete this document?')) return;
    router.delete(route('staff-members.documents.destroy', { staffId: props.member.id, docId }), { preserveScroll: true });
};

const docForm = useForm({
    document_file: null as File | null,
    name: '',
    type: 'General'
});

const fileInput = ref<HTMLInputElement | null>(null);

const triggerUpload = () => {
    if (fileInput.value) fileInput.value.click();
};

const showDocModal = ref(false);

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        docForm.document_file = file;
        docForm.name = file.name.split('.').slice(0, -1).join('.').replace(/[-_]/g, ' ');
        showDocModal.value = true;
    }
};

const confirmUpload = () => {
    docForm.post(route('staff-members.documents.store', props.member.id), {
        preserveScroll: true,
        onSuccess: () => {
            docForm.reset();
            showDocModal.value = false;
            if (fileInput.value) fileInput.value.value = '';
        }
    });
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'approved': return 'text-emerald-600 bg-emerald-50 border-emerald-100';
        case 'pending': return 'text-amber-600 bg-amber-50 border-amber-100';
        case 'rejected': return 'text-rose-600 bg-rose-50 border-rose-100';
        default: return 'text-slate-600 bg-slate-50 border-slate-100';
    }
};
</script>

<template>
    <Head :title="`Profile - ${member.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center gap-4">
                    <Link :href="route('staff-members.index')" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-width="2"></path></svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">{{ member.name }}</h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">{{ member.position || 'Professional Personnel' }}</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20">
                        Export HR File
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto pt-8 px-4 pb-20">
            <!-- Profile Header Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
                <div class="h-32 bg-slate-900 relative">
                    <div class="absolute -bottom-12 left-8 w-24 h-24 rounded-3xl bg-indigo-500 border-8 border-white flex items-center justify-center text-4xl font-black text-white shadow-xl">
                        {{ member.name.charAt(0) }}
                    </div>
                </div>
                <div class="pt-16 pb-8 px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="md:col-span-1 border-r border-slate-100 pr-8">
                        <div class="label uppercase text-[9px] font-black text-slate-400 tracking-widest mb-1">Base Salary</div>
                        <div class="text-2xl font-black text-slate-900 tabular-nums">€{{ formatMoney(member.base_salary) }}</div>
                        <div class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-tight">Monthly Gross</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xs font-bold text-slate-500 flex items-center gap-2">
                           <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2"></path></svg>
                           {{ member.email || 'No email record' }}
                        </div>
                        <div class="text-xs font-bold text-slate-500 flex items-center gap-2">
                           <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-width="2"></path></svg>
                           {{ member.phone || 'No phone record' }}
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xs font-bold text-slate-500 flex items-center gap-2">
                           <span class="text-[9px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-600 font-black">TIC</span>
                           {{ member.tax_id || 'Pending' }}
                        </div>
                        <div class="text-xs font-bold text-slate-500 flex items-center gap-2">
                           <span class="text-[9px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-600 font-black">SI</span>
                           {{ member.si_number || 'Pending' }}
                        </div>
                    </div>
                    <div class="text-right">
                         <div class="label uppercase text-[9px] font-black text-slate-400 tracking-widest mb-1">Joined Date</div>
                         <div class="text-sm font-black text-slate-700 tracking-tight">{{ formatDate(member.joining_date) }}</div>
                    </div>
                </div>
            </div>

            <!-- Tabbed Sections -->
            <div class="flex gap-2 mb-6 p-1 bg-slate-100 rounded-2xl w-fit">
                <button v-for="tab in ['overview', 'leave', 'documents', 'payroll']" :key="tab" @click="activeTab = tab" 
                    :class="activeTab === tab ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
                    class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                >
                    {{ tab }}
                </button>
            </div>

            <!-- Overview Grid -->
            <div v-if="activeTab === 'overview'" class="grid grid-cols-1 md:grid-cols-3 gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="md:col-span-2 space-y-8">
                    <!-- Statistics -->
                    <div class="grid grid-cols-2 gap-6">
                        <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6">
                            <h5 class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-4">Leave Utilization</h5>
                            <div class="flex items-end justify-between">
                                <div class="text-3xl font-black text-indigo-900 tabular-nums">{{ member.leave_balance }}<span class="text-sm">/{{ member.annual_leave_total }}d</span></div>
                                <div class="w-20 bg-indigo-200 h-2 rounded-full overflow-hidden">
                                    <div class="bg-indigo-600 h-full" :style="{ width: (member.leave_balance / (member.annual_leave_total || 1)) * 100 + '%' }"></div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-6">
                            <h5 class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-4">YTD Net Paid</h5>
                            <div class="text-3xl font-black text-emerald-900 tabular-nums">€{{ formatMoney(member.expenses.reduce((acc: any, curr: any) => acc + Number(curr.net_payable), 0)) }}</div>
                        </div>
                    </div>

                    <!-- Payroll Master Config -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-sm font-black text-slate-800 uppercase tracking-tight">Payroll Settings</h4>
                            <Link :href="route('staff-members.index')" class="text-[9px] font-bold uppercase tracking-widest text-indigo-600 hover:text-indigo-800 transition-colors">Edit Settings</Link>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                                <div class="text-[9px] uppercase font-black text-slate-400 tracking-widest mb-1">Union Fee</div>
                                <div class="text-sm font-black text-slate-700">{{ member.union_rate ? member.union_rate + '%' : 'None' }} <span v-if="member.union_type" class="text-[9px] font-bold text-indigo-400 ml-1">({{ member.union_type }})</span></div>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                                <div class="text-[9px] uppercase font-black text-slate-400 tracking-widest mb-1">Provident EE</div>
                                <div class="text-sm font-black text-slate-700">{{ member.provident_employee_rate ? member.provident_employee_rate + '%' : 'Default' }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                                <div class="text-[9px] uppercase font-black text-slate-400 tracking-widest mb-1">Provident ER</div>
                                <div class="text-sm font-black text-slate-700">{{ member.provident_employer_rate ? member.provident_employer_rate + '%' : 'Default' }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                                <div class="text-[9px] uppercase font-black text-slate-400 tracking-widest mb-1">Holiday Fund</div>
                                <div class="text-sm font-black" :class="member.use_holiday_fund ? 'text-emerald-600' : 'text-slate-400'">{{ member.use_holiday_fund ? (member.holiday_rate ? member.holiday_rate + '%' : '8%') : 'Disabled' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    <!-- Emergency Contact -->
                    <div class="bg-rose-50 border border-rose-100 rounded-2xl p-6">
                        <h5 class="text-[10px] font-black text-rose-400 uppercase tracking-widest mb-4 italic">Emergency Contact</h5>
                        <div class="font-black text-slate-900 text-sm mb-1 uppercase tracking-tight">{{ member.emergency_contact_name || 'Not provided' }}</div>
                        <div class="text-xs font-bold text-rose-500">{{ member.emergency_contact_phone || '---' }}</div>
                    </div>

                    <!-- Banking -->
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6">
                        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Payment Destination</h5>
                        <div class="text-[11px] font-black text-slate-900 break-all bg-white p-3 rounded-lg border border-slate-200 shadow-sm leading-relaxed tracking-wider">
                            {{ member.iban || 'NO IBAN REGISTERED' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payroll History -->
            <div v-if="activeTab === 'payroll'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-500">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Pay Period</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Gross Amount</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Net Payable</th>
                            <th class="px-6 py-5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="pay in member.expenses" :key="pay.id" class="hover:bg-slate-50 transition-all">
                            <td class="px-6 py-5 font-black text-slate-700 text-sm italic">{{ formatDate(pay.expense_date) }}</td>
                            <td class="px-6 py-5 text-right font-bold text-slate-500 tabular-nums">€{{ formatMoney(pay.gross_salary) }}</td>
                            <td class="px-6 py-5 text-right font-black text-emerald-600 tabular-nums">€{{ formatMoney(pay.net_payable) }}</td>
                            <td class="px-6 py-5 text-right">
                                <a :href="route('payslips.download', pay.id)" class="bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border border-emerald-100 hover:bg-emerald-100 transition-all">Download Payslip</a>
                            </td>
                        </tr>
                        <tr v-if="member.expenses.length === 0">
                            <td colspan="4" class="px-6 py-20 text-center text-slate-300 font-bold uppercase tracking-widest text-xs">No payroll history recorded for this member.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Documents Section -->
            <div v-if="activeTab === 'documents'" class="grid grid-cols-2 md:grid-cols-4 gap-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <input type="file" ref="fileInput" class="hidden" @change="handleFileUpload" accept=".pdf,.png,.jpg,.jpeg" />
                <div @click="triggerUpload" :class="{'opacity-50 pointer-events-none': docForm.processing}" class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center p-8 aspect-square hover:border-indigo-300 hover:bg-indigo-50/20 transition-all cursor-pointer group relative">
                    <div class="w-12 h-12 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 group-hover:text-indigo-500 shadow-sm transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3"></path></svg>
                    </div>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-4">Upload Document</span>
                </div>
                
                <div v-for="doc in member.documents" :key="doc.id" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all h-fit group flex flex-col">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2"></path></svg>
                    </div>
                    <h6 class="text-xs font-black text-slate-900 mb-1 leading-tight">{{ doc.name }}</h6>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tight italic">{{ doc.type || 'Standard Document' }}</p>
                    <div class="mt-4 pt-4 border-t border-slate-50 flex items-center justify-between">
                        <a :href="doc.download_url" class="text-indigo-600 font-black text-[9px] uppercase tracking-widest hover:text-indigo-800">Download</a>
                        <button @click="deleteDocument(doc.id)" class="text-rose-400 hover:text-rose-600 text-[9px] font-black uppercase tracking-widest transition-colors">Delete</button>
                    </div>
                 </div>
            </div>

            <!-- Upload Document Modal -->
            <div v-if="showDocModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in duration-200">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl p-8 max-w-md w-full">
                    <h3 class="text-lg font-black text-slate-900 mb-6 uppercase tracking-tight">Document Details</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Document Title</label>
                            <input v-model="docForm.name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs outline-none focus:ring-2 focus:ring-indigo-500/50" placeholder="e.g. Identity Card, Work Visa">
                            <div v-if="docForm.errors.name" class="mt-1 text-rose-500 text-xs">{{ docForm.errors.name }}</div>
                        </div>
                        
                        <div>
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Document Type</label>
                            <select v-model="docForm.type" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs outline-none focus:ring-2 focus:ring-indigo-500/50 font-bold text-slate-700">
                                <option value="General">General Document</option>
                                <option value="Contract">Employment Contract</option>
                                <option value="ID/Passport">ID / Passport</option>
                                <option value="Medical Certificate">Medical Certificate</option>
                                <option value="Tax Form">Tax Form (e.g., IR59)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end mt-8">
                        <button type="button" @click="showDocModal = false;" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all">Cancel</button>
                        <button type="button" @click="confirmUpload" :disabled="docForm.processing" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest bg-indigo-600 text-white hover:bg-indigo-700 transition-all flex items-center justify-center min-w-[100px]">
                            <span v-if="docForm.processing">Uploading...</span>
                            <span v-else>Save File</span>
                        </button>
                    </div>
                </div>
            </div>

             <!-- Leave Management -->
             <div v-if="activeTab === 'leave'" class="grid grid-cols-1 md:grid-cols-3 gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-white rounded-2xl border border-slate-200 p-8 h-fit">
                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-6">Record Leave Request</h4>
                    <form @submit.prevent="submitLeave" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Start Date</label>
                                <input v-model="leaveForm.start_date" type="date" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1 tracking-wider">End Date</label>
                                <input v-model="leaveForm.end_date" type="date" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Type</label>
                            <select v-model="leaveForm.type" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 font-bold text-slate-700">
                                <option value="annual">Annual Holiday</option>
                                <option value="sick">Medical / Sick Leave</option>
                                <option value="military">Military Service</option>
                                <option value="pregnancy">Pregnancy / Maternity</option>
                                <option value="paternity">Paternity Leave</option>
                                <option value="special">Special / Compassionate</option>
                                <option value="unpaid">Unpaid Leave</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Note / Reason</label>
                            <textarea v-model="leaveForm.reason" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-slate-900 text-white font-black py-2.5 rounded-xl text-[9px] uppercase tracking-widest hover:bg-slate-800 transition-all shadow-xl">Submit Request</button>
                    </form>
                </div>

                <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Period</th>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Type</th>
                                <th class="px-6 py-5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="leave in member.leave_requests" :key="leave.id" class="hover:bg-slate-50 transition-all">
                                <td class="px-6 py-5">
                                    <div class="font-bold text-slate-900 text-xs">{{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}</div>
                                    <div class="text-[9px] text-slate-400 font-bold uppercase tracking-tight">{{ leave.days_count }} business days</div>
                                </td>
                                <td class="px-6 py-5">
                                    <span :class="getStatusColor(leave.status)" class="px-2 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest border">
                                        {{ leave.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="text-[10px] font-bold text-slate-600 uppercase">{{ leave.type }}</span>
                                </td>
                                <td class="px-6 py-5 text-right flex items-center gap-2 justify-end">
                                    <button v-if="leave.status === 'pending'" @click="approveLeave(leave.id)" class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 hover:bg-emerald-100 transition-all">Approve</button>
                                    <button v-if="leave.status === 'pending'" @click="rejectLeave(leave.id)" class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-500 border border-rose-100 hover:bg-rose-100 transition-all">Reject</button>
                                    <button v-if="leave.status === 'approved'" @click="rejectLeave(leave.id)" class="text-[9px] font-black uppercase tracking-widest text-rose-400 hover:underline">Revoke</button>
                                </td>
                            </tr>
                            <tr v-if="!member.leave_requests || member.leave_requests.length === 0">
                                <td colspan="4" class="px-6 py-20 text-center text-slate-300 font-bold uppercase tracking-widest text-[10px]">No leave history recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
             </div>

        </div>
    </AuthenticatedLayout>
</template>
