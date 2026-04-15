<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';

const props = defineProps<{
    staff_members: any[],
    workspace: any
}>();

const form = useForm({
    category: 'other',
    amount: 0,
    total_gross: 0,
    vat_amount: 0,
    expense_date: new Date().toISOString().split('T')[0],
    reminder_time: '',
    vendor_name: '',
    receipt_file: null as File | null,
    notes: '',
    
    // Payroll fields
    staff_member_id: null as number | null,
    is_payroll: false,
    use_13th_month: false,
    gross_salary: 0,
    si_employee: 0,
    si_employer: 0,
    gesi_employee: 0,
    gesi_employer: 0,
    tax_employee: 0,
    provident_employee: 0,
    provident_employer: 0,
    redundancy_amount: 0,
    training_amount: 0,
    cohesion_amount: 0,
    holiday_amount: 0,
    union_amount: 0,
    net_payable: 0
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

const onStaffSelect = (e: Event) => {
    const target = e.target as HTMLSelectElement;
    const staffId = parseInt(target.value);
    const member = props.staff_members.find(s => s.id === staffId);
    if (member) {
        form.staff_member_id = member.id;
        form.vendor_name = member.name;
        form.gross_salary = parseFloat(member.base_salary || 2500);
        
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

const onGrossChange = () => {
    if (form.category === 'staff_wages') {
        calcPayroll();
    } else {
        form.amount = Number((Number(form.total_gross) - Number(form.vat_amount)).toFixed(2));
    }
};

const submit = () => {
    form.post(route('expenses.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create Expense" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Record New Expense</h2>
                    <p class="text-xs text-slate-400 mt-1 font-medium">Log your business expenditures and automate payroll liabilities</p>
                </div>
                <div class="flex gap-3">
                    <button @click="submit" :disabled="form.processing" class="bg-slate-900 text-white px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/20 disabled:opacity-50">
                        {{ form.processing ? 'Recording...' : 'Save Expense' }}
                    </button>
                    <Link :href="route('expenses.index')" class="bg-white border border-slate-200 text-slate-700 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-50 transition-all">
                        Cancel
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-7xl pt-8 px-4 pb-20 mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Main Form Column -->
                <div :class="form.category === 'staff_wages' && form.staff_member_id ? 'lg:col-span-8' : 'lg:col-span-12'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition-all duration-500">
                    <div class="p-6 border-b border-slate-50 bg-slate-50/30">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Core Transaction Data</h3>
                    </div>
                    <div class="p-8">
                        <form @submit.prevent="submit" class="space-y-8">
                            <FormErrorSummary :errors="form.errors" />

                            <!-- Category & Date -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Expense Category</label>
                                    <select v-model="form.category" :class="withValidation('w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 px-4 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all font-bold text-sm text-slate-700 capitalize cursor-pointer', form.errors.category)">
                                        <option value="fuel">Fuel & Logistics</option>
                                        <option value="staff_wages">Staff Wages (Payroll)</option>
                                        <option value="sub_rental">Sub-Rental</option>
                                        <option value="marketing">Marketing</option>
                                        <option value="utility">Utility / Bills</option>
                                        <option value="other">Other Business Expense</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Transaction Date</label>
                                    <input v-model="form.expense_date" type="date" :class="withValidation('w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 px-4 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all font-bold text-sm text-slate-700', form.errors.expense_date)" required />
                                </div>
                            </div>

                            <!-- Payroll Interface -->
                            <div v-if="form.category === 'staff_wages'" class="bg-emerald-50/40 rounded-[2rem] border border-emerald-100 p-8 space-y-8 shadow-inner-sm animate-in zoom-in-95 duration-300">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-700 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2.5"></path></svg>
                                        </div>
                                        <div>
                                            <h3 class="text-xs font-black uppercase text-emerald-900 tracking-widest leading-none">Cyprus Payroll Engine</h3>
                                            <p class="text-[9px] text-emerald-600 font-bold uppercase mt-1.5 opacity-70">Automated tax & contribution logic v2026</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 bg-white/50 px-4 py-2 rounded-2xl border border-emerald-100/50 shadow-sm">
                                        <span class="text-[9px] uppercase font-black text-emerald-700 tracking-tight">Include 13th month</span>
                                        <button type="button" @click="form.use_13th_month = !form.use_13th_month; calcPayroll()" class="relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="form.use_13th_month ? 'bg-emerald-600' : 'bg-slate-200'">
                                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out" :class="form.use_13th_month ? 'translate-x-5' : 'translate-x-0'"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-black text-emerald-800 uppercase tracking-widest mb-3 ml-1">Select Personnel</label>
                                        <select 
                                            v-model="form.staff_member_id" 
                                            @change="onStaffSelect"
                                            :class="withValidation('w-full rounded-2xl border border-emerald-200 bg-white py-3.5 px-5 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-black text-sm text-emerald-900 shadow-sm', form.errors.staff_member_id)"
                                        >
                                            <option :value="null">-- SEARCH STAFF DATABASE --</option>
                                            <option v-for="member in staff_members" :key="member.id" :value="member.id">{{ member.name }} (€{{ member.base_salary }})</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-emerald-800 uppercase tracking-widest mb-3 ml-1">Gross Salary (€)</label>
                                        <div class="relative">
                                            <input v-model.number="form.gross_salary" @input="onGrossChange" type="number" step="0.01" :class="withValidation('w-full rounded-2xl border border-emerald-200 bg-white py-3.5 px-6 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-black text-xl text-emerald-900 shadow-sm tabular-nums', form.errors.gross_salary)" />
                                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-emerald-300 font-black">€</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Deductions -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 border border-emerald-100 shadow-sm transition-all hover:scale-[1.02] hover:shadow-md group">
                                        <div class="text-[9px] font-black text-slate-400 uppercase mb-1.5 tracking-tighter opacity-70 group-hover:opacity-100 transition-opacity">Social Insurance (EE)</div>
                                        <div class="text-lg font-black text-rose-500 tabular-nums leading-none">-€{{ form.si_employee.toLocaleString() }}</div>
                                    </div>
                                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 border border-emerald-100 shadow-sm transition-all hover:scale-[1.02] hover:shadow-md group">
                                        <div class="text-[9px] font-black text-slate-400 uppercase mb-1.5 tracking-tighter opacity-70 group-hover:opacity-100 transition-opacity">GESI (EE)</div>
                                        <div class="text-lg font-black text-rose-500 tabular-nums leading-none">-€{{ form.gesi_employee.toLocaleString() }}</div>
                                    </div>
                                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 border border-emerald-100 shadow-sm transition-all hover:scale-[1.02] hover:shadow-md group">
                                        <div class="text-[9px] font-black text-slate-400 uppercase mb-1.5 tracking-tighter opacity-70 group-hover:opacity-100 transition-opacity">Income Tax (PAYE)</div>
                                        <div class="text-lg font-black text-rose-500 tabular-nums leading-none">-€{{ form.tax_employee.toLocaleString() }}</div>
                                    </div>
                                    <div v-if="form.provident_employee > 0" class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 border border-emerald-100 shadow-sm transition-all hover:scale-[1.02] hover:shadow-md group">
                                        <div class="text-[9px] font-black text-slate-400 uppercase mb-1.5 tracking-tighter opacity-70 group-hover:opacity-100 transition-opacity">Provident Fund (EE)</div>
                                        <div class="text-lg font-black text-rose-500 tabular-nums leading-none">-€{{ form.provident_employee.toLocaleString() }}</div>
                                    </div>
                                    <div v-if="form.union_amount > 0" class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 border border-emerald-100 shadow-sm transition-all hover:scale-[1.02] hover:shadow-md group">
                                        <div class="text-[9px] font-black text-slate-400 uppercase mb-1.5 tracking-tighter opacity-70 group-hover:opacity-100 transition-opacity">Union Fee</div>
                                        <div class="text-lg font-black text-rose-500 tabular-nums leading-none">-€{{ form.union_amount.toLocaleString() }}</div>
                                    </div>
                                    <div :class="form.provident_employee > 0 || form.union_amount > 0 ? 'md:col-span-2' : ''" class="bg-indigo-900 rounded-2xl p-4 shadow-xl shadow-indigo-900/20 border border-indigo-800 flex flex-col justify-between transition-all hover:scale-[1.05]">
                                        <div class="text-[9px] font-black text-emerald-300 uppercase mb-1 tracking-widest opacity-80">Net Payable</div>
                                        <div class="text-xl font-black text-white tabular-nums leading-none">€{{ form.net_payable.toLocaleString() }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-px bg-emerald-200/50 rounded-3xl overflow-hidden border border-emerald-100 shadow-sm">
                                    <div class="md:col-span-7 bg-white/60 p-6">
                                        <div class="text-[9px] font-black text-emerald-800 uppercase tracking-widest mb-4 opacity-60">Employer Contributions (Liabilities)</div>
                                        <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-[11px] font-bold">
                                            <div class="flex justify-between border-b border-emerald-100/50 pb-1.5 hover:border-emerald-300 transition-colors">
                                                <span class="text-slate-500">Social Insurance</span>
                                                <span class="text-slate-900">€{{ form.si_employer.toLocaleString() }}</span>
                                            </div>
                                            <div class="flex justify-between border-b border-emerald-100/50 pb-1.5 hover:border-emerald-300 transition-colors">
                                                <span class="text-slate-500">GESY Contribution</span>
                                                <span class="text-slate-900">€{{ form.gesi_employer.toLocaleString() }}</span>
                                            </div>
                                            <div class="flex justify-between border-b border-emerald-100/50 pb-1.5 hover:border-emerald-300 transition-colors">
                                                <span class="text-slate-500">Social Cohesion</span>
                                                <span class="text-slate-900">€{{ form.cohesion_amount.toLocaleString() }}</span>
                                            </div>
                                            <div class="flex justify-between border-b border-emerald-100/50 pb-1.5 hover:border-emerald-300 transition-colors">
                                                <span class="text-slate-500">Redundancy & Tr.</span>
                                                <span class="text-slate-900">€{{ (Number(form.redundancy_amount) + Number(form.training_amount)).toFixed(2) }}</span>
                                            </div>
                                            <div v-if="form.provident_employer > 0" class="flex justify-between border-b border-emerald-100/50 pb-1.5 hover:border-emerald-300 transition-colors">
                                                <span class="text-slate-500">Provident Fund ER</span>
                                                <span class="text-slate-900">€{{ form.provident_employer.toLocaleString() }}</span>
                                            </div>
                                            <div v-if="form.holiday_amount > 0" class="flex justify-between border-b border-emerald-100/50 pb-1.5 hover:border-emerald-300 transition-colors">
                                                <span class="text-slate-500">Holiday Fund</span>
                                                <span class="text-slate-900">€{{ form.holiday_amount.toLocaleString() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="md:col-span-5 bg-emerald-950 p-6 text-white flex flex-col justify-center relative overflow-hidden group">
                                        <div class="absolute -right-2 -bottom-2 opacity-10 group-hover:scale-110 transition-transform duration-1000">
                                            <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"></path></svg>
                                        </div>
                                        <div class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400">Total Employer Cost</div>
                                        <div class="text-3xl font-black tabular-nums mt-1 leading-none">€{{ form.total_gross.toLocaleString() }}</div>
                                        <p class="text-[8px] font-bold text-emerald-500 uppercase mt-3 tracking-wide opacity-80 leading-relaxed">Full monthly liability including all statutory funds & gross salary.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-6">
                                <div class="relative group">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1 group-focus-within:text-indigo-600 transition-colors">Vendor Reference</label>
                                    <input v-model="form.vendor_name" type="text" :class="withValidation('w-full rounded-xl border border-slate-200 py-3.5 px-4 text-sm font-bold shadow-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all bg-slate-50/30', form.errors.vendor_name)" placeholder="Entity being paid..." />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">VAT Amount (€)</label>
                                    <div class="relative">
                                        <input v-model.number="form.vat_amount" @input="onGrossChange" type="number" step="0.01" :class="withValidation('w-full rounded-xl border border-slate-200 py-3.5 px-4 text-sm font-bold shadow-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all bg-slate-50/30 tabular-nums', form.errors.vat_amount)" />
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 font-bold">€</div>
                                    </div>
                                </div>
                                <div v-if="form.category !== 'staff_wages'">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Net Base Amount (€)</label>
                                    <div class="relative">
                                        <input v-model.number="form.amount" @input="onGrossChange" type="number" step="0.01" :class="withValidation('w-full rounded-xl border border-slate-200 py-3.5 px-4 text-sm font-bold shadow-sm outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all bg-slate-50/30 tabular-nums', form.errors.amount)" />
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 font-bold">€</div>
                                    </div>
                                </div>
                                <div v-if="form.category !== 'staff_wages'">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Total Transaction Value (€)</label>
                                    <div class="relative">
                                        <input v-model.number="form.total_gross" @input="onGrossChange" type="number" step="0.01" :class="withValidation('w-full rounded-xl border border-slate-900 py-3.5 px-4 text-sm font-black text-white bg-slate-900 outline-none focus:ring-8 focus:ring-indigo-500/10 transition-all shadow-xl shadow-slate-900/10 tabular-nums', form.errors.total_gross)" />
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-600 font-black">€</div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-50">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Contextual Notes</label>
                                <textarea v-model="form.notes" rows="3" :class="withValidation('w-full rounded-2xl border border-slate-200 bg-slate-50/20 py-4 px-5 text-sm font-medium outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all resize-none shadow-sm', form.errors.notes)" placeholder="Additional details for auditing..."></textarea>
                            </div>

                            <div class="pt-4">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Digital Evidence (Receipt / Invoice)</label>
                                <div class="relative border-2 border-dashed border-slate-100 rounded-2xl p-6 hover:border-slate-200 transition-colors flex items-center justify-center bg-slate-50/20 group">
                                    <input type="file" @input="form.receipt_file = ($event.target as HTMLInputElement).files?.[0] || null" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                    <div class="text-center">
                                        <svg class="w-8 h-8 text-slate-300 mx-auto mb-2 group-hover:text-slate-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Upload File <span class="text-slate-300 tracking-normal font-normal ml-1">(JPG, PNG, PDF up to 5MB)</span></p>
                                        <p v-if="form.receipt_file" class="text-[10px] font-black text-indigo-600 mt-2">SELECTED: {{ form.receipt_file?.name }}</p>
                                    </div>
                                </div>
                                <FormErrorSummary :errors="{receipt_file: form.errors.receipt_file}" />
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Staff Profile Context Sidebar -->
                <div v-if="form.category === 'staff_wages' && form.staff_member_id" class="lg:col-span-4 space-y-6 sticky top-8 animate-in slide-in-from-right duration-500">
                    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden border-t-8 border-t-indigo-600">
                        <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
                            <div>
                                <h3 class="text-[11px] font-black uppercase text-slate-500 tracking-widest leading-none">Master Profile Sync</h3>
                                <p class="text-[9px] text-slate-400 font-bold uppercase mt-1.5 leading-none">Central Record Management</p>
                            </div>
                            <div class="w-10 h-10 rounded-2xl bg-indigo-600 flex items-center justify-center text-white text-sm font-black shadow-lg shadow-indigo-600/20">
                                {{ staffForm.name ? staffForm.name.charAt(0) : '?' }}
                            </div>
                        </div>
                        <div class="p-8 space-y-6">
                            <div>
                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-2 ml-1 tracking-[0.1em]">Provident Fund Allocation</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="relative group">
                                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[8px] font-black text-slate-400 group-focus-within:text-indigo-600 transition-colors">EE %</div>
                                        <input v-model.number="staffForm.provident_employee_rate" type="number" step="0.01" class="w-full bg-slate-100/50 border border-slate-100 rounded-xl py-2 pl-9 pr-3 text-xs font-black focus:ring-4 focus:ring-indigo-500/10 focus:bg-white focus:border-indigo-500 outline-none transition-all tabular-nums text-slate-700" />
                                    </div>
                                    <div class="relative group">
                                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[8px] font-black text-slate-400 group-focus-within:text-indigo-600 transition-colors">ER %</div>
                                        <input v-model.number="staffForm.provident_employer_rate" type="number" step="0.01" class="w-full bg-slate-100/50 border border-slate-100 rounded-xl py-2 pl-9 pr-3 text-xs font-black focus:ring-4 focus:ring-indigo-500/10 focus:bg-white focus:border-indigo-500 outline-none transition-all tabular-nums text-slate-700" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-2 ml-1 tracking-[0.1em]">Union Fee Management</label>
                                <div class="flex gap-3">
                                    <select v-model="staffForm.union_type" class="flex-grow bg-slate-100/50 border border-slate-100 rounded-xl py-2 px-4 text-xs font-black focus:ring-4 focus:ring-indigo-500/10 focus:bg-white focus:border-indigo-500 outline-none transition-all uppercase text-slate-700 cursor-pointer">
                                        <option :value="null">NONE</option>
                                        <option value="PEO">PEO (1.0%)</option>
                                        <option value="SEK">SEK (1.0%)</option>
                                        <option value="OTHER">OTHER</option>
                                    </select>
                                    <div class="relative w-24 group">
                                        <input v-model.number="staffForm.union_rate" type="number" step="0.01" class="w-full bg-slate-100/50 border border-slate-100 rounded-xl py-2 px-4 text-xs font-black focus:ring-4 focus:ring-indigo-500/10 focus:bg-white focus:border-indigo-500 outline-none transition-all tabular-nums text-slate-700" />
                                        <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[8px] font-black text-slate-400">%</div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-2 ml-1 tracking-[0.1em]">Default Base Salary (€)</label>
                                <div class="relative group">
                                    <input v-model.number="staffForm.base_salary" type="number" step="1" class="w-full bg-slate-100/50 border border-slate-100 rounded-xl py-2 px-4 text-xs font-black focus:ring-4 focus:ring-indigo-500/10 focus:bg-white focus:border-indigo-500 outline-none transition-all tabular-nums text-slate-700" />
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 font-black text-[10px]">€</div>
                                </div>
                            </div>

                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 group transition-all hover:bg-white hover:shadow-md hover:border-slate-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <button type="button" @click="staffForm.use_holiday_fund = !staffForm.use_holiday_fund" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none shadow-inner-sm" :class="staffForm.use_holiday_fund ? 'bg-indigo-600' : 'bg-slate-200'">
                                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out" :class="staffForm.use_holiday_fund ? 'translate-x-5' : 'translate-x-0'"></span>
                                        </button>
                                        <span class="text-[9px] font-black uppercase text-slate-600 tracking-wider">Holiday Fund</span>
                                    </div>
                                    <div v-if="staffForm.use_holiday_fund" class="relative w-20 animate-in fade-in slide-in-from-right-2">
                                        <input v-model.number="staffForm.holiday_rate" type="number" step="0.01" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-[10px] font-black focus:ring-4 focus:ring-indigo-500/10 outline-none border-indigo-200" />
                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 text-[8px] font-black text-indigo-400">%</div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6">
                                <button type="button" @click="updateStaffFromPanel" :disabled="staffForm.processing" class="w-full bg-slate-900 text-white rounded-2xl py-4 text-[10px] font-black uppercase tracking-[0.2em] hover:bg-indigo-600 transition-all shadow-xl shadow-slate-900/10 disabled:opacity-50 flex items-center justify-center gap-3 active:scale-95 group/btn">
                                    <svg v-if="staffForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span class="group-hover/btn:scale-105 transition-transform">{{ staffForm.processing ? 'SYNCING DATABASE...' : 'SAVE MASTER PROFILE' }}</span>
                                </button>
                                <div class="mt-4 flex items-center gap-3 px-2">
                                    <div class="flex-grow h-px bg-slate-100"></div>
                                    <span class="text-[7px] font-black text-slate-300 uppercase tracking-widest whitespace-nowrap">Integrated Record Control</span>
                                    <div class="flex-grow h-px bg-slate-100"></div>
                                </div>
                                <p class="text-[8px] text-center text-slate-400 font-bold uppercase mt-3 px-3 leading-relaxed opacity-70">Saves directly to personnel records. Changes will instantly propagate to all pending calculations.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-indigo-600 rounded-[2rem] p-6 text-white shadow-2xl shadow-indigo-600/30 relative overflow-hidden group border border-indigo-500/50">
                        <div class="absolute -right-6 -bottom-6 opacity-20 group-hover:scale-110 transition-transform duration-1000 rotate-12">
                            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"></path></svg>
                        </div>
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center">
                                <span class="text-[10px] font-bold">!</span>
                            </div>
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-100">Workflow Note</h4>
                        </div>
                        <p class="text-[11px] font-medium leading-relaxed opacity-95">Changes made here are permanent. For monthly exceptions (overtime/unpaid leave), use the **Gross Salary** input on the left.</p>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
