<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    expense: {
        id: number;
        category: string;
        amount: number;
        vat_amount: number | null;
        expense_date: string;
        reminder_time: string | null;
        vendor_name: string | null;
        receipt_url: string | null;
        receipt_download_url?: string | null;
        created_at: string;
        is_payroll: boolean;
        gross_salary: number | null;
        si_employee: number | null;
        si_employer: number | null;
        gesi_employee: number | null;
        gesi_employer: number | null;
        tax_employee: number | null;
        net_payable: number | null;
        provident_employee: number | null;
        provident_employer: number | null;
        union_amount: number | null;
        redundancy_amount: number | null;
        training_amount: number | null;
        cohesion_amount: number | null;
        holiday_amount: number | null;
    }
}>();

const getCategoryStyle = (cat: string) => {
    const styles: Record<string, string> = {
        fuel: 'bg-amber-50 text-amber-700 border-amber-100',
        staff_wages: 'bg-emerald-50 text-emerald-700 border-emerald-100',
        sub_rental: 'bg-indigo-50 text-indigo-700 border-indigo-100',
        marketing: 'bg-rose-50 text-rose-700 border-rose-100',
        utility: 'bg-sky-50 text-sky-700 border-sky-100',
        other: 'bg-slate-50 text-slate-700 border-slate-100'
    };
    return styles[cat] || 'bg-slate-50 text-slate-700 border-slate-100';
};
</script>

<template>
    <Head title="Expense Details" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Expense Details</h2>
                    <p class="text-sm text-slate-500 mt-1">Viewing expense #{{ expense.id }}</p>
                </div>
                <div class="flex gap-3">
                    <Link :href="route('expenses.edit', expense.id)" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                        Edit Expense
                    </Link>
                    <Link :href="route('expenses.index')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors">
                        Back to Expenses
                    </Link>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-200">
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Expense Information</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Category</label>
                                <span :class="getCategoryStyle(expense.category)" class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium border capitalize">
                                    {{ expense.category.replace('_', ' ') }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Amount (Net)</label>
                                <div class="text-xl font-bold text-slate-900">€{{ Number(expense.amount).toFixed(2) }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">VAT Amount</label>
                                <div class="text-xl font-bold text-indigo-600">€{{ Number(expense.vat_amount ?? 0).toFixed(2) }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Total Gross</label>
                                <div class="text-2xl font-bold text-slate-900">€{{ (Number(expense.amount) + Number(expense.vat_amount ?? 0)).toFixed(2) }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Date</label>
                                <div class="text-sm font-medium text-slate-900">{{ new Date(expense.expense_date).toLocaleDateString('en-GB') }}</div>
                            </div>
                            <div v-if="expense.reminder_time">
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Reminder Time</label>
                                <div class="text-sm font-medium text-slate-900">{{ expense.reminder_time.substring(0, 5) }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Vendor</label>
                                <div class="text-sm font-medium text-slate-900">{{ expense.vendor_name || 'Not specified' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payroll Calculation Details -->
                <div v-if="expense.is_payroll" class="bg-indigo-50/50 rounded-2xl border border-indigo-100 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-indigo-100 bg-indigo-100/30 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2" stroke-linecap="round"></path></svg>
                            </div>
                            <h3 class="text-xs font-bold text-indigo-900 uppercase tracking-widest">Payroll Calculation Breakdown</h3>
                        </div>
                        <span class="text-xs font-bold text-indigo-600 px-3 py-1 bg-white rounded-full border border-indigo-100 shadow-sm">Monthly</span>
                    </div>
                    
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                            <!-- Left: Employee Side -->
                            <div class="space-y-4">
                                <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] mb-4">Employee Deductions</h4>
                                <div class="flex justify-between items-center py-2 border-b border-indigo-100">
                                    <span class="text-sm text-slate-600">Gross Monthly Salary</span>
                                    <span class="text-sm font-bold text-slate-900">€{{ Number(expense.gross_salary).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-indigo-100">
                                    <span class="text-sm text-slate-600">Social Insurance (8.8%)</span>
                                    <span class="text-sm font-bold text-rose-500">-€{{ Number(expense.si_employee).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-indigo-100">
                                    <span class="text-sm text-slate-600">NHIS / GESI (2.65%)</span>
                                    <span class="text-sm font-bold text-rose-500">-€{{ Number(expense.gesi_employee).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-indigo-100">
                                    <span class="text-sm text-slate-600">Pay-As-You-Earn (Tax)</span>
                                    <span class="text-sm font-bold text-rose-500">-€{{ Number(expense.tax_employee).toFixed(2) }}</span>
                                </div>
                                <div v-if="expense.provident_employee && Number(expense.provident_employee) > 0" class="flex justify-between items-center py-2 border-b border-indigo-100">
                                    <span class="text-sm text-slate-600">Provident Fund (EE)</span>
                                    <span class="text-sm font-bold text-rose-500">-€{{ Number(expense.provident_employee).toFixed(2) }}</span>
                                </div>
                                <div v-if="expense.union_amount && Number(expense.union_amount) > 0" class="flex justify-between items-center py-2 border-b border-indigo-100">
                                    <span class="text-sm text-slate-600">Union Fee</span>
                                    <span class="text-sm font-bold text-rose-500">-€{{ Number(expense.union_amount).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-4 mt-2">
                                    <span class="text-sm font-black uppercase tracking-widest text-emerald-600">Net Take-Home Pay</span>
                                    <span class="text-2xl font-black text-emerald-600">€{{ Number(expense.net_payable).toFixed(2) }}</span>
                                </div>
                            </div>

                            <!-- Right: Employer Burden -->
                            <div class="space-y-4 bg-white/40 p-6 rounded-2xl border border-indigo-50 shadow-inner">
                                <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] mb-4">Employer Contributions (ER)</h4>
                                <div class="flex justify-between items-center py-2 border-b border-indigo-100/50">
                                    <span class="text-sm text-slate-600">Social Insurance (ER Portion)</span>
                                    <span class="text-sm font-bold text-indigo-700">+€{{ Number(expense.si_employer).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-indigo-100/50">
                                    <span class="text-sm text-slate-600">NHIS / GESI (ER Portion)</span>
                                    <span class="text-sm font-bold text-indigo-700">+€{{ Number(expense.gesi_employer).toFixed(2) }}</span>
                                </div>
                                <div v-if="expense.provident_employer && Number(expense.provident_employer) > 0" class="flex justify-between items-center py-2 border-b border-indigo-100/50">
                                    <span class="text-sm text-slate-600">Provident Fund (ER Portion)</span>
                                    <span class="text-sm font-bold text-indigo-700">+€{{ Number(expense.provident_employer).toFixed(2) }}</span>
                                </div>
                                <div v-if="expense.cohesion_amount && Number(expense.cohesion_amount) > 0" class="flex justify-between items-center py-2 border-b border-indigo-100/50">
                                    <span class="text-sm text-slate-600">Social Cohesion Fund</span>
                                    <span class="text-sm font-bold text-indigo-700">+€{{ Number(expense.cohesion_amount).toFixed(2) }}</span>
                                </div>
                                <div v-if="(expense.redundancy_amount && Number(expense.redundancy_amount) > 0) || (expense.training_amount && Number(expense.training_amount) > 0)" class="flex justify-between items-center py-2 border-b border-indigo-100/50">
                                    <span class="text-sm text-slate-600">Redundancy & Training</span>
                                    <span class="text-sm font-bold text-indigo-700">+€{{ (Number(expense.redundancy_amount ?? 0) + Number(expense.training_amount ?? 0)).toFixed(2) }}</span>
                                </div>
                                <div v-if="expense.holiday_amount && Number(expense.holiday_amount) > 0" class="flex justify-between items-center py-2 border-b border-indigo-100/50">
                                    <span class="text-sm text-slate-600">Holiday Fund</span>
                                    <span class="text-sm font-bold text-indigo-700">+€{{ Number(expense.holiday_amount).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-10">
                                    <span class="text-sm font-black uppercase tracking-widest text-slate-900">Total Company Outflow</span>
                                    <div class="text-right">
                                        <div class="text-2xl font-black text-slate-900">€{{ (Number(expense.gross_salary) + Number(expense.si_employer ?? 0) + Number(expense.gesi_employer ?? 0) + Number(expense.provident_employer ?? 0) + Number(expense.cohesion_amount ?? 0) + Number(expense.redundancy_amount ?? 0) + Number(expense.training_amount ?? 0) + Number(expense.holiday_amount ?? 0)).toFixed(2) }}</div>
                                        <div class="text-[9px] text-slate-400 uppercase font-bold tracking-tight mt-1">Gross + ER Contributions</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Receipt -->
                <div v-if="expense.receipt_download_url" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-200">
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Receipt</h3>
                    </div>
                    <div class="p-6">
                        <a :href="expense.receipt_download_url" target="_blank" class="flex items-center gap-3 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2" stroke-linecap="round"></path>
                            </svg>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">View Receipt</div>
                                <div class="text-xs text-slate-500">Click to open in new tab</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4">Actions</h3>
                    <div class="space-y-3">
                        <Link :href="route('expenses.edit', expense.id)" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 transition-colors">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round"></path>
                            </svg>
                            <span class="text-sm font-medium text-slate-700">Edit Expense</span>
                        </Link>
                        <Link :href="route('expenses.index')" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition-colors">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2" stroke-linecap="round"></path>
                            </svg>
                            <span class="text-sm font-medium text-slate-700">View All Expenses</span>
                        </Link>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4">Details</h3>
                    <div class="space-y-3">
                        <div>
                            <div class="text-xs text-slate-500">Expense ID</div>
                            <div class="text-sm font-medium text-slate-900">#{{ expense.id }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Created</div>
                            <div class="text-sm font-medium text-slate-900">{{ new Date(expense.created_at).toLocaleDateString('en-GB') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
