<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    connections: Array<{
        id: number; provider: string; label: string;
        is_active: boolean; last_synced_at: string | null; transactions_count: number;
    }>;
    transactions: { data: any[]; links: any[]; total: number; };
    totalVolume: number;
    filters: { connectionId: string | null; dateFrom: string; dateTo: string; };
    invoices: Array<{ id: number; invoice_number: string; grand_total_gross: number; }>;
    expenses: Array<{ id: number; vendor_name: string | null; expense_date: string; amount: number; }>;
}>();

const connectionId = ref(props.filters.connectionId ?? '');
const dateFrom     = ref(props.filters.dateFrom);
const dateTo       = ref(props.filters.dateTo);
const syncingId    = ref<number | null>(null);

// Reconciliation modal state
const linkingTx    = ref<any | null>(null);
const linkType     = ref<'invoice' | 'expense'>('invoice');
const linkId       = ref<number | null>(null);

const providerMeta: Record<string, { label: string; color: string; bg: string; dot: string }> = {
    vivawallet: { label: 'Viva Wallet', color: 'text-indigo-700', bg: 'bg-indigo-50 border-indigo-100', dot: 'bg-indigo-500' },
    mypos:      { label: 'myPOS',       color: 'text-emerald-700', bg: 'bg-emerald-50 border-emerald-100', dot: 'bg-emerald-500' },
};

const applyFilters = () => {
    router.get(route('banking.index'), {
        connection_id: connectionId.value || undefined,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
};
watch([connectionId, dateFrom, dateTo], applyFilters);

const syncConnection = (id: number) => {
    syncingId.value = id;
    router.post(route('banking.sync', id), {
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, { preserveScroll: true, onFinish: () => { syncingId.value = null; } });
};

const deleteConnection = (id: number) => {
    if (confirm('Remove this banking connection and all its synced transactions?')) {
        router.delete(route('banking.destroy', id), { preserveScroll: true });
    }
};

// --- Reconciliation ---
const openLinkModal = (tx: any) => {
    linkingTx.value = tx;
    linkType.value = 'invoice';
    linkId.value = null;
};

const submitLink = () => {
    if (!linkingTx.value || !linkId.value) return;
    router.post(route('banking.link', linkingTx.value.id), {
        linked_type: linkType.value,
        linked_id: linkId.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { linkingTx.value = null; },
    });
};

const removeLink = (txId: number) => {
    router.post(route('banking.unlink', txId), {}, { preserveScroll: true });
};

const formatAmount = (amount: number) =>
    new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'EUR' }).format(amount);

const linkedLabel = (tx: any) => {
    if (!tx.linked_type) return null;
    if (tx.linked_type === 'invoice') return `Invoice ${tx.linked?.invoice_number ?? '#' + tx.linked_id}`;
    return `Expense — ${tx.linked?.vendor_name ?? '#' + tx.linked_id}`;
};
</script>

<template>
    <Head title="Banking" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-4">
                <h2 class="text-xl font-bold text-slate-800">Banking Connections</h2>
                <Link :href="route('banking.create')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap">
                    + Add Connection
                </Link>
            </div>
        </template>

        <div class="space-y-8">

            <!-- Connections Grid -->
            <div v-if="connections.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="conn in connections" :key="conn.id"
                    class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col gap-4"
                    :class="{ 'opacity-60': !conn.is_active }"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <span :class="[providerMeta[conn.provider]?.bg, providerMeta[conn.provider]?.color]"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider mb-2">
                                <span :class="providerMeta[conn.provider]?.dot" class="w-1.5 h-1.5 rounded-full"></span>
                                {{ providerMeta[conn.provider]?.label ?? conn.provider }}
                            </span>
                            <div class="text-sm font-bold text-slate-900">{{ conn.label }}</div>
                            <div class="text-[10px] text-slate-400 mt-1">{{ conn.transactions_count }} transactions synced</div>
                        </div>
                        <span v-if="!conn.is_active" class="text-[9px] font-bold bg-slate-100 text-slate-500 px-2 py-1 rounded uppercase">Inactive</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium">{{ conn.last_synced_at ? 'Last sync: ' + conn.last_synced_at : 'Never synced' }}</div>
                    <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                        <button @click="syncConnection(conn.id)" :disabled="syncingId === conn.id"
                            class="flex-1 flex items-center justify-center gap-1.5 bg-slate-900 text-white text-[11px] font-bold py-2 px-3 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            <svg :class="{ 'animate-spin': syncingId === conn.id }" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            {{ syncingId === conn.id ? 'Syncing…' : 'Sync Now' }}
                        </button>
                        <Link :href="route('banking.edit', conn.id)" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round"/></svg>
                        </Link>
                        <button @click="deleteConnection(conn.id)" class="p-2 text-slate-300 hover:text-rose-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="bg-white border border-dashed border-slate-300 rounded-xl p-16 text-center">
                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800 mb-1">No Banking Connections</h3>
                <p class="text-xs text-slate-500 mb-4">Add your first payment provider to start syncing transactions.</p>
                <Link :href="route('banking.create')" class="inline-flex items-center bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                    Add Connection
                </Link>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-widest">Transactions</h3>
                        <p class="text-[10px] text-slate-400 mt-0.5">Volume: <strong class="text-slate-700">{{ formatAmount(totalVolume) }}</strong></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <select v-model="connectionId" class="bg-white border border-slate-200 rounded-lg py-2 pl-3 pr-8 text-xs font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                            <option value="">All Connections</option>
                            <option v-for="c in connections" :key="c.id" :value="String(c.id)">{{ c.label }}</option>
                        </select>
                        <input v-model="dateFrom" type="date" class="bg-white border border-slate-200 rounded-lg py-2 px-3 text-xs focus:ring-2 focus:ring-indigo-500/20 outline-none" />
                        <span class="text-slate-400 text-xs">→</span>
                        <input v-model="dateTo" type="date" class="bg-white border border-slate-200 rounded-lg py-2 px-3 text-xs focus:ring-2 focus:ring-indigo-500/20 outline-none" />
                    </div>
                </div>

                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Source</th>
                            <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Type / Card</th>
                            <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Reference</th>
                            <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Reconciled To</th>
                            <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right">Amount</th>
                            <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3 text-xs text-slate-500 tabular-nums whitespace-nowrap">
                                {{ new Date(tx.transaction_date).toLocaleDateString('en-GB') }}<br>
                                <span class="text-[10px] text-slate-400">{{ new Date(tx.transaction_date).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span :class="[providerMeta[tx.provider]?.bg, providerMeta[tx.provider]?.color]"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold border uppercase">
                                    {{ providerMeta[tx.provider]?.label ?? tx.provider }}
                                </span>
                                <div class="text-[10px] text-slate-400 mt-0.5">{{ tx.connection?.label }}</div>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600 capitalize">
                                <div>{{ tx.type ?? '—' }}</div>
                                <div v-if="tx.card_last4" class="text-[10px] text-slate-400 font-mono">{{ tx.card_type }} ····{{ tx.card_last4 }}</div>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ tx.reference ?? '—' }}</td>
                            <td class="px-5 py-3 text-xs">
                                <!-- Linked badge -->
                                <div v-if="tx.linked_type" class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-lg text-[10px] font-bold">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" stroke-width="2" stroke-linecap="round"/></svg>
                                        {{ linkedLabel(tx) }}
                                    </span>
                                    <button @click="removeLink(tx.id)" class="text-slate-300 hover:text-rose-500 transition-colors" title="Remove link">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/></svg>
                                    </button>
                                </div>
                                <span v-else class="text-slate-300 text-[10px]">Unlinked</span>
                            </td>
                            <td class="px-5 py-3 text-right font-bold tabular-nums text-slate-900 text-sm">{{ formatAmount(tx.amount) }}</td>
                            <td class="px-5 py-3 text-center">
                                <span :class="['F','succeeded','completed'].includes(tx.status)
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                                    : ['R','failed'].includes(tx.status)
                                    ? 'bg-rose-50 text-rose-700 border-rose-100'
                                    : 'bg-slate-100 text-slate-500 border-slate-200'"
                                    class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold border uppercase">
                                    {{ tx.status ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button v-if="!tx.linked_type" @click="openLinkModal(tx)"
                                    class="text-[10px] font-bold text-indigo-500 hover:text-indigo-700 transition-colors whitespace-nowrap">
                                    Link →
                                </button>
                            </td>
                        </tr>
                        <tr v-if="transactions.data.length === 0">
                            <td colspan="8" class="px-6 py-16 text-center text-sm text-slate-400 italic">
                                No transactions found. Sync a connection to import data.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Showing {{ transactions.data.length }} of {{ transactions.total }}</span>
                    <div class="flex gap-2">
                        <Link v-for="link in transactions.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                            :class="[link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 border-slate-200', !link.url ? 'opacity-30 cursor-not-allowed' : '']"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border shadow-sm" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Reconciliation Modal -->
        <div v-if="linkingTx" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="linkingTx = null"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
                <h3 class="text-base font-bold text-slate-900 mb-1">Link Transaction</h3>
                <p class="text-xs text-slate-500 mb-5">
                    {{ formatAmount(linkingTx.amount) }} on {{ new Date(linkingTx.transaction_date).toLocaleDateString('en-GB') }}
                </p>

                <!-- Type selector -->
                <div class="flex gap-3 mb-4">
                    <button v-for="t in ['invoice','expense']" :key="t" @click="linkType = t as any; linkId = null"
                        :class="linkType === t ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600'"
                        class="flex-1 py-2 px-4 rounded-lg border-2 text-sm font-semibold capitalize transition-all">
                        {{ t }}
                    </button>
                </div>

                <!-- Picker -->
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-slate-600 mb-1 capitalize">Select {{ linkType }}</label>
                    <select v-model="linkId" class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                        <option :value="null">— choose —</option>
                        <template v-if="linkType === 'invoice'">
                            <option v-for="inv in invoices" :key="inv.id" :value="inv.id">
                                {{ inv.invoice_number }} — {{ formatAmount(inv.grand_total_gross) }}
                            </option>
                        </template>
                        <template v-else>
                            <option v-for="exp in expenses" :key="exp.id" :value="exp.id">
                                {{ new Date(exp.expense_date).toLocaleDateString('en-GB') }} — {{ exp.vendor_name ?? 'Unspecified' }} ({{ formatAmount(exp.amount) }})
                            </option>
                        </template>
                    </select>
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="linkingTx = null" class="px-4 py-2 text-slate-500 text-sm font-medium hover:text-slate-700">Cancel</button>
                    <button @click="submitLink" :disabled="!linkId"
                        class="bg-indigo-600 text-white text-sm font-bold py-2 px-6 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-40">
                        Save Link
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
