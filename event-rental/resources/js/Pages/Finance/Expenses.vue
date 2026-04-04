<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    expenses: {
        data: any[],
        links: any[],
        total: number
    },
    filters: { search: string, category: string, sort?: string, direction?: string },
    stats: {
        totalThisMonth: number,
        byCategory: Array<{ category: string, total: number }>
    }
}>();

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');
const sortField = ref(props.filters.sort || 'expense_date');
const sortDirection = ref(props.filters.direction || 'desc');

const triggerSort = (field: string) => {
    const newDirection = sortField.value === field && sortDirection.value === 'desc' ? 'asc' : 'desc';
    sortField.value = field;
    sortDirection.value = newDirection;
    router.get(route('expenses.index'), {
        search: search.value,
        category: categoryFilter.value,
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

watch([search, categoryFilter], () => {
    router.get(route('expenses.index'), { 
        search: search.value, 
        category: categoryFilter.value,
        sort: sortField.value,
        direction: sortDirection.value
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
});

const deleteExpense = (id: number) => {
    if (confirm('Delete this expense record?')) {
        router.delete(route('expenses.destroy', id), { preserveScroll: true });
    }
};

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
    <Head title="Expenses" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-4">
                <h2 class="text-xl font-bold text-slate-800">Expense Tracking</h2>
                
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <!-- Search -->
                    <div class="relative flex-grow lg:flex-grow-0">
                        <input v-model="search" type="text" placeholder="Search expenses..." class="bg-white border border-slate-200 rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none w-full lg:w-64 transition-all" />
                        <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round"></path></svg>
                    </div>

                    <!-- Category Filter -->
                    <select v-model="categoryFilter" class="bg-white border border-slate-200 rounded-lg py-2 pl-3 pr-8 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                        <option value="">All Categories</option>
                        <option value="fuel">Fuel & Logistics</option>
                        <option value="staff_wages">Staff & Wages</option>
                        <option value="marketing">Marketing</option>
                        <option value="utility">Utilities</option>
                    </select>

                    <Link :href="route('expenses.create')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap">
                        New Expense
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="bg-slate-900 p-6 rounded-xl text-white shadow-lg relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Spend MTD</div>
                        <div class="text-3xl font-bold tabular-nums">€{{ Number(stats.totalThisMonth).toLocaleString() }}</div>
                        <div class="mt-4 flex items-center gap-2 text-[10px] font-medium text-slate-500 uppercase tracking-wider">
                            <span class="w-2 h-2 bg-indigo-500 rounded-full shadow-[0_0_8px_rgba(99,102,241,0.6)]"></span>
                            Active Workspace
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3 bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 px-1">Spend by Category</h3>
                    <div class="flex gap-4 flex-wrap">
                        <div v-for="cat in stats.byCategory" :key="cat.category" class="bg-slate-50 px-4 py-3 rounded-lg border border-slate-100 min-w-[120px]">
                             <div class="text-[10px] font-bold uppercase text-slate-400 mb-1 tracking-wider">{{ cat.category.replace('_', ' ') }}</div>
                             <div class="text-lg font-bold text-slate-800 tabular-nums">€{{ Number(cat.total).toLocaleString() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th 
                                class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition-colors select-none"
                                @click="triggerSort('expense_date')"
                            >
                                <span class="flex items-center gap-1">
                                    Date
                                    <span class="text-slate-400 text-[10px]">{{ sortIcon('expense_date') }}</span>
                                </span>
                            </th>
                            <th 
                                class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition-colors select-none"
                                @click="triggerSort('category')"
                            >
                                <span class="flex items-center gap-1">
                                    Category
                                    <span class="text-slate-400 text-[10px]">{{ sortIcon('category') }}</span>
                                </span>
                            </th>
                            <th 
                                class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition-colors select-none"
                                @click="triggerSort('vendor_name')"
                            >
                                <span class="flex items-center gap-1">
                                    Vendor / Ref
                                    <span class="text-slate-400 text-[10px]">{{ sortIcon('vendor_name') }}</span>
                                </span>
                            </th>
                            <th 
                                class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider text-right cursor-pointer hover:bg-slate-100 transition-colors select-none"
                                @click="triggerSort('amount')"
                            >
                                <span class="flex items-center justify-end gap-1">
                                    Net Amount
                                    <span class="text-slate-400 text-[10px]">{{ sortIcon('amount') }}</span>
                                </span>
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider text-right">VAT</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="exp in expenses.data" :key="exp.id" class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4 text-sm text-slate-500 tabular-nums">
                                {{ new Date(exp.expense_date).toLocaleDateString('en-GB') }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getCategoryStyle(exp.category)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border capitalize tracking-wider">
                                    {{ exp.category.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-[10px] font-bold text-slate-400 border border-slate-200">
                                        #{{ exp.id }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900 truncate">{{ exp.vendor_name || 'Unspecified' }}</div>
                                        <div v-if="exp.notes" class="text-[11px] text-slate-400 truncate max-w-[200px]">{{ exp.notes }}</div>
                                    </div>
                                    <a v-if="exp.receipt_url" :href="exp.receipt_url" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-all text-[10px] font-bold uppercase tracking-wider group" title="View Receipt">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.414a4 4 0 00-5.656-5.656l-6.415 6.414a6 6 0 108.486 8.486L20.5 13" stroke-width="2" stroke-linecap="round"></path></svg>
                                        <span>View Receipt</span>
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-slate-900 tabular-nums">
                                €{{ Number(exp.amount).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4 text-right tabular-nums">
                                <span v-if="exp.vat_amount && Number(exp.vat_amount) > 0" class="text-indigo-600 font-semibold">€{{ Number(exp.vat_amount).toFixed(2) }}</span>
                                <span v-else class="text-slate-300 text-xs">&mdash;</span>
                            </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <Link :href="route('expenses.show', exp.id)" class="p-2 text-slate-400 hover:text-slate-900 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2"></path></svg>
                                </Link>
                                <Link :href="route('expenses.edit', exp.id)" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round"></path></svg>
                                </Link>
                                <button @click="deleteExpense(exp.id)" class="p-2 text-slate-300 hover:text-rose-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round"></path></svg>
                                </button>
                            </div>
                        </td>
                        </tr>
                        <tr v-if="expenses.data.length === 0">
                            <td colspan="6" class="px-6 py-20 text-center text-sm text-slate-400 italic">No expenses recorded for this period.</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-medium">Showing {{ expenses.data.length }} of {{ expenses.total }} expenses</span>
                    <div class="flex gap-2">
                        <Link v-for="link in expenses.links" :key="link.label" :href="link.url || '#'" 
                              v-html="link.label"
                              :class="[
                                  link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 hover:text-slate-700 border-slate-200',
                                  !link.url ? 'opacity-30 cursor-not-allowed' : ''
                              ]"
                              class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all border shadow-sm" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>