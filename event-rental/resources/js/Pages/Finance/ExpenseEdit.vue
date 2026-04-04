<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
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
    }
}>();

const form = useForm({
    category: props.expense.category,
    amount: props.expense.amount,
    vat_amount: props.expense.vat_amount ?? 0,
    expense_date: props.expense.expense_date ? props.expense.expense_date.substring(0, 10) : '',
    reminder_time: props.expense.reminder_time ? props.expense.reminder_time.substring(0, 5) : '',
    vendor_name: props.expense.vendor_name || '',
    receipt_file: null as File | null,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        // Ensure values are properly formatted
        amount: String(data.amount),
        expense_date: data.expense_date,
        reminder_time: data.reminder_time || null,
    })).put(route('expenses.update', props.expense.id), {
        preserveScroll: true,
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

        <div class="max-w-3xl">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Expense Details</h3>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Category</label>
                                <select v-model="form.category" class="w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all bg-white">
                                    <option value="fuel">Fuel & Logistics</option>
                                    <option value="staff_wages">Staff & Wages</option>
                                    <option value="sub_rental">Sub Rental</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="utility">Utilities</option>
                                    <option value="other">Other</option>
                                </select>
                                <p v-if="form.errors.category" class="mt-1 text-xs text-rose-500">{{ form.errors.category }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Amount Net (€)</label>
                                <input v-model.number="form.amount" type="number" step="0.01" min="0.01" class="w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" required />
                                <p v-if="form.errors.amount" class="mt-1 text-xs text-rose-500">{{ form.errors.amount }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">VAT Amount (€)</label>
                                <input v-model.number="form.vat_amount" type="number" step="0.01" min="0" class="w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
                                <p v-if="form.errors.vat_amount" class="mt-1 text-xs text-rose-500">{{ form.errors.vat_amount }}</p>
                                <p class="text-xs text-slate-400 mt-1">Gross: €{{ (Number(form.amount) + Number(form.vat_amount)).toFixed(2) }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Date</label>
                                <input v-model="form.expense_date" type="date" class="w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" required />
                                <p v-if="form.errors.expense_date" class="mt-1 text-xs text-rose-500">{{ form.errors.expense_date }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Reminder Time</label>
                                <input v-model="form.reminder_time" type="time" class="w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
                                <p v-if="form.errors.reminder_time" class="mt-1 text-xs text-rose-500">{{ form.errors.reminder_time }}</p>
                                <p class="text-xs text-slate-400 mt-1">Optional - for calendar reminders</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Vendor Name</label>
                                <input v-model="form.vendor_name" type="text" class="w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" placeholder="e.g. Shell Gas Station" />
                                <p v-if="form.errors.vendor_name" class="mt-1 text-xs text-rose-500">{{ form.errors.vendor_name }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Receipt</label>
                                <input type="file" @change="(e: any) => { form.receipt_file = e.target.files[0] }" accept="image/*,application/pdf" class="block w-full text-sm text-slate-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-lg file:border-0
                                    file:text-xs file:font-medium
                                    file:bg-slate-100 file:text-slate-700
                                    hover:file:bg-slate-200 cursor-pointer" />
                                <p class="text-xs text-slate-400 mt-1">Upload a new receipt to replace the existing one (JPG, PNG, PDF - Max 5MB)</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Action buttons at bottom -->
            <div class="mt-6 flex items-center justify-end gap-4">
                <Link :href="route('expenses.index')" class="text-sm text-slate-500 hover:text-slate-700 transition-colors">
                    Cancel
                </Link>
                <button @click="submit" :disabled="form.processing" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50">
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>