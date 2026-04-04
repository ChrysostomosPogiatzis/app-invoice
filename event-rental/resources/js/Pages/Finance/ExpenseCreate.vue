<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { withValidation } from '@/utils/validation';

defineProps<{}>();

const form = useForm({
    category: 'other',
    amount: 0,
    vat_amount: 0,
    expense_date: new Date().toISOString().split('T')[0],
    reminder_time: '',
    vendor_name: '',
    receipt_file: null as File | null,
    notes: ''
});

const submit = () => {
    form.post(route('expenses.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Record Expense" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('expenses.index')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                </Link>
                <h2 class="text-xl font-bold text-slate-800">Record New Expense</h2>
            </div>
        </template>

        <div class="max-w-3xl mx-auto pt-8 px-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <FormErrorSummary :errors="form.errors" />
                    
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                             <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Category</label>
                             <select v-model="form.category" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all font-medium text-sm', form.errors.category)">
                                <option value="fuel">Fuel & Logistics</option>
                                <option value="staff_wages">Staff & Wages</option>
                                <option value="sub_rental">Equipment Sub-Rental</option>
                                <option value="marketing">Marketing</option>
                                <option value="utility">Utilities</option>
                                <option value="other">Other Expenses</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Transaction Date</label>
                            <input v-model="form.expense_date" type="date" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all font-medium text-sm', form.errors.expense_date)" />
                        </div>
                    </div>

                    <!-- Vendor & Amount -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Vendor / Payee</label>
                            <input v-model="form.vendor_name" type="text" placeholder="e.g. Shell" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all font-medium text-sm', form.errors.vendor_name)" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Amount (Net €)</label>
                            <input v-model.number="form.amount" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all font-bold text-lg text-slate-900', form.errors.amount)" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">VAT Amount (€)</label>
                            <input v-model.number="form.vat_amount" type="number" step="0.01" min="0" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all font-bold text-lg text-slate-900', form.errors.vat_amount)" />
                            <p class="text-[9px] text-slate-400 mt-1">Total gross: €{{ (Number(form.amount) + Number(form.vat_amount)).toFixed(2) }}</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Reminder Time</label>
                            <input v-model="form.reminder_time" type="time" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all font-medium text-sm', form.errors.reminder_time)" />
                            <p class="text-[9px] text-slate-400 mt-1">Optional - for calendar reminders</p>
                        </div>
                    </div>

                    <!-- Receipt Upload -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Receipt Document</label>
                        <div class="relative group border-2 border-dashed border-slate-200 rounded-xl p-4 hover:border-indigo-300 transition-all bg-slate-50/50">
                            <input type="file" @input="form.receipt_file = ($event.target as HTMLInputElement).files?.[0] || null" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                            <div class="flex items-center justify-between pointer-events-none">
                                <span class="text-xs font-semibold text-slate-400">{{ form.receipt_file ? form.receipt_file.name : 'Upload PDF or JPG receipt...' }}</span>
                                <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Internal Notes</label>
                        <textarea v-model="form.notes" rows="3" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all font-medium text-sm resize-none', form.errors.notes)" placeholder="Optional transaction details..."></textarea>
                    </div>

                    <div class="pt-6 flex justify-between items-center border-t border-slate-100">
                        <p class="text-[10px] text-slate-400 font-medium uppercase tracking-tight max-w-[200px]">
                            Once recorded, expenses will be reflected in financial reports and monthly burn summaries.
                        </p>
                        <div class="flex gap-4">
                            <Link :href="route('expenses.index')" class="px-4 py-2 text-slate-400 font-bold hover:text-slate-600 transition-colors uppercase text-xs tracking-widest">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white font-bold py-2.5 px-8 rounded-lg shadow-md hover:bg-slate-900 transition-all disabled:opacity-50 uppercase text-xs tracking-widest">
                                {{ form.processing ? 'Saving...' : 'Record Expense' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
