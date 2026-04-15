<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    invoices: {
        data: any[],
        links: any[],
        total: number
    },
    filters: { search: string, status: string, sort?: string, direction?: string }
}>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const sortField = ref(props.filters.sort || 'date');
const sortDirection = ref(props.filters.direction || 'desc');

const triggerSort = (field: string) => {
    const newDirection = sortField.value === field && sortDirection.value === 'desc' ? 'asc' : 'desc';
    sortField.value = field;
    sortDirection.value = newDirection;
    router.get(route('invoices.index'), {
        search: search.value,
        status: statusFilter.value,
        sort: field,
        direction: newDirection
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

const sortIcon = (field: string) => {
    if (sortField.value !== field) return '↕';
    return sortDirection.value === 'asc' ? '↑' : '↓';
};

watch([search, statusFilter], () => {
    router.get(route('invoices.index'), { 
        search: search.value, 
        status: statusFilter.value,
        sort: sortField.value,
        direction: sortDirection.value
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
});

const voidInvoice = (id: number) => {
    if (confirm('Are you sure you want to void this invoice?')) {
         router.delete(route('invoices.destroy', id), { preserveScroll: true });
    }
};

const getStatusStyle = (status: string) => {
    const styles: Record<string, string> = {
        paid: 'bg-emerald-50 text-emerald-700 border-emerald-100',
        unpaid: 'bg-amber-50 text-amber-700 border-amber-100',
        overdue: 'bg-rose-50 text-rose-700 border-rose-100',
        partially_paid: 'bg-indigo-50 text-indigo-700 border-indigo-100',
        voided: 'bg-slate-50 text-slate-700 border-slate-100'
    };
    return styles[status] || 'bg-slate-50 text-slate-700 border-slate-100';
};
</script>

<template>
    <Head title="Billing & Invoices" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-4">
                <h2 class="text-xl font-bold text-slate-800">Billing & Invoices</h2>
                
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <!-- Search -->
                    <div class="relative flex-grow lg:flex-grow-0">
                        <input v-model="search" type="text" placeholder="Search invoice #" class="bg-white border border-slate-200 rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none w-full lg:w-64 transition-all" />
                        <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round"></path></svg>
                    </div>

                    <!-- Status Filter -->
                    <select v-model="statusFilter" class="bg-white border border-slate-200 rounded-lg py-2 pl-3 pr-8 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                        <option value="">All Statuses</option>
                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="overdue">Overdue</option>
                    </select>

                    <Link :href="route('invoices.create')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap">
                        New Invoice
                    </Link>
                </div>
            </div>
        </template>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th 
                            class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition-colors select-none"
                            @click="triggerSort('date')"
                        >
                            <span class="flex items-center gap-1">
                                Date
                                <span class="text-slate-400 text-[10px]">{{ sortIcon('date') }}</span>
                            </span>
                        </th>
                        <th 
                            class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition-colors select-none"
                            @click="triggerSort('invoice_number')"
                        >
                            <span class="flex items-center gap-1">
                                Invoice #
                                <span class="text-slate-400 text-[10px]">{{ sortIcon('invoice_number') }}</span>
                            </span>
                        </th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">Client</th>
                        <th 
                            class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider text-center cursor-pointer hover:bg-slate-100 transition-colors select-none"
                            @click="triggerSort('status')"
                        >
                            <span class="flex items-center justify-center gap-1">
                                Status
                                <span class="text-slate-400 text-[10px]">{{ sortIcon('status') }}</span>
                            </span>
                        </th>
                        <th 
                            class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider text-right cursor-pointer hover:bg-slate-100 transition-colors select-none"
                            @click="triggerSort('grand_total_gross')"
                        >
                            <span class="flex items-center justify-end gap-1">
                                Total
                                <span class="text-slate-400 text-[10px]">{{ sortIcon('grand_total_gross') }}</span>
                            </span>
                        </th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 text-sm text-slate-500 tabular-nums">
                            {{ new Date(invoice.date).toLocaleDateString('en-GB') }}
                        </td>
                        <td class="px-6 py-4">
                            <Link :href="route('invoices.show', invoice)" class="text-sm font-bold text-slate-900 hover:text-indigo-600 transition-colors">
                                {{ invoice.invoice_number }}
                            </Link>
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="invoice.contact">
                                <Link :href="route('contacts.show', invoice.contact_id)" class="text-sm font-medium text-slate-800 hover:text-indigo-600">{{ invoice.contact.name }}</Link>
                                <div class="text-[11px] text-slate-400 font-medium line-clamp-1">{{ invoice.contact.company_name || 'Individual' }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span :class="getStatusStyle(invoice.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border capitalize">
                                {{ invoice.status.replace('_', ' ') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-bold text-slate-900 tabular-nums">€{{ parseFloat(invoice.grand_total_gross || 0).toFixed(2) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <Link :href="route('invoices.show', invoice)" class="p-2 text-slate-400 hover:text-slate-900 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2"></path></svg>
                                </Link>
                                <button @click="voidInvoice(invoice.id)" class="p-2 text-slate-300 hover:text-rose-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="invoices.data.length === 0">
                        <td colspan="6" class="px-6 py-20 text-center text-sm text-slate-400 italic">No invoices found.</td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">Showing {{ invoices.data.length }} of {{ invoices.total }} invoices</span>
                <div class="flex gap-2">
                    <Link v-for="link in invoices.links" :key="link.label" :href="link.url || '#'" 
                          v-html="link.label"
                          :class="[
                              link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 hover:text-slate-700 border-slate-200',
                              !link.url ? 'opacity-30 cursor-not-allowed' : ''
                          ]"
                          class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all border shadow-sm" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>