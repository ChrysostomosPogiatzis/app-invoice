<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps<{
    invoice: any
}>();

const showPaymentForm = ref(false);
const showShareSettings = ref(false);
const emailing = ref(false);

const emailInvoice = () => {
    if (!confirm('Are you sure you want to email this invoice to the client?')) return;
    emailing.value = true;
    useForm({}).post(route('invoices.email', props.invoice.id), {
        onSuccess: () => {
            emailing.value = false;
        },
        onError: () => {
            emailing.value = false;
        },
        onFinish: () => {
            emailing.value = false;
        }
    });
};

const paymentForm = useForm({
    invoice_id: props.invoice.id,
    amount: parseFloat(props.invoice.balance_due).toFixed(2),
    payment_method: 'Bank Transfer',
    payment_date: new Date().toISOString().split('T')[0],
    notes: ''
});

const vatBreakdown = computed(() => {
    const breakdown: Record<number, { rate: number, net: number, vat: number }> = {};
    props.invoice.items.forEach((item: any) => {
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

const submitPayment = () => {
    paymentForm.post(route('payments.store'), {
        onSuccess: () => {
            showPaymentForm.value = false;
            paymentForm.reset();
        }
    });
};

const publicUrl = props.invoice.public_shares?.[0]?.share_token 
    ? route('public.invoice.show', props.invoice.public_shares[0].share_token) 
    : null;

const shareForm = useForm({
    password: '',
    expires_at: props.invoice.public_shares?.[0]?.expires_at
        ? new Date(props.invoice.public_shares[0].expires_at).toISOString().slice(0, 16)
        : '',
});

const formatDate = (date: string) => date ? new Date(date).toLocaleDateString('en-GB') : '—';

const grossUnitPrice = (item: any) => {
    const storedGross = parseFloat(item.product?.unit_price_gross);

    if (!Number.isNaN(storedGross)) {
        return storedGross.toFixed(2);
    }

    const net = parseFloat(item.unit_price_net) || 0;
    const vatRate = parseFloat(item.vat_rate) || 0;

    return (net * (1 + (vatRate / 100))).toFixed(2);
};

const copyLink = () => {
    if (publicUrl) {
        navigator.clipboard.writeText(publicUrl);
        alert('Sharing link copied to clipboard!');
    }
};

const saveShareSettings = () => {
    shareForm.transform((data) => ({
        ...data,
        expires_at: data.expires_at || null,
        password: data.password || null,
    })).put(route('invoices.share.update', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            shareForm.reset('password');
            showShareSettings.value = false;
        }
    });
};

const voidInvoice = () => {
    if (!confirm('Are you sure you want to VOID this document? This will return all items to stock and zero out the balance.')) return;
    useForm({}).post(route('invoices.void', props.invoice.id));
};

const createCreditNote = () => {
    if (!confirm('This will create a new Credit Note linked to this invoice. Continue?')) return;
    useForm({}).post(route('invoices.credit-note', props.invoice.id));
};
</script>

<template>
    <Head :title="invoice.invoice_number" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center gap-4">
                    <Link :href="route('invoices.index')" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </Link>
                    <h2 class="text-xl font-bold text-slate-800">Invoice: {{ invoice.invoice_number }}</h2>
                </div>
                <div class="flex gap-2">
                    <button v-if="invoice.balance_due > 0" @click="showPaymentForm = !showPaymentForm" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors shadow-sm">
                        {{ showPaymentForm ? 'Cancel Payment' : 'Record Payment' }}
                    </button>
                    <a :href="route('invoices.download', invoice.id)" target="_blank" class="bg-white text-slate-700 border border-slate-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors shadow-sm flex items-center gap-2">
                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                         Download PDF
                    </a>
                    <button 
                        @click="emailInvoice" 
                        :disabled="emailing"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/20 flex items-center gap-2 disabled:opacity-50"
                    >
                        <svg v-if="!emailing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2"></path></svg>
                        <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ emailing ? 'Sending...' : 'Email to Client' }}
                    </button>
                    <button v-if="publicUrl" @click="copyLink" class="bg-indigo-50 text-indigo-700 border border-indigo-100 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-indigo-100 transition-colors">
                        Copy Share Link
                    </button>
                    <button @click="showShareSettings = !showShareSettings" class="bg-white text-indigo-700 border border-indigo-200 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-indigo-50 transition-colors">
                        {{ showShareSettings ? 'Close Share' : 'Share Settings' }}
                    </button>
                    <button v-if="invoice.status !== 'void'" @click="voidInvoice" class="bg-white text-rose-600 border border-rose-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-rose-50 transition-colors shadow-sm flex items-center gap-2">
                         Void Document
                    </button>
                    <button v-if="invoice.doc_type === 'invoice' && invoice.status !== 'void'" @click="createCreditNote" class="bg-amber-100 text-amber-700 border border-amber-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-100 transition-colors shadow-sm">
                        Create Credit Note
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">
            <div v-if="showShareSettings" class="bg-white rounded-xl border border-slate-200 p-8 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Share Settings</h3>
                        <p class="text-sm text-slate-500 mt-1">Protect the public invoice link with a password and optional expiry.</p>
                        <p v-if="publicUrl" class="text-xs text-slate-400 mt-3 break-all">{{ publicUrl }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span v-if="invoice.public_shares?.[0]?.has_password" class="px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-[10px] font-bold uppercase tracking-wider text-amber-700">Password Protected</span>
                        <span v-if="invoice.public_shares?.[0]?.expires_at" class="px-3 py-1 rounded-full bg-slate-50 border border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-600">Expires {{ formatDate(invoice.public_shares[0].expires_at) }}</span>
                    </div>
                </div>

                <form @submit.prevent="saveShareSettings" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Password</label>
                        <input
                            v-model="shareForm.password"
                            type="password"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 outline-none font-medium text-sm"
                            placeholder="Leave blank to remove password"
                        />
                        <p class="text-[11px] text-slate-400 mt-2">Enter a new password to replace the current one, or leave blank to make the link password-free.</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Expires At</label>
                        <input
                            v-model="shareForm.expires_at"
                            type="datetime-local"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 outline-none font-medium text-sm"
                        />
                        <p class="text-[11px] text-slate-400 mt-2">Leave empty to keep the share link active until you change it.</p>
                    </div>

                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" :disabled="shareForm.processing" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            Save Share Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Payment Form -->
            <div v-if="showPaymentForm" class="bg-white rounded-xl border border-slate-200 p-8 shadow-lg animate-in slide-in-from-top-2 duration-300">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 leading-none">Record Payment</h3>
                        <p class="text-xs text-slate-500 mt-1 uppercase tracking-wider font-semibold">Financial Settlement Protocol</p>
                    </div>
                </div>

                <form @submit.prevent="submitPayment" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Amount (€)</label>
                            <input v-model.number="paymentForm.amount" type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 outline-none font-bold text-sm" required />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Payment Date</label>
                            <input v-model="paymentForm.payment_date" type="date" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 outline-none font-bold text-sm" required />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Method</label>
                            <select v-model="paymentForm.payment_method" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 outline-none font-bold text-sm">
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card Payment</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Payment Notes / Reference</label>
                        <textarea v-model="paymentForm.notes" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 outline-none font-medium text-sm" placeholder="Internal notes..."></textarea>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" :disabled="paymentForm.processing" class="bg-indigo-600 text-white font-bold py-2.5 px-8 rounded-lg transition-all uppercase text-xs tracking-wider hover:bg-slate-900 shadow-md">
                            Commit Payment
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <!-- Document Header -->
                <div class="p-8 border-b border-slate-100 flex justify-between items-start bg-slate-50/30">
                    <div>
                        <div class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider mb-4 inline-block">{{ invoice.doc_type || 'INVOICE' }}</div>
                        <h1 class="text-4xl font-bold text-slate-900 tracking-tight">{{ invoice.invoice_number }}</h1>
                    </div>
                    <div class="text-right">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                        <div :class="{
                            'bg-emerald-50 text-emerald-700 border-emerald-100': invoice.status === 'paid',
                            'bg-amber-50 text-amber-700 border-amber-100': invoice.status === 'partial',
                            'bg-rose-50 text-rose-700 border-rose-100': invoice.status === 'unpaid'
                        }" class="px-5 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest border shadow-sm">
                             {{ invoice.status.replace('_', ' ') }}
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8 border-b border-slate-100">
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-4">Bill To</h4>
                        <div class="space-y-1">
                            <Link :href="route('contacts.show', invoice.contact_id)" class="text-xl font-bold text-slate-900 hover:text-indigo-600 transition-colors">{{ invoice.contact?.name }}</Link>
                            <div class="text-sm font-medium text-slate-500">{{ invoice.contact?.company_name || 'Individual Client' }}</div>
                            <div class="pt-2 flex flex-col gap-1 text-[11px] text-slate-400 font-medium">
                                <div>{{ invoice.contact?.email }}</div>
                                <div>{{ invoice.contact?.mobile_number }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="md:text-right">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-4">Dates</h4>
                        <div class="space-y-2 inline-block text-left md:text-right w-full">
                             <div class="flex justify-between text-sm">
                                <span class="font-medium text-slate-400">Emission Date:</span>
                                <span class="font-bold text-slate-800 tabular-nums">{{ formatDate(invoice.date) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="font-medium text-slate-400">Settlement Due:</span>
                                <span class="font-bold text-indigo-600 tabular-nums">{{ formatDate(invoice.due_date || invoice.date) }}</span>
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
                                <th class="pb-4 text-center">Gross Price</th>
                                <th class="pb-4 text-center">VAT %</th>
                                <th class="pb-4 text-right">Gross Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="item in invoice.items" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-5 font-bold text-slate-800 text-sm">{{ item.description }}</td>
                                <td class="py-5 text-center font-bold text-slate-500 text-xs">{{ item.quantity }}</td>
                                <td class="py-5 text-center font-bold text-slate-500 text-xs tabular-nums">€{{ parseFloat(item.unit_price_net).toFixed(2) }}</td>
                                <td class="py-5 text-center font-bold text-slate-500 text-xs tabular-nums">€{{ grossUnitPrice(item) }}</td>
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

                    <!-- Payments Log -->
                    <div v-if="invoice.payments?.length" class="mt-12 space-y-3">
                         <h4 class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest px-1">Settlement Log</h4>
                         <div v-for="p in invoice.payments" :key="p.id" class="flex justify-between items-center bg-emerald-50/50 border border-emerald-100 p-4 rounded-lg group">
                              <div class="flex items-center gap-4">
                                   <div class="text-xs font-bold text-emerald-800 tabular-nums">{{ formatDate(p.payment_date) }}</div>
                                   <div class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest">{{ p.payment_method }}</div>
                              </div>
                              <div class="text-sm font-bold text-emerald-900 tabular-nums">+€{{ parseFloat(p.amount).toFixed(2) }}</div>
                         </div>
                    </div>

                    <!-- Footer Summary -->
                    <div class="mt-12 pt-8 border-t border-slate-100 flex flex-col md:flex-row justify-between gap-12">
                        <div class="flex-grow space-y-8">
                            <div class="grid grid-cols-2 gap-8">
                                <div>
                                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Settlement Profile</h4>
                                    <div class="space-y-1.5 text-sm font-medium">
                                        <div class="text-[11px] text-slate-400 font-bold uppercase italic">IBAN: <span class="text-slate-800 ml-2">{{ invoice.workspace?.iban || '—' }}</span></div>
                                        <div class="text-[11px] text-slate-400 font-bold uppercase italic">BIC: <span class="text-slate-800 ml-2">{{ invoice.workspace?.bic || '—' }}</span></div>
                                    </div>
                                </div>
                                <div v-if="invoice.attachments?.length">
                                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Digital Audit Trace</h4>
                                    <div class="flex gap-2">
                                        <a v-for="a in invoice.attachments" :key="a.id" :href="a.download_url" target="_blank" class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.414a4 4 0 00-5.656-5.656l-6.415 6.414a6 6 0 108.486 8.486L20.5 13" stroke-width="2" stroke-linecap="round"></path></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-indigo-50/30 p-6 rounded-lg border border-indigo-100/50">
                                <p class="text-[10px] text-slate-400 leading-relaxed font-bold uppercase tracking-tight">
                                    Final ownership transfer contingent upon full settlement clearance. Professional service fee of 8.5% p.a. applies to delayed liquidations.
                                </p>
                            </div>
                        </div>

                        <div class="w-full md:w-72 space-y-3">
                            <div class="flex justify-between text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <span>Sub-Total Net</span>
                                <span class="tabular-nums">€{{ parseFloat(invoice.subtotal_net).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-xs font-bold text-slate-400 uppercase tracking-wider pb-3 border-b border-slate-100">
                                <span>Tax Total</span>
                                <span class="tabular-nums">€{{ parseFloat(invoice.total_vat_amount).toFixed(2) }}</span>
                            </div>

                            <div class="flex justify-between items-center py-4">
                                 <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">Grand Total</span>
                                 <span class="text-3xl font-black text-slate-900 tabular-nums tracking-tighter">€{{ parseFloat(invoice.grand_total_gross).toFixed(2) }}</span>
                            </div>

                            <div class="p-5 bg-slate-900 rounded-xl text-white space-y-4">
                                 <div class="flex justify-between text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                     <span>Net Amount Paid</span>
                                     <span class="text-emerald-400 tabular-nums">€{{ parseFloat(invoice.amount_paid).toFixed(2) }}</span>
                                 </div>
                                 <div class="flex justify-between items-center pt-2 border-t border-white/10">
                                     <span class="text-[11px] font-bold text-indigo-300 uppercase tracking-wider">Balance Due</span>
                                     <span class="text-xl font-bold tabular-nums">€{{ parseFloat(invoice.balance_due).toFixed(2) }}</span>
                                 </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
