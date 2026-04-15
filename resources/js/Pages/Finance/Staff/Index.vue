<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney, withValidation } from '@/utils/helpers';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';

const props = defineProps<{
    staff: any[]
}>();

const page = usePage();
const workspace = computed(() => (page.props as any).workspace);

const tierLimits = {
    starter: 1,
    professional: 5,
    enterprise: 999999
};

const maxStaff = computed(() => tierLimits[workspace.value?.tier as keyof typeof tierLimits] || 5);
const staffCount = computed(() => props.staff.length);
const remainingSlots = computed(() => typeof maxStaff.value === 'number' ? Math.max(0, maxStaff.value - staffCount.value) : 'Unlimited');
const usagePercentage = computed(() => (staffCount.value / (typeof maxStaff.value === 'number' ? maxStaff.value : 100)) * 100);

const isAdding = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    position: '',
    base_salary: 0,
    id_number: '',
    si_number: '',
    tax_id: '',
    iban: '',
    joining_date: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    annual_leave_total: 20,
    leave_balance: 20.00,
    provident_employee_rate: null as number | null,
    provident_employer_rate: null as number | null,
    union_rate: 0,
    union_type: null as string | null,
    use_holiday_fund: false,
    holiday_rate: null as number | null,
});

const submit = () => {
    if (editingId.value) {
        form.put(route('staff-members.update', editingId.value), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('staff-members.store'), {
            onSuccess: () => resetForm(),
        });
    }
};

const edit = (member: any) => {
    editingId.value = member.id;
    form.name = member.name;
    form.email = member.email || '';
    form.phone = member.phone || '';
    form.position = member.position || '';
    form.base_salary = member.base_salary;
    form.id_number = member.id_number || '';
    form.si_number = member.si_number || '';
    form.tax_id = member.tax_id || '';
    form.iban = member.iban || '';
    form.joining_date = member.joining_date || '';
    form.emergency_contact_name = member.emergency_contact_name || '';
    form.emergency_contact_phone = member.emergency_contact_phone || '';
    form.annual_leave_total = member.annual_leave_total || 20;
    form.leave_balance = member.leave_balance || 20.00;
    form.provident_employee_rate = member.provident_employee_rate;
    form.provident_employer_rate = member.provident_employer_rate;
    form.union_rate = member.union_rate || 0;
    form.union_type = member.union_type;
    form.use_holiday_fund = member.use_holiday_fund === 1 || member.use_holiday_fund === true;
    form.holiday_rate = member.holiday_rate;
    isAdding.value = true;
};

const resetForm = () => {
    form.reset();
    isAdding.value = false;
    editingId.value = null;
};

const deleteMember = (id: number) => {
    if (confirm('Are you sure you want to remove this staff member?')) {
        form.delete(route('staff-members.destroy', id));
    }
};
</script>

<template>
    <Head title="Staff Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="text-xl font-bold text-slate-800">Staff Management</h2>
                <button 
                    @click="isAdding = true" 
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors"
                >
                    Add Staff Member
                </button>
            </div>
        </template>

        <div class="max-w-5xl mx-auto pt-8 px-4 space-y-6">
            <!-- Subscription Capacity Tracker -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center justify-between shadow-sm overflow-hidden relative group transition-all hover:shadow-md">
                <div class="flex items-center gap-6">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center border-2" :class="[usagePercentage >= 100 ? 'border-rose-100 bg-rose-50 text-rose-600' : 'border-indigo-100 bg-indigo-50 text-indigo-600']">
                         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2"></path></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-tight">Personnel Capacity</h3>
                            <span :class="[workspace?.tier === 'enterprise' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700']" class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border border-current/10">
                                {{ workspace?.tier === 'starter' ? 'Freelancer' : workspace?.tier === 'professional' ? 'Small Biz' : 'Enterprise' }}
                            </span>
                        </div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-1">{{ staffCount }} / {{ maxStaff >= 999999 ? '∞' : maxStaff }} Slot Occupancy</p>
                    </div>
                </div>
                
                <div class="flex flex-col items-end gap-2 text-right">
                    <div class="flex items-center gap-4">
                        <div class="space-y-1">
                             <div class="flex justify-between text-[10px] font-black uppercase tracking-widest" :class="[usagePercentage >= 100 ? 'text-rose-500' : 'text-slate-400']">
                                 <span>{{ Math.round(usagePercentage) }}% Load</span>
                                 <span v-if="workspace?.tier !== 'enterprise'" class="ml-4">{{ remainingSlots }} remaining</span>
                             </div>
                             <div class="w-[200px] h-2 bg-slate-100 rounded-full overflow-hidden">
                                 <div 
                                    class="h-full rounded-full transition-all duration-1000" 
                                    :class="[usagePercentage >= 90 ? 'bg-rose-500' : usagePercentage >= 70 ? 'bg-amber-500' : 'bg-indigo-600']"
                                    :style="`width: ${Math.min(100, usagePercentage)}%`"
                                 ></div>
                             </div>
                        </div>
                    </div>
                    <p v-if="usagePercentage >= 90 && workspace?.tier !== 'enterprise'" class="text-[9px] font-bold text-rose-500 uppercase tracking-widest animate-pulse">Critical Load reached: Upgrade Required</p>
                    <p v-else-if="workspace?.tier !== 'enterprise'" class="text-[9px] font-bold text-slate-400 uppercase tracking-widest italic opacity-60">Workspace limit is strictly enforced based on tier</p>
                </div>

                <div class="absolute -bottom-1 -right-1 w-24 h-24 text-slate-50 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none transform translate-y-4 group-hover:translate-y-0 duration-700">
                    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path></svg>
                </div>
            </div>

            <!-- Modal for Adding/Editing -->
            <div v-if="isAdding" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-sm">
                <div class="bg-white rounded-2xl w-full max-w-2xl p-8 shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">{{ editingId ? 'Update Profile' : 'New Staff Profile' }}</h3>
                        <button @click="resetForm" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2"></path></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-8">
                        <FormErrorSummary :errors="form.errors" />

                        <!-- Basic Information -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em]">Personal Details</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Full Identity Name</label>
                                    <input v-model="form.name" type="text" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all', form.errors.name)" required />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Email Address</label>
                                    <input v-model="form.email" type="email" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all', form.errors.email)" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Phone Number</label>
                                    <input v-model="form.phone" type="text" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all', form.errors.phone)" />
                                </div>
                            </div>
                        </div>

                        <!-- Legal & Financial -->
                        <div class="space-y-4 pt-4 border-t border-slate-100">
                            <h4 class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em]">Compliance & Financial</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">SI Number</label>
                                    <input v-model="form.si_number" type="text" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all', form.errors.si_number)" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tax ID (TIC)</label>
                                    <input v-model="form.tax_id" type="text" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all', form.errors.tax_id)" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Passport / ID #</label>
                                    <input v-model="form.id_number" type="text" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all', form.errors.id_number)" />
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">IBAN (Bank Account)</label>
                                    <input v-model="form.iban" type="text" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-mono outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all', form.errors.iban)" placeholder="CY..." />
                                </div>
                            </div>
                        </div>

                        <!-- Role & Leave -->
                        <div class="space-y-4 pt-4 border-t border-slate-100">
                            <h4 class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em]">Role & HR Metrics</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Position / Title</label>
                                    <input v-model="form.position" type="text" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm font-bold outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all', form.errors.position)" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Monthly Gross Salary (€)</label>
                                    <input v-model.number="form.base_salary" type="number" step="0.01" :class="withValidation('w-full bg-slate-900 border-none rounded-xl py-2.5 px-4 text-sm font-black text-white outline-none focus:ring-4 focus:ring-indigo-500/20 transition-all', form.errors.base_salary)" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Joining Date</label>
                                    <input v-model="form.joining_date" type="date" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all', form.errors.joining_date)" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Annual Leave Limit</label>
                                        <input v-model.number="form.annual_leave_total" type="number" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all', form.errors.annual_leave_total)" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Current Balance</label>
                                        <input v-model.number="form.leave_balance" type="number" step="0.5" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all', form.errors.leave_balance)" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Provident Fund (Employee %)</label>
                                    <input v-model.number="form.provident_employee_rate" type="number" step="0.01" placeholder="Workspace Default" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold text-indigo-600', form.errors.provident_employee_rate)" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Provident Fund (Employer %)</label>
                                    <input v-model.number="form.provident_employer_rate" type="number" step="0.01" placeholder="Workspace Default" :class="withValidation('w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold text-indigo-600', form.errors.provident_employer_rate)" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Trade Union Membership</label>
                                    <div class="flex gap-3">
                                        <select v-model="form.union_type" class="flex-grow bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none focus:ring-4 focus:ring-indigo-500/10 transition-all">
                                            <option :value="null">None / Not Member</option>
                                            <option value="PEO">PEO (Pancyprian Federation of Labour)</option>
                                            <option value="SEK">SEK (Cyprus Workers Confederation)</option>
                                            <option value="OTHER">Other Union</option>
                                        </select>
                                        <div class="w-24">
                                            <input v-model.number="form.union_rate" type="number" step="0.01" placeholder="Rate %" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-sm outline-none" />
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 pt-2">
                                    <button type="button" @click="form.use_holiday_fund = !form.use_holiday_fund" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="form.use_holiday_fund ? 'bg-indigo-600' : 'bg-slate-200'">
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="form.use_holiday_fund ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                    <div class="flex-grow flex items-center gap-3">
                                        <div>
                                            <p class="text-[10px] font-black text-slate-700 uppercase tracking-tight">Holiday Fund Applicable</p>
                                            <p class="text-[9px] text-slate-400 font-bold uppercase">Sector-specific fund</p>
                                        </div>
                                        <div v-if="form.use_holiday_fund" class="ml-auto w-24">
                                            <input v-model.number="form.holiday_rate" type="number" step="0.01" placeholder="Rate %" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm outline-none font-bold text-indigo-600" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-4 pt-6">
                            <button type="button" @click="resetForm" class="flex-grow bg-white border border-slate-200 text-slate-500 font-black py-3 rounded-xl text-[10px] uppercase tracking-widest hover:bg-slate-50 transition-all">Cancel</button>
                            <button type="submit" :disabled="form.processing" class="flex-grow bg-indigo-600 text-white font-black py-3 rounded-xl text-[10px] uppercase tracking-[0.2em] hover:bg-indigo-700 shadow-xl shadow-indigo-500/20 transition-all disabled:opacity-50">
                                {{ editingId ? 'Save Profile Updates' : 'Confirm New Hire' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden text-sm">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Team Member</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Contact & ID</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Job Details</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Leave Balance</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Base Salary</th>
                            <th class="px-6 py-5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="member in staff" :key="member.id" class="hover:bg-indigo-50/20 transition-all group">
                            <td class="px-6 py-5">
                                <Link :href="route('staff-members.show', member.id)" class="flex items-center gap-3 group">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-black text-xs uppercase group-hover:bg-indigo-200 transition-all">
                                        {{ member.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900 text-sm group-hover:text-indigo-600 transition-colors">{{ member.name }}</div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">Joined {{ formatDate(member.joining_date) }}</div>
                                    </div>
                                </Link>
                            </td>
                            <td class="px-6 py-5">
                                <div class="space-y-0.5">
                                    <div class="text-[11px] font-bold text-slate-600 flex items-center gap-1.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2"></path></svg>
                                        {{ member.email || 'No Email' }}
                                    </div>
                                    <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4" stroke-width="2"></path></svg>
                                        ID: {{ member.id_number || '—' }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest border border-slate-200">
                                    {{ member.position || 'Employee' }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2">
                                    <div class="w-full bg-slate-100 h-1.5 rounded-full max-w-[60px] overflow-hidden">
                                        <div class="bg-emerald-500 h-full rounded-full" :style="{ width: Math.min((member.leave_balance / (member.annual_leave_total || 1)) * 100, 100) + '%' }"></div>
                                    </div>
                                    <span class="text-[11px] font-black text-emerald-600 tabular-nums">
                                        {{ member.leave_balance }}d
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-right font-black text-slate-900 tabular-nums text-sm">
                                €{{ formatMoney(member.base_salary) }}
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex gap-4 justify-end">
                                    <button @click="edit(member)" class="text-indigo-600 hover:text-indigo-800 font-black text-[10px] uppercase tracking-[0.2em] transition-all">Update</button>
                                    <button @click="deleteMember(member.id)" class="text-rose-400 hover:text-rose-600 font-black text-[10px] uppercase tracking-[0.2em] transition-all">Remove</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="staff.length === 0">
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2"></path></svg>
                                    </div>
                                    <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">No staff members assigned to this workspace</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
