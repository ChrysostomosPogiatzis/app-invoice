<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps<{
    overview: {
        totalBilled: number,
        totalExpenses: number,
        pendingPayments: number
    }
}>();

// Default to current month (YYYY-MM)
const currentMonth = new Date().toISOString().slice(0, 7);
const selectedMonth = ref(currentMonth);

const downloadMonthlyInvoices = () => {
    window.location.href = route('reports.monthly.invoices') + '?month=' + selectedMonth.value;
};

const downloadMonthlyExpenses = () => {
    window.location.href = route('reports.monthly.expenses') + '?month=' + selectedMonth.value;
};

const exportData = (type: string) => {
    window.location.href = route('reports.export', { type });
};

const monthDisplay = (val: string) => {
    if (!val) return '';
    const [y, m] = val.split('-');
    return new Date(Number(y), Number(m) - 1, 1).toLocaleString('en-GB', { month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head title="Reports & Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold text-slate-800">Business Reports</h2>
        </template>

        <div class="max-w-6xl mx-auto space-y-8">
            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Total Revenue</div>
                    <div class="text-3xl font-bold text-indigo-600 tabular-nums">€{{ overview.totalBilled }}</div>
                    <p class="text-[10px] text-slate-400 mt-2 font-medium uppercase">All-time billed amount</p>
                </div>

                <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Total Expenses</div>
                    <div class="text-3xl font-bold text-rose-500 tabular-nums">€{{ overview.totalExpenses }}</div>
                    <p class="text-[10px] text-slate-400 mt-2 font-medium uppercase">Operating costs & procurement</p>
                </div>

                <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Pending Payments</div>
                    <div class="text-3xl font-bold text-amber-500 tabular-nums">€{{ overview.pendingPayments }}</div>
                    <p class="text-[10px] text-slate-400 mt-2 font-medium uppercase">Outstanding accounts receivable</p>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- Monthly Accounting Report — the main new feature              -->
            <!-- ============================================================ -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-900 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="text-xs font-bold text-white uppercase tracking-widest">Monthly Accounting Reports</h3>
                        <p class="text-[11px] text-slate-400 mt-1">Select a month and download PDF reports for your accountant.</p>
                    </div>
                    <!-- Month picker -->
                    <div class="flex items-center gap-3">
                        <input
                            v-model="selectedMonth"
                            type="month"
                            class="bg-slate-800 border border-slate-700 text-white text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 outline-none transition-all tabular-nums"
                        />
                    </div>
                </div>

                <div class="p-8">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-6">
                        Selected period:
                        <span class="text-indigo-600 font-bold">{{ monthDisplay(selectedMonth) }}</span>
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Invoices PDF -->
                        <div class="relative overflow-hidden border border-slate-200 rounded-xl p-7 group hover:border-indigo-300 hover:shadow-md transition-all bg-gradient-to-br from-white to-indigo-50/30">
                            <div class="absolute -top-6 -right-6 w-24 h-24 bg-indigo-100 rounded-full opacity-30 group-hover:opacity-50 transition-opacity"></div>
                            <div class="flex items-start gap-4 mb-5">
                                <div class="w-12 h-12 bg-indigo-600 text-white rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-slate-900">Invoice Summary</h4>
                                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">All invoices for the selected month — net, VAT, gross, payment status and outstanding balance.</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                                <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                    <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                                    PDF · Landscape A4
                                </div>
                                <button
                                    @click="downloadMonthlyInvoices"
                                    class="flex items-center gap-2 bg-indigo-600 text-white text-xs font-bold px-5 py-2.5 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Download PDF
                                </button>
                            </div>
                        </div>

                        <!-- Expenses PDF -->
                        <div class="relative overflow-hidden border border-slate-200 rounded-xl p-7 group hover:border-rose-300 hover:shadow-md transition-all bg-gradient-to-br from-white to-rose-50/30">
                            <div class="absolute -top-6 -right-6 w-24 h-24 bg-rose-100 rounded-full opacity-30 group-hover:opacity-50 transition-opacity"></div>
                            <div class="flex items-start gap-4 mb-5">
                                <div class="w-12 h-12 bg-rose-600 text-white rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-slate-900">Expense Summary</h4>
                                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">All expenses with net amount, VAT, and gross total. Includes a breakdown by category at the bottom.</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                                <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                    <span class="w-2 h-2 bg-rose-500 rounded-full"></span>
                                    PDF · Portrait A4
                                </div>
                                <button
                                    @click="downloadMonthlyExpenses"
                                    class="flex items-center gap-2 bg-rose-600 text-white text-xs font-bold px-5 py-2.5 rounded-lg hover:bg-rose-700 transition-colors shadow-sm"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Download PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick hint -->
                    <p class="text-[10px] text-slate-400 mt-5 text-center font-medium">
                        Both reports are generated in real-time and include all data recorded in your workspace for the selected month.
                    </p>
                </div>
            </div>

            <!-- CSV Export Hub (existing) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">CSV Export Engine</h3>
                        <p class="text-[11px] text-slate-400 mt-1">Generate CSV files for external accounting and analysis.</p>
                    </div>
                </div>

                <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div @click="exportData('financial')" class="p-6 bg-slate-50 border border-slate-100 rounded-xl hover:border-indigo-200 hover:bg-white transition-all cursor-pointer group">
                        <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center mb-4 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </div>
                        <h5 class="text-sm font-bold text-slate-900 mb-1">Financial Audit</h5>
                        <p class="text-[10px] text-slate-400 font-medium leading-relaxed uppercase tracking-wider">Export all invoices and VAT transactions for fiscal reporting.</p>
                    </div>

                    <div @click="exportData('inventory')" class="p-6 bg-slate-50 border border-slate-100 rounded-xl hover:border-emerald-200 hover:bg-white transition-all cursor-pointer group">
                        <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center mb-4 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </div>
                        <h5 class="text-sm font-bold text-slate-900 mb-1">Inventory Performance</h5>
                        <p class="text-[10px] text-slate-400 font-medium leading-relaxed uppercase tracking-wider">Asset utilization rates and stock movement historical data.</p>
                    </div>

                    <div @click="exportData('clients')" class="p-6 bg-slate-50 border border-slate-100 rounded-xl hover:border-rose-200 hover:bg-white transition-all cursor-pointer group">
                        <div class="w-10 h-10 bg-rose-100 text-rose-600 rounded-lg flex items-center justify-center mb-4 transition-colors group-hover:bg-rose-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </div>
                        <h5 class="text-sm font-bold text-slate-900 mb-1">CRM Database</h5>
                        <p class="text-[10px] text-slate-400 font-medium leading-relaxed uppercase tracking-wider">Mailing lists, contact history, and entity behavioral data.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
