<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/utils/helpers';

const props = defineProps<{
    payments: any
}>();

const deletePayment = (id: number) => {
    if (confirm('Are you sure you want to delete this payment? The invoice balance will be updated accordingly.')) {
        router.delete(route('payments.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Payment History" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Payment Settlement Log</h2>
                <div class="flex gap-4">
                    <div class="bg-indigo-50 px-4 py-2 rounded-xl border border-indigo-100 flex items-center gap-3">
                         <div class="label uppercase text-[9px] font-black text-indigo-400 tracking-widest">Total Resolved</div>
                         <div class="text-sm font-black text-indigo-700 tabular-nums">€{{ formatMoney(payments.data.reduce((acc: number, p: any) => acc + Number(p.amount), 0)) }}</div>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Invoice</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Client</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Method</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Amount</th>
                            <th class="px-6 py-5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 italic">
                        <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-slate-50/50 transition-all group">
                            <td class="px-6 py-5">
                                <div class="text-xs font-black text-slate-900 tracking-tight">{{ formatDate(payment.payment_date) }}</div>
                                <div class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Settlement Day</div>
                            </td>
                            <td class="px-6 py-5">
                                <Link :href="route('invoices.show', payment.invoice_id)" class="text-xs font-black text-indigo-600 hover:text-slate-900 transition-colors uppercase tracking-widest">
                                    {{ payment.invoice.invoice_number }}
                                </Link>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-xs font-bold text-slate-700 tracking-tight">{{ payment.invoice.contact?.name }}</div>
                                <div class="text-[9px] text-slate-400 font-bold uppercase tracking-tight">{{ payment.invoice.contact?.company_name || 'Individual' }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-[9px] font-black uppercase tracking-widest border border-slate-200">
                                    {{ payment.payment_method }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right font-black text-emerald-600 tabular-nums">
                                €{{ formatMoney(payment.amount) }}
                            </td>
                            <td class="px-6 py-5 text-right">
                                <button @click="deletePayment(payment.id)" class="text-slate-300 hover:text-rose-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="payments.data.length === 0">
                            <td colspan="6" class="px-6 py-20 text-center text-slate-300 font-black uppercase tracking-widest text-[10px] italic">No payment transactions recorded in this log.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="payments.links && payments.links.length > 3" class="mt-8 flex justify-center gap-1">
                <Link v-for="(link, k) in payments.links" :key="k" 
                    :href="link.url || '#'" 
                    v-html="link.label"
                    :class="[
                        'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border',
                        link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-lg shadow-indigo-500/20' : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50',
                        !link.url ? 'opacity-50 cursor-not-allowed hidden' : ''
                    ]"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
