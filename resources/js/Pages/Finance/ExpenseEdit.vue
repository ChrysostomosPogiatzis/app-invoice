<script setup lang="ts">
import { onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';

const props = defineProps<{
    expense: any;
    workspace: any;
    staff_members: any[];
}>();

const form = useForm({
    category: props.expense.category,
    amount: props.expense.amount,
    total_gross: Number(props.expense.amount) + Number(props.expense.vat_amount || 0) + Number(props.expense.si_employer || 0) + Number(props.expense.gesi_employer || 0),
    vat_amount: props.expense.vat_amount ?? 0,
    expense_date: props.expense.expense_date ? props.expense.expense_date.substring(0, 10) : '',
    reminder_time: props.expense.reminder_time ? props.expense.reminder_time.substring(0, 5) : '',
    vendor_name: props.expense.vendor_name || '',
    receipt_file: null as File | null,
    notes: props.expense.notes || '',

    // Payroll fields
    staff_member_id: props.expense.staff_member_id,
    is_payroll: props.expense.is_payroll === 1 || props.expense.is_payroll === true,
    use_13th_month: false,
    gross_salary: props.expense.gross_salary || props.expense.amount,
    si_employee: props.expense.si_employee || 0,
    si_employer: props.expense.si_employer || 0,
    gesi_employee: props.expense.gesi_employee || 0,
    gesi_employer: props.expense.gesi_employer || 0,
    tax_employee: props.expense.tax_employee || 0,
    provident_employee: props.expense.provident_employee || 0,
    provident_employer: props.expense.provident_employer || 0,
    redundancy_amount: props.expense?.redundancy_amount || 0,
    training_amount: props.expense?.training_amount || 0,
    cohesion_amount: props.expense?.cohesion_amount || 0,
    holiday_amount: props.expense?.holiday_amount || 0,
    union_amount: props.expense.union_amount || 0,
    net_payable: props.expense.net_payable || 0
});

const staffForm = useForm({
    name: '',
    base_salary: 0,
    provident_employee_rate: 0,
    provident_employer_rate: 0,
    union_type: null as string | null,
    union_rate: 0,
    use_holiday_fund: false,
    holiday_rate: null as number | null,
});

const withValidation = (classes: string, error: any) => {
    return `${classes} ${error ? 'border-rose-500 bg-rose-50' : ''}`;
};

const calcPayroll = () => {
    const gross = Number(form.gross_salary);
    if (!gross || gross <= 0) return;

    form.is_payroll = true;
    const months = form.use_13th_month ? 13 : 12;
    const w = props.workspace;

    // 1. Social Insurance (Capped)
    const siCap = parseFloat(w.si_monthly_cap || 5546);
    const insurableAmount = Math.min(gross, siCap);

    form.si_employee = Number((insurableAmount * (w.si_employee_rate / 100)).toFixed(2));
    form.si_employer = Number((insurableAmount * (w.si_employer_rate / 100)).toFixed(2));

    // 2. GESI (Uncapped)
    form.gesi_employee = Number((gross * (w.gesi_employee_rate / 100)).toFixed(2));
    form.gesi_employer = Number((gross * (w.gesi_employer_rate / 100)).toFixed(2));

    // 3. Provident Fund & Union Fees
    const member = props.staff_members.find(s => s.id === form.staff_member_id);
    const pEmployeeRate = member?.provident_employee_rate ?? w.provident_employee_rate ?? 0;
    const pEmployerRate = member?.provident_employer_rate ?? w.provident_employer_rate ?? 0;
    const unionRate = member?.union_rate ?? 0;

    form.provident_employee = Number((gross * (pEmployeeRate / 100)).toFixed(2));
    form.provident_employer = Number((gross * (pEmployerRate / 100)).toFixed(2));
    form.union_amount = Number((gross * (unionRate / 100)).toFixed(2));

    // 4. Extra Employer Contributions
    const redundancyRate = w.redundancy_rate ?? 1.20;
    const trainingRate = w.training_rate ?? 0.50;
    const cohesionRate = w.cohesion_rate ?? 2.00;
    const holidayRate = member?.use_holiday_fund ? (member.holiday_rate ?? w.holiday_rate ?? 8.0) : 0;

    form.redundancy_amount = Number((insurableAmount * (redundancyRate / 100)).toFixed(2));
    form.training_amount = Number((insurableAmount * (trainingRate / 100)).toFixed(2));
    form.cohesion_amount = Number((gross * (cohesionRate / 100)).toFixed(2));
    form.holiday_amount = Number((insurableAmount * (holidayRate / 100)).toFixed(2));

    // 5. Projected Annual Taxable Income
    const annualGross = gross * months;
    const annualSI = form.si_employee * months;
    const annualGESI = form.gesi_employee * months;
    const annualProvident = form.provident_employee * months;
    const annualTaxable = annualGross - annualSI - annualGESI - annualProvident;

    // 6. Progressive Tax Brackets (Cyprus 2026 Standard)
    let annualTax = 0;
    const brackets = w.tax_brackets || [
        {threshold: 0, rate: 0},
        {threshold: 22000, rate: 20},
        {threshold: 32000, rate: 25},
        {threshold: 42000, rate: 30},
        {threshold: 72000, rate: 35}
    ];

    const sortedBrackets = [...brackets].sort((a, b) => a.threshold - b.threshold);
    for (let i = 0; i < sortedBrackets.length; i++) {
        const current = sortedBrackets[i];
        const next = sortedBrackets[i+1];
        const lower = current.threshold;
        const upper = next ? next.threshold : Infinity;
        const rate = (current.rate || 0) / 100;
        if (annualTaxable > lower) {
            const taxableInThisBracket = Math.min(annualTaxable, upper) - lower;
            annualTax += taxableInThisBracket * rate;
        }
    }

    // 7. Monthly Share & Results
    form.tax_employee = Number((annualTax / months).toFixed(2));
    form.net_payable = Number((gross - form.si_employee - form.gesi_employee - form.tax_employee - form.provident_employee - form.union_amount).toFixed(2));
    form.amount = gross;
    
    // Total gross from accounting perspective (Total Employer Social/Fund obligation)
    form.total_gross = Number((
        gross + 
        Number(form.si_employer) + 
        Number(form.gesi_employer) + 
        Number(form.provident_employer) + 
        Number(form.redundancy_amount) + 
        Number(form.training_amount) + 
        Number(form.cohesion_amount) + 
        Number(form.holiday_amount)
    ).toFixed(2));
};

const onStaffSelect = (e?: Event) => {
    let staffId = form.staff_member_id;
    if (e) {
        const target = e.target as HTMLSelectElement;
        staffId = parseInt(target.value);
    }
    
    const member = props.staff_members.find(s => s.id === staffId);
    if (member) {
        form.staff_member_id = member.id;
        form.gross_salary = member.base_salary;
        
        // Populate staff edit form
        staffForm.name = member.name;
        staffForm.base_salary = member.base_salary;
        staffForm.provident_employee_rate = member.provident_employee_rate;
        staffForm.provident_employer_rate = member.provident_employer_rate;
        staffForm.union_type = member.union_type;
        staffForm.union_rate = member.union_rate;
        staffForm.use_holiday_fund = member.use_holiday_fund === 1 || member.use_holiday_fund === true;
        staffForm.holiday_rate = member.holiday_rate;
        
        calcPayroll();
    }
};

const updateStaffFromPanel = () => {
    if (!form.staff_member_id) return;
    staffForm.put(route('staff-members.update', form.staff_member_id), {
        preserveScroll: true,
        onSuccess: () => {
             // After update, recalculate using the new state from props
             setTimeout(calcPayroll, 200);
        }
    });
};

onMounted(() => {
    if (form.staff_member_id) {
        onStaffSelect();
    }
});

const onTotalGrossChange = () => {
    if (form.category === 'staff_wages') {
        calcPayroll();
    } else {
        form.amount = Number((Number(form.total_gross) - Number(form.vat_amount)).toFixed(2));
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PUT'
    })).post(route('expenses.update', props.expense.id), {
        forceFormData: true,
        onSuccess: () => {},
    });
};
</script>

<template>
    <Head title="Edit Expense" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Edit Expense</h2>
                    <p class="text-sm text-slate-500 mt-1">Updating expense #{{ expense.id }}</p>
                </div>
                <div class="flex gap-3">
                    <button @click="submit" :disabled="form.processing" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <Link :href="route('expenses.index')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors">
                        Cancel
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-7xl pt-8 px-4 pb-20 mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Main Form Column -->
                <div :class="form.category === 'staff_wages' && form.staff_member_id ? 'lg:col-span-8' : 'lg:col-span-12'" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-200">
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Expense Details</h3>
                    </div>
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <FormErrorSummary :errors="form.errors" />

                            <!-- Category & Date -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Category</label>
                                    <select v-model="form.category" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all font-medium text-sm capitalize', form.errors.category)">
                                        <option value="fuel">Fuel & Logistics</option>
                                        <option value="staff_wages">Staff Wages (Payroll)</option>
                                        <option value="sub_rental">Sub-Rental</option>
                                        <option value="marketing">Marketing</option>
                                        <option value="utility">Utility / Bills</option>
                                        <option value="other">Other Business Expense</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Expense Date</label>
                                    <input v-model="form.expense_date" type="date" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all font-medium text-sm', form.errors.expense_date)" required />
                                </div>
                            </div>

                            <!-- Payroll Interface -->
                            <div v-if="form.category === 'staff_wages'" class="bg-emerald-50/50 rounded-2xl border border-emerald-100 p-8 space-y-6 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xs font-black uppercase text-emerald-800 tracking-widest flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"></path></svg>
                                        Cyprus Payroll Calculator 2026
                                    </h3>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] uppercase font-bold text-emerald-600">Include 13th month</span>
                                        <button type="button" @click="form.use_13th_month = !form.use_13th_month; calcPayroll()" class="relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="form.use_13th_month ? 'bg-emerald-600' : 'bg-slate-200'">
                                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="form.use_13th_month ? 'translate-x-5' : 'translate-x-0'"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Staff Member</label>
                                        <select 
                                            v-model="form.staff_member_id" 
                                            @change="onStaffSelect"
                                            :class="withValidation('w-full rounded-lg border border-slate-200 bg-white py-2 px-4 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all font-bold text-sm text-emerald-900', form.errors.staff_member_id)"
                                        >
                                            <option :value="null">-- Choose Staff --</option>
                                            <option v-for="member in staff_members" :key="member.id" :value="member.id">{{ member.name }} (€{{ member.base_salary }})</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Gross Monthly Salary (€)</label>
                                        <input v-model.number="form.gross_salary" @input="calcPayroll" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-white py-2.5 px-4 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all font-black text-lg text-emerald-900', form.errors.gross_salary)" />
                                    </div>
                                </div>

                                <!-- Results Grid -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="bg-white rounded-lg p-3 border border-emerald-100 shadow-sm transition-all hover:border-emerald-300">
                                        <div class="text-[8px] font-bold text-slate-400 uppercase mb-0.5">Social Insurance (EE)</div>
                                        <div class="text-sm font-black text-rose-500 tabular-nums">-€{{ form.si_employee }}</div>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border border-emerald-100 shadow-sm transition-all hover:border-emerald-300">
                                        <div class="text-[8px] font-bold text-slate-400 uppercase mb-0.5">GESY (EE)</div>
                                        <div class="text-sm font-black text-rose-500 tabular-nums">-€{{ form.gesi_employee }}</div>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border border-emerald-100 shadow-sm transition-all hover:border-emerald-300">
                                        <div class="text-[8px] font-bold text-slate-400 uppercase mb-0.5">Income Tax (PAYE)</div>
                                        <div class="text-sm font-black text-rose-500 tabular-nums">-€{{ form.tax_employee }}</div>
                                    </div>
                                    <div class="bg-indigo-900 rounded-2xl p-4 shadow-xl shadow-indigo-900/20 border border-indigo-800 flex flex-col justify-between transition-all hover:scale-[1.05]">
                                        <div class="text-[9px] font-black text-indigo-300 uppercase mb-1 tracking-widest opacity-80">Net Payable</div>
                                        <div class="text-xl font-black text-white tabular-nums leading-none">€{{ form.net_payable.toLocaleString() }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-px bg-emerald-100 rounded-xl overflow-hidden border border-emerald-100">
                                    <div class="bg-white p-4">
                                        <div class="text-[8px] font-bold text-slate-400 uppercase mb-2">Employer Obligations (Added Cost)</div>
                                        <div class="grid grid-cols-2 gap-3 text-[10px]">
                                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                                <span class="text-slate-500 font-medium">SI Employer</span>
                                                <span class="font-bold text-slate-700">€{{ form.si_employer }}</span>
                                            </div>
                                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                                <span class="text-slate-500 font-medium">GESY Employer</span>
                                                <span class="font-bold text-slate-700">€{{ form.gesi_employer }}</span>
                                            </div>
                                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                                <span class="text-slate-500 font-medium">Social Cohesion</span>
                                                <span class="font-bold text-slate-700">€{{ form.cohesion_amount }}</span>
                                            </div>
                                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                                <span class="text-slate-500 font-medium">Redundancy & Tr.</span>
                                                <span class="font-bold text-slate-700">€{{ (Number(form.redundancy_amount) + Number(form.training_amount)).toFixed(2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-emerald-900 p-4 text-white flex flex-col justify-center border-l border-emerald-800">
                                        <div class="text-[9px] font-black uppercase tracking-widest text-emerald-300 opacity-60">Total Employer Liability</div>
                                        <div class="text-2xl font-black tabular-nums mt-1">€{{ form.total_gross }}</div>
                                        <div class="text-[8px] font-bold text-emerald-400 uppercase mt-1 tracking-tighter italic">Including all contributions and gross wages</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                                <div class="relative group">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Vendor / Reference</label>
                                    <input v-model="form.vendor_name" type="text" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm font-medium outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all', form.errors.vendor_name)" placeholder="Company Name / Payee" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">VAT Amount (€)</label>
                                    <input v-model.number="form.vat_amount" @input="onTotalGrossChange" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm font-medium outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all', form.errors.vat_amount)" />
                                </div>
                                <div v-if="form.category !== 'staff_wages'">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Amount (Net) (€)</label>
                                    <input v-model.number="form.amount" @input="onTotalGrossChange" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm font-medium outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all', form.errors.amount)" />
                                </div>
                                <div v-if="form.category !== 'staff_wages'">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Total (Gross) (€)</label>
                                    <input v-model.number="form.total_gross" @input="onTotalGrossChange" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-900 py-2.5 px-4 text-sm font-black text-white bg-slate-900 outline-none focus:ring-4 focus:ring-indigo-500/20 transition-all', form.errors.total_gross)" />
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Description / Notes</label>
                                <textarea v-model="form.notes" rows="4" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm font-medium outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-text', form.errors.notes)" placeholder="Brief record of the expenditure purpose..."></textarea>
                            </div>

                            <div class="pt-4">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Evidence / Receipt (Optional)</label>
                                <input type="file" @input="form.receipt_file = ($event.target as HTMLInputElement).files?.[0] || null" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 transition-all" />
                                <p v-if="form.receipt_file" class="text-[10px] font-black text-indigo-600 mt-2">SELECTED: {{ form.receipt_file?.name }}</p>
                                <FormErrorSummary :errors="{receipt_file: form.errors.receipt_file}" />
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Staff Profile Context Sidebar -->
                <div v-if="form.category === 'staff_wages' && form.staff_member_id" class="lg:col-span-4 space-y-6 sticky top-8 animate-in slide-in-from-right duration-500">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden border-t-4 border-t-indigo-600">
                        <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                            <div>
                                <h3 class="text-[10px] font-black uppercase text-slate-500 tracking-widest leading-none">Master Staff Profile</h3>
                                <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Direct Record Management</p>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-xs font-black">
                                {{ staffForm.name ? staffForm.name.charAt(0) : '?' }}
                            </div>
                        </div>
                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-1.5 ml-1">Provident Fund (EE/ER %)</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="relative">
                                        <div class="absolute left-2 top-1/2 -translate-y-1/2 text-[7px] font-black text-slate-400">EE</div>
                                        <input v-model.number="staffForm.provident_employee_rate" type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-1.5 pl-6 pr-2 text-xs font-bold focus:ring-2 focus:ring-indigo-500/20 outline-none" />
                                    </div>
                                    <div class="relative">
                                        <div class="absolute left-2 top-1/2 -translate-y-1/2 text-[7px] font-black text-slate-400">ER</div>
                                        <input v-model.number="staffForm.provident_employer_rate" type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-1.5 pl-6 pr-2 text-xs font-bold focus:ring-2 focus:ring-indigo-500/20 outline-none" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-1.5 ml-1">Union Membership</label>
                                <div class="flex gap-2">
                                    <select v-model="staffForm.union_type" class="flex-grow bg-slate-50 border border-slate-200 rounded-lg py-1.5 px-3 text-xs font-bold focus:ring-2 focus:ring-indigo-500/20 outline-none uppercase">
                                        <option :value="null">None</option>
                                        <option value="PEO">PEO</option>
                                        <option value="SEK">SEK</option>
                                    </select>
                                    <div class="relative w-24">
                                        <input v-model.number="staffForm.union_rate" type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-1.5 px-3 text-xs font-bold focus:ring-2 focus:ring-indigo-500/20 outline-none" />
                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 text-[8px] font-black text-slate-400">%</div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="staffForm.use_holiday_fund = !staffForm.use_holiday_fund" class="relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="staffForm.use_holiday_fund ? 'bg-indigo-600' : 'bg-slate-200'">
                                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="staffForm.use_holiday_fund ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                    <span class="text-[9px] font-black uppercase text-slate-600">Holiday Fund</span>
                                </div>
                                <div v-if="staffForm.use_holiday_fund" class="relative w-20">
                                    <input v-model.number="staffForm.holiday_rate" type="number" step="0.01" class="w-full bg-white border border-slate-200 rounded py-1 px-1.5 text-[10px] font-black focus:ring-2 focus:ring-indigo-500/20 outline-none" />
                                    <div class="absolute right-1 top-1/2 -translate-y-1/2 text-[7px] font-bold text-slate-400">%</div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-50">
                                <button type="button" @click="updateStaffFromPanel" :disabled="staffForm.processing" class="w-full bg-slate-900 text-white rounded-lg py-3 text-[9px] font-black uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/10 disabled:opacity-50 flex items-center justify-center gap-2">
                                    <svg v-if="staffForm.processing" class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ staffForm.processing ? 'Syncing Profile...' : 'Sync Master Profile' }}
                                </button>
                                <p class="text-[8px] text-center text-slate-400 font-bold uppercase mt-3 px-2 leading-normal italic">Syncing updates the central staff record & refreshes the payroll calculator below.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-indigo-900 rounded-xl p-5 text-white shadow-xl shadow-indigo-900/20 relative overflow-hidden group">
                        <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-700">
                            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"></path></svg>
                        </div>
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-indigo-300">Pro-Tip</h4>
                        <p class="text-[11px] font-medium leading-relaxed mt-2 opacity-90">If this specific month has a one-off bonus or deduction, adjust the **Gross Monthly Salary** on the left instead of the Master Profile.</p>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>