<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    quote: any
}>();

const showStatusModal = ref(false);
const statusForm = ref(props.quote.status);

const vatBreakdown = computed(() => {
    const breakdown: Record<number, { rate: number, net: number, vat: number }> = {};
    props.quote.items.forEach((item: any) => {
        const rate = item.vat_rate;
        const net = item.quantity * item.unit_price_net;
        const vat = net * (rate / 100);
        
        if (!breakdown[rate]) {
            breakdown[rate] = { rate, net: 0, vat: 0 };
        }
        breakdown[rate].net += net;
        breakdown[rate].vat += vat;
    });
    return Object.values(breakdown);
});

const formatDate = (date: string) => date ? new Date(date).toLocaleDateString('en-GB') : '—';

const getStatusStyle = (status: string) => {
    const styles: Record<string, string> = {
        draft: 'bg-slate-50 text-slate-700 border-slate-100',
        sent: 'bg-blue-50 text-blue-700 border-blue-100',
        viewed: 'bg-cyan-50 text-cyan-700 border-cyan-100',
        accepted: 'bg-emerald-50 text-emerald-700 border-emerald-100',
        declined: 'bg-rose-50 text-rose-700 border-rose-100',
        expired: 'bg-amber-50 text-amber-700 border-amber-100',
        converted: 'bg-indigo-50 text-indigo-700 border-indigo-100'
    };
    return styles[status] || 'bg-slate-50 text-slate-700 border-slate-100';
};

const canConvert = computed(() => {
    return ['draft', 'sent', 'viewed', 'accepted'].includes(props.quote.status);
});

const convertToInvoice = () => {
    if (confirm('Are you sure you want to convert this quote to an invoice?')) {
        router.post(route('quotes.convert', props.quote.id));
    }
};

const updateStatus = () => {
    router.patch(route('quotes.update-status', props.quote.id), { status: statusForm.value }, {
        onSuccess: () => {
            showStatusModal.value = false;
        }
    });
};

const deleteQuote = () => {
    if (confirm('Are you sure you want to delete this quote?')) {
        router.delete(route('quotes.destroy', props.quote.id));
    }
};
</script>

<template>
    <Head :title="quote.quote_number" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center gap-4">
                    <Link :href="route('quotes.index')" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </Link>
                    <h2 class="text-xl font-bold text-slate-800">Quote: {{ quote.quote_number }}</h2>
                </div>
                <div class="flex gap-2">
                    <button 
                        v-if="canConvert" 
                        @click="convertToInvoice" 
                        class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors shadow-sm flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        Convert to Invoice
                    </button>
                    <button 
                        v-if="quote.status === 'draft'"
                        @click="showStatusModal = !showStatusModal" 
                        class="bg-white text-slate-700 border border-slate-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors shadow-sm"
                    >
                        Update Status
                    </button>
                    <button 
                        v-if="quote.status === 'draft'"
                        @click="deleteQuote" 
                        class="bg-white text-rose-600 border border-rose-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-rose-50 transition-colors shadow-sm"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Status Update Modal -->
            <div v-if="showStatusModal" class="bg-white rounded-xl border border-slate-200 p-6 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Update Quote Status</h3>
                <div class="flex gap-2 flex-wrap">
                    <button 
                        v-for="status in ['draft', 'sent', 'viewed', 'accepted', 'declined', 'expired']" 
                        :key="status"
                        @click="statusForm = status; updateStatus()"
                        :class="statusForm === status ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        class="px-4 py-2 rounded-lg text-sm font-medium capitalize transition-colors"
                    >
                        {{ status }}
                    </button>
                </div>
            </div>

            <!-- Converted Info -->
            <div v-if="quote.status === 'converted' && quote.invoice" class="bg-indigo-50 border border-indigo-200 rounded-xl p-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"></path></svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-indigo-900">This quote has been converted to an invoice</div>
                        <div class="text-xs text-indigo-600">Converted on {{ formatDate(quote.converted_at) }}</div>
                    </div>
                </div>
                <Link :href="route('invoices.show', quote.invoice.id)" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                    View Invoice →
                </Link>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <!-- Document Header -->
                <div class="p-8 border-b border-slate-100 flex justify-between items-start bg-slate-50/30">
                    <div>
                        <div class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider mb-4 inline-block">QUOTE</div>
                        <h1 class="text-4xl font-bold text-slate-900 tracking-tight">{{ quote.quote_number }}</h1>
                    </div>
                    <div class="text-right">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                        <div :class="getStatusStyle(quote.status)" class="px-5 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest border shadow-sm capitalize">
                            {{ quote.status }}
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8 border-b border-slate-100">
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-4">Bill To</h4>
                        <div class="space-y-1">
                            <Link :href="route('contacts.show', quote.contact_id)" class="text-xl font-bold text-slate-900 hover:text-indigo-600 transition-colors">{{ quote.contact?.name }}</Link>
                            <div class="text-sm font-medium text-slate-500">{{ quote.contact?.company_name || 'Individual Client' }}</div>
                            <div class="pt-2 flex flex-col gap-1 text-[11px] text-slate-400 font-medium">
                                <div>{{ quote.contact?.email }}</div>
                                <div>{{ quote.contact?.mobile_number }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="md:text-right">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-4">Dates</h4>
                        <div class="space-y-2 inline-block text-left md:text-right w-full">
                            <div class="flex justify-between text-sm">
                                <span class="font-medium text-slate-400">Quote Date:</span>
                                <span class="font-bold text-slate-800 tabular-nums">{{ formatDate(quote.date) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="font-medium text-slate-400">Valid Until:</span>
                                <span class="font-bold text-indigo-600 tabular-nums">{{ formatDate(quote.valid_until) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="p-8">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                <th class="pb-4">Description</th>
                                <th class="pb-4 text-center">Qty</th>
                                <th class="pb-4 text-center">Net Price</th>
                                <th class="pb-4 text-center">VAT %</th>
                                <th class="pb-4 text-right">Gross Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="item in quote.items" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-5 font-bold text-slate-800 text-sm">{{ item.description }}</td>
                                <td class="py-5 text-center font-bold text-slate-500 text-xs">{{ item.quantity }}</td>
                                <td class="py-5 text-center font-bold text-slate-500 text-xs tabular-nums">€{{ parseFloat(item.unit_price_net).toFixed(2) }}</td>
                                <td class="py-5 text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ item.vat_rate }}%</td>
                                <td class="py-5 text-right font-bold text-slate-900 text-sm tabular-nums">€{{ parseFloat(item.total_gross).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- VAT Breakdown Grid -->
                    <div class="mt-12 bg-slate-50 rounded-xl p-6 border border-slate-100 flex flex-wrap gap-6">
                        <div v-for="vat in vatBreakdown" :key="vat.rate" class="min-w-[120px]">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ vat.rate }}% VAT Tier</div>
                            <div class="text-sm font-bold text-indigo-600 tabular-nums">€{{ vat.vat.toFixed(2) }} <span class="text-[9px] text-slate-400 font-medium">on €{{ vat.net.toFixed(2) }}</span></div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="quote.notes || quote.terms" class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-if="quote.notes" class="bg-slate-50 rounded-xl p-6 border border-slate-100">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Notes</h4>
                            <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ quote.notes }}</p>
                        </div>
                        <div v-if="quote.terms" class="bg-slate-50 rounded-xl p-6 border border-slate-100">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Terms & Conditions</h4>
                            <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ quote.terms }}</p>
                        </div>
                    </div>

                    <!-- Footer Summary -->
                    <div class="mt-12 pt-8 border-t border-slate-100 flex flex-col md:flex-row justify-between gap-12">
                        <div class="flex-grow">
                            <div class="bg-amber-50/30 p-6 rounded-lg border border-amber-100/50">
                                <p class="text-[10px] text-slate-400 leading-relaxed font-bold uppercase tracking-tight">
                                    This quote is valid until {{ formatDate(quote.valid_until) }}. Prices are subject to change after this date.
                                </p>
                            </div>
                        </div>

                        <div class="w-full md:w-72 space-y-3">
                            <div class="flex justify-between text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <span>Sub-Total Net</span>
                                <span class="tabular-nums">€{{ parseFloat(quote.subtotal_net).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-xs font-bold text-slate-400 uppercase tracking-wider pb-3 border-b border-slate-100">
                                <span>Tax Total</span>
                                <span class="tabular-nums">€{{ parseFloat(quote.total_vat_amount).toFixed(2) }}</span>
                            </div>

                            <div class="flex justify-between items-center py-4">
                                <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">Grand Total</span>
                                <span class="text-3xl font-black text-slate-900 tabular-nums tracking-tighter">€{{ parseFloat(quote.grand_total_gross).toFixed(2) }}</span>
                            </div>

                            <div v-if="quote.discount > 0" class="p-4 bg-rose-50 rounded-xl border border-rose-100">
                                <div class="flex justify-between text-[10px] font-bold text-rose-600 uppercase tracking-wider">
                                    <span>Discount Applied</span>
                                    <span class="tabular-nums">-€{{ parseFloat(quote.discount).toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>