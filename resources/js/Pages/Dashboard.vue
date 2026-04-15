<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Props {
    stats: {
        totalRevenue: number;
        totalExpenses: number;
        activeContacts: number;
        lowStockItems: number;
        openReceivables: number;
        quotePipeline: number;
        paymentsThisPeriod?: number;
        paymentsThisMonth?: number;
        overdueInvoices: number;
        remindersDueSoon: number;
        periodPerformance?: { revenue: number; expenses: number; margin: number; };
        monthlyPerformance?: { revenue: number; expenses: number; margin: number; };
        comparisonPerformance?: { revenue: number; expenses: number; margin: number; payments: number; };
    };
    period?: { key: string; label: string; currentLabel: string; comparisonLabel: string; options: { key: string; label: string }[]; };
    recentInvoices: any[];
    recentContacts: any[];
    recentPayments: any[];
    topCustomers: any[];
    stockWatch: any[];
    quotesExpiring: any[];
    calendarEvents: any[];
}

const props = defineProps<Props>();

const stats = computed(() => ({
    totalRevenue: props.stats?.totalRevenue ?? 0,
    totalExpenses: props.stats?.totalExpenses ?? 0,
    activeContacts: props.stats?.activeContacts ?? 0,
    lowStockItems: props.stats?.lowStockItems ?? 0,
    openReceivables: props.stats?.openReceivables ?? 0,
    quotePipeline: props.stats?.quotePipeline ?? 0,
    paymentsThisPeriod: props.stats?.paymentsThisPeriod ?? props.stats?.paymentsThisMonth ?? 0,
    overdueInvoices: props.stats?.overdueInvoices ?? 0,
    remindersDueSoon: props.stats?.remindersDueSoon ?? 0,
    periodPerformance: props.stats?.periodPerformance ?? props.stats?.monthlyPerformance ?? { revenue: 0, expenses: 0, margin: 0 },
    comparisonPerformance: props.stats?.comparisonPerformance ?? { revenue: 0, expenses: 0, margin: 0, payments: 0 },
}));

const period = computed(() => props.period ?? {
    key: '30d', label: 'Last 30 Days', currentLabel: 'Current period', comparisonLabel: 'Previous period',
    options: [{ key: '30d', label: 'Last 30 Days' }, { key: '3m', label: 'Last 3 Months' }, { key: 'ytd', label: 'This Year' }],
});

const isLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { isLoaded.value = true; }, 100);
});

const currentDate = ref(new Date());
const viewMode = ref<'month' | 'week'>('week');
const selectedDay = ref<{ date: Date; events: any[] } | null>(null);
const tooltipPosition = ref({ x: 0, y: 0 });

const currency = (value: number) => `EUR ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const shortDate = (dateStr: string) => new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
const fullDate = (dateStr: string) => new Date(dateStr).toLocaleString('en-GB');
const formatTime = (timeStr: string | null) => timeStr ? timeStr.substring(0, 5) : '';

const percentageChange = (current: number, previous: number) => {
    if (!previous && !current) return 0;
    if (!previous) return 100;
    return ((current - previous) / previous) * 100;
};
const amountChange = (current: number, previous: number) => current - previous;

const comparisonTone = (change: number) => change > 0 ? 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20' : change < 0 ? 'text-rose-500 bg-rose-500/10 border-rose-500/20' : 'text-slate-400 bg-slate-100 border-slate-200';
const comparisonTextTone = (change: number) => change > 0 ? 'text-emerald-500' : change < 0 ? 'text-rose-500' : 'text-slate-400';
const comparisonPrefix = (change: number) => change > 0 ? '+' : '';
const signedCurrency = (value: number) => `${value > 0 ? '+' : value < 0 ? '-' : ''}${currency(Math.abs(value))}`;

const pComps = computed(() => ({
    revenue: percentageChange(stats.value.periodPerformance.revenue, stats.value.comparisonPerformance.revenue),
    expenses: percentageChange(stats.value.periodPerformance.expenses, stats.value.comparisonPerformance.expenses),
    payments: percentageChange(stats.value.paymentsThisPeriod, stats.value.comparisonPerformance.payments),
    margin: percentageChange(stats.value.periodPerformance.margin, stats.value.comparisonPerformance.margin),
}));
const pDeltas = computed(() => ({
    revenue: amountChange(stats.value.periodPerformance.revenue, stats.value.comparisonPerformance.revenue),
    expenses: amountChange(stats.value.periodPerformance.expenses, stats.value.comparisonPerformance.expenses),
    payments: amountChange(stats.value.paymentsThisPeriod, stats.value.comparisonPerformance.payments),
    margin: amountChange(stats.value.periodPerformance.margin, stats.value.comparisonPerformance.margin),
}));

const kpis = computed(() => [
    { label: `${period.value.label} Revenue`, value: currency(stats.value.periodPerformance.revenue), icon: 'M12 6v6m0 0v6m0-6h6m-6 0H6', tone: 'text-white', bg: 'from-indigo-600 to-indigo-900', comparison: `${comparisonPrefix(pComps.value.revenue)}${pComps.value.revenue.toFixed(1)}%`, compTone: comparisonTone(pComps.value.revenue) },
    { label: `${period.value.label} Expenses`, value: currency(stats.value.periodPerformance.expenses), icon: 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6', tone: 'text-slate-900', bg: 'from-white to-slate-50', comparison: `${comparisonPrefix(pComps.value.expenses)}${pComps.value.expenses.toFixed(1)}%`, compTone: comparisonTone(-pComps.value.expenses) },
    { label: 'Receivables', value: currency(stats.value.openReceivables), icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', tone: 'text-slate-900', bg: 'from-white to-amber-50/30', warning: stats.value.openReceivables > 0 },
    { label: `${period.value.label} Payments`, value: currency(stats.value.paymentsThisPeriod), icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', tone: 'text-slate-900', bg: 'from-white to-emerald-50/30', comparison: `${comparisonPrefix(pComps.value.payments)}${pComps.value.payments.toFixed(1)}%`, compTone: comparisonTone(pComps.value.payments) },
]);

const kpiDelays = ['delay-[0ms]', 'delay-[100ms]', 'delay-[200ms]', 'delay-[300ms]'];

const daysInMonth = (date: Date) => new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
const firstDayOfMonth = (date: Date) => { let day = new Date(date.getFullYear(), date.getMonth(), 1).getDay(); return day === 0 ? 6 : day - 1; };
const startOfWeek = (date: Date) => { const copy = new Date(date); const day = copy.getDay(); const diff = day === 0 ? -6 : 1 - day; copy.setDate(copy.getDate() + diff); copy.setHours(0, 0, 0, 0); return copy; };

const weekDays = computed(() => {
    const start = startOfWeek(currentDate.value);
    return Array.from({ length: 7 }, (_, index) => { const date = new Date(start); date.setDate(start.getDate() + index); return date; });
});

const monthDays = computed(() => {
    const d = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), 1);
    const startOffset = d.getDay() === 0 ? 6 : d.getDay() - 1;
    const start = new Date(d);
    start.setDate(start.getDate() - startOffset);
    return Array.from({ length: 35 }, (_, index) => { const date = new Date(start); date.setDate(start.getDate() + index); return date; });
});

const getEventsForDate = (date: Date) => {
    const dStr = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    return props.calendarEvents.filter(event => event.date.startsWith(dStr)).sort((a, b) => (formatTime(a.time || null) || '23:59').localeCompare(formatTime(b.time || null) || '23:59'));
};

const getEventTypeConfig = (type: string) => {
    const configs: Record<string, { bg: string; text: string; glow: string; label: string }> = {
        invoice: { bg: 'bg-indigo-50 border-indigo-200', text: 'text-indigo-700', glow: 'shadow-indigo-500/20', label: 'Invoice' },
        quote: { bg: 'bg-sky-50 border-sky-200', text: 'text-sky-700', glow: 'shadow-sky-500/20', label: 'Quote' },
        reminder: { bg: 'bg-amber-50 border-amber-200', text: 'text-amber-700', glow: 'shadow-amber-500/20', label: 'Reminder' },
        comm: { bg: 'bg-emerald-50 border-emerald-200', text: 'text-emerald-700', glow: 'shadow-emerald-500/20', label: 'Contact' },
        expense: { bg: 'bg-rose-50 border-rose-200', text: 'text-rose-700', glow: 'shadow-rose-500/20', label: 'Expense' },
        payroll: { bg: 'bg-teal-50 border-teal-200', text: 'text-teal-700', glow: 'shadow-teal-500/20', label: 'Payroll' },
        leave: { bg: 'bg-fuchsia-50 border-fuchsia-200', text: 'text-fuchsia-700', glow: 'shadow-fuchsia-500/20', label: 'Leave' },
    };
    return configs[type] || { bg: 'bg-slate-50 border-slate-200', text: 'text-slate-700', glow: 'shadow-slate-500/20', label: type };
};

const handleDayClick = (date: Date, event: MouseEvent) => {
    const events = getEventsForDate(date);
    if (events.length > 0) { const rect = (event.currentTarget as HTMLElement).getBoundingClientRect(); tooltipPosition.value = { x: rect.left, y: rect.bottom + 12 }; selectedDay.value = { date, events }; }
};
const closeTooltip = () => { selectedDay.value = null; };
const navigateToUrl = (url: string | null | undefined) => { if (url) window.location.href = url; };
const isToday = (date: Date) => date.toDateString() === new Date().toDateString();

// Calendar navigation
const goToToday = () => { currentDate.value = new Date(); };
const prevPeriod = () => {
    const d = new Date(currentDate.value);
    if (viewMode.value === 'week') { d.setDate(d.getDate() - 7); }
    else { d.setMonth(d.getMonth() - 1); d.setDate(1); }
    currentDate.value = d;
};
const nextPeriod = () => {
    const d = new Date(currentDate.value);
    if (viewMode.value === 'week') { d.setDate(d.getDate() + 7); }
    else { d.setMonth(d.getMonth() + 1); d.setDate(1); }
    currentDate.value = d;
};
const calendarLabel = computed(() => {
    if (viewMode.value === 'month') {
        return currentDate.value.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
    }
    const start = startOfWeek(currentDate.value);
    const end = new Date(start); end.setDate(start.getDate() + 6);
    const sLabel = start.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
    const eLabel = end.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    return sLabel + ' – ' + eLabel;
});

// Animated counter simulation
const animatedMargin = ref(0);
onMounted(() => {
    const target = stats.value.periodPerformance.margin;
    let start = 0;
    const duration = 1500;
    const step = (timestamp: number) => {
        if (!start) start = timestamp;
        const progress = Math.min((timestamp - start) / duration, 1);
        animatedMargin.value = progress * target;
        if (progress < 1) window.requestAnimationFrame(step);
    };
    window.requestAnimationFrame(step);
});
</script>

<template>
    <Head title="Command Center" />

    <AuthenticatedLayout>
        <div class="relative min-h-screen bg-slate-50/50 pb-20 rounded-[2.5rem] overflow-hidden -mx-4 -mt-4 shadow-inner ring-1 ring-slate-200">
            <!-- Ambient Background Glows -->
            <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-indigo-50/80 to-transparent pointer-events-none -z-10"></div>
            <div class="absolute -top-[20%] -right-[10%] w-[50%] h-[50%] rounded-full bg-indigo-400/10 blur-[120px] pointer-events-none -z-10"></div>
            <div class="absolute top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-emerald-400/10 blur-[120px] pointer-events-none -z-10"></div>

            <div class="px-8 pt-10 pb-8 w-full max-w-[1600px] mx-auto flex flex-col xl:flex-row gap-6 justify-between items-start xl:items-center relative z-10 w-full">
                <div class="animate-in fade-in slide-in-from-left-8 duration-700">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-100 border border-indigo-200 shadow-inner mb-4">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span>
                        <span class="text-[10px] font-black text-indigo-700 uppercase tracking-widest">Live Workspace Data</span>
                    </div>
                    <h2 class="text-4xl font-black text-slate-900 tracking-tight">Command Center</h2>
                    <p class="mt-2 text-sm font-medium text-slate-500 max-w-xl leading-relaxed">Your entire business pulse at a glance. Track invoices, monitor team follow-ups, and visualize your financial runway securely.</p>
                </div>

                <div class="flex flex-col xl:items-end gap-4 animate-in fade-in slide-in-from-right-8 duration-700">
                    <div class="inline-flex bg-white/80 backdrop-blur-xl border border-slate-200/60 p-1 rounded-2xl shadow-sm">
                        <Link v-for="option in period.options" :key="option.key" :href="route('dashboard', { period: option.key })" preserve-scroll
                            class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-[0.12em] transition-all duration-300"
                            :class="period.key === option.key ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/20 scale-100' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/50 scale-95 hover:scale-100'">
                            {{ option.label }}
                        </Link>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('quotes.create')" class="px-6 py-2.5 rounded-xl border border-slate-200 bg-white shadow-sm text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-50 hover:border-slate-300 hover:shadow-md hover:-translate-y-0.5">New Quote</Link>
                        <Link :href="route('invoices.create')" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 shadow-lg shadow-indigo-500/30 text-xs font-black uppercase tracking-widest text-white transition hover:shadow-xl hover:shadow-indigo-500/40 hover:-translate-y-0.5">New Invoice</Link>
                    </div>
                </div>
            </div>

            <div class="px-8 w-full max-w-[1600px] mx-auto space-y-8 relative z-10 transition-opacity duration-1000" :class="isLoaded ? 'opacity-100' : 'opacity-0 translate-y-4'">
                <!-- Core KPIs -->
                <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <div v-for="(item, i) in kpis" :key="item.label" class="relative group rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_15px_40px_rgb(0,0,0,0.08)] bg-gradient-to-br transition-all duration-500 overflow-hidden" :class="[item.bg, item.tone, kpiDelays[i] || '']">
                        <div class="absolute inset-0 bg-white/40 ring-1 ring-inset ring-white/50 rounded-3xl pointer-events-none"></div>
                        <div class="relative z-10 flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">{{ item.label }}</p>
                                <h3 class="text-3xl font-black mt-3 tabular-nums tracking-tight">{{ item.value }}</h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center shadow-sm" :class="item.tone === 'text-white' ? 'text-white' : 'text-slate-600'">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path></svg>
                            </div>
                        </div>
                        <div v-if="item.comparison" class="relative z-10 mt-6 pt-4 border-t border-white/20 flex items-center justify-between">
                            <span class="text-xs font-bold opacity-80">vs Previous</span>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border" :class="item.compTone">{{ item.comparison }}</span>
                        </div>
                        <div v-if="item.warning" class="relative z-10 mt-6 pt-4 flex gap-2 items-center text-amber-600 border-t border-amber-200/50">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                            <span class="text-xs font-bold uppercase tracking-widest">Invoices Overdue</span>
                        </div>
                    </div>
                </section>

                <div class="grid grid-cols-1 xl:grid-cols-[1fr_380px] gap-8 align-top">
                    
                    <!-- Left Core: Calendar Map & Main Logs -->
                    <div class="space-y-8 flex flex-col">
                        
                        <!-- Premium Calendar Widget -->
                        <div class="bg-white/80 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-8 shadow-xl shadow-slate-200/40">
                            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                                <div>
                                    <h3 class="text-lg font-black text-slate-900 tracking-tight">Timeline Hub</h3>
                                    <p class="text-sm font-medium text-slate-500 mt-1">Cross-reference quotes, calls, and out-of-office data.</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <!-- Prev / Label / Next -->
                                    <div class="flex items-center gap-1 bg-slate-100/80 border border-slate-200 rounded-2xl p-1">
                                        <button @click="prevPeriod" class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-500 hover:bg-white hover:text-slate-900 hover:shadow-sm transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                        </button>
                                        <span class="px-3 text-[11px] font-black text-slate-700 uppercase tracking-widest whitespace-nowrap min-w-[140px] text-center">{{ calendarLabel }}</span>
                                        <button @click="nextPeriod" class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-500 hover:bg-white hover:text-slate-900 hover:shadow-sm transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </div>
                                    <!-- Today -->
                                    <button @click="goToToday" class="px-3 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-700 hover:shadow-sm transition-all">Today</button>
                                    <!-- Week / Month toggle -->
                                    <div class="flex items-center gap-1 p-1 bg-slate-100/50 rounded-2xl border border-slate-200/50">
                                        <button @click="viewMode = 'month'" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all" :class="viewMode === 'month' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'">Month</button>
                                        <button @click="viewMode = 'week'" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all" :class="viewMode === 'week' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'">Week</button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="viewMode === 'week'" class="grid grid-cols-7 gap-3">
                                <div v-for="day in weekDays" :key="'week-' + day.toISOString()" @click="handleDayClick(day, $event)" class="group cursor-pointer rounded-2xl p-4 transition-all duration-300 border bg-white" :class="isToday(day) ? 'border-indigo-300 shadow-lg shadow-indigo-100 ring-2 ring-indigo-50/50 scale-105 z-10' : 'border-slate-100 hover:border-indigo-200 hover:shadow-md'">
                                    <div class="text-center mb-4">
                                        <div class="text-[10px] font-black uppercase tracking-widest mb-1" :class="isToday(day) ? 'text-indigo-600' : 'text-slate-400'">{{ day.toLocaleDateString('en-GB', { weekday: 'short' }) }}</div>
                                        <div class="text-2xl font-black" :class="isToday(day) ? 'text-indigo-900' : 'text-slate-800'">{{ day.toLocaleDateString('en-GB', { day: 'numeric' }) }}</div>
                                    </div>
                                    <div class="space-y-1.5 h-[160px] overflow-y-auto pr-1">
                                        <div v-for="event in getEventsForDate(day)" :key="event.title" @click.stop="navigateToUrl(event.url)" class="px-2 py-1.5 rounded-lg border text-[9px] font-black uppercase tracking-wider truncate transition-transform hover:scale-105" :class="[getEventTypeConfig(event.type).bg, getEventTypeConfig(event.type).text]">
                                            {{ event.time ? (formatTime(event.time) + ' : ') : '' }}{{ event.title }}
                                        </div>
                                        <div v-if="getEventsForDate(day).length === 0" class="flex items-center justify-center h-full text-[10px] font-bold text-slate-300 uppercase tracking-widest text-center mt-4 border-2 border-dashed border-slate-100 rounded-xl py-6">Empty</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else-if="viewMode === 'month'" class="grid grid-cols-7 gap-px bg-slate-200 border border-slate-200 rounded-2xl overflow-hidden shadow-inner">
                                <div v-for="day in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" :key="day" class="text-[9px] font-black uppercase tracking-widest text-slate-500 text-center py-2.5 bg-slate-50">{{ day }}</div>
                                <div v-for="day in monthDays" :key="'month-' + day.toISOString()" @click="handleDayClick(day, $event)"
                                    class="min-h-[100px] bg-white p-2 cursor-pointer transition-colors hover:bg-indigo-50/30 relative flex flex-col gap-1"
                                    :class="day.getMonth() !== currentDate.getMonth() ? 'opacity-40' : ''">
                                    <!-- Day number -->
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[11px] font-black w-6 h-6 flex items-center justify-center rounded-full"
                                            :class="isToday(day) ? 'bg-indigo-600 text-white shadow' : 'text-slate-600'">
                                            {{ day.getDate() }}
                                        </span>
                                        <span v-if="getEventsForDate(day).length > 2" class="text-[8px] font-bold text-slate-400">
                                            +{{ getEventsForDate(day).length - 2 }} more
                                        </span>
                                    </div>
                                    <!-- Event pills -->
                                    <div v-for="(event, idx) in getEventsForDate(day).slice(0, 2)" :key="event.title + idx"
                                        @click.stop="navigateToUrl(event.url)"
                                        class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wide truncate leading-relaxed border"
                                        :class="[getEventTypeConfig(event.type).bg, getEventTypeConfig(event.type).text]"
                                        :title="event.title">
                                        {{ event.title }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Logs Overview -->
                        <div class="bg-white/80 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-8 shadow-xl shadow-slate-200/40">
                            <div class="flex items-center gap-4 mb-6 border-b border-slate-100 pb-6">
                                <h3 class="text-lg font-black text-slate-900 tracking-tight flex-1">Activity Log</h3>
                                <div class="flex gap-2">
                                    <span v-for="t in ['invoice', 'quote', 'expense', 'leave']" :key="t" class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border" :class="[getEventTypeConfig(t).bg, getEventTypeConfig(t).text]">{{ getEventTypeConfig(t).label }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Recent Invoices -->
                                <div class="space-y-4">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Latest Invoices</h4>
                                    <Link :href="route('invoices.show', inv.id)" v-for="inv in recentInvoices.slice(0, 4)" :key="inv.id" class="group block border border-slate-100 rounded-2xl p-4 hover:border-indigo-300 hover:shadow-lg hover:-translate-y-1 transition-all bg-white">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <div class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ inv.invoice_number }}</div>
                                                <div class="text-[11px] font-semibold text-slate-500 mt-1">{{ inv.contact?.name || 'Unknown' }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-black text-slate-900">{{ currency(inv.grand_total_gross) }}</div>
                                                <div class="mt-1"><span class="px-2 py-0.5 rounded-md bg-slate-100 text-[9px] font-black uppercase tracking-widest text-slate-500">{{ inv.status }}</span></div>
                                            </div>
                                        </div>
                                    </Link>
                                </div>

                                <!-- Recent Quotes -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center mb-4">
                                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Active Quotes</h4>
                                    </div>
                                    <Link :href="route('quotes.show', q.id)" v-for="q in quotesExpiring.slice(0, 4)" :key="q.id" class="group block border border-slate-100 rounded-2xl p-4 hover:border-sky-300 hover:shadow-lg hover:shadow-sky-100 hover:-translate-y-1 transition-all bg-white">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <div class="text-sm font-bold text-slate-900 group-hover:text-sky-600 transition-colors">{{ q.quote_number }}</div>
                                                <div class="text-[10px] font-black uppercase tracking-wider text-sky-500 mt-1.5 flex items-center gap-1.5"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Valid until {{ shortDate(q.valid_until) }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-black text-slate-900">{{ currency(q.grand_total_gross) }}</div>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Sidebar -->
                    <div class="space-y-8 flex flex-col">
                        
                        <!-- Profit Margin Readout -->
                        <div class="rounded-3xl bg-slate-900 p-8 shadow-2xl shadow-indigo-900/20 text-white relative overflow-hidden group">
                           <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 to-purple-600/20 opacity-50 group-hover:opacity-100 transition-opacity duration-700"></div>
                           <div class="relative z-10">
                               <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-300 mb-6 flex items-center gap-2"><span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-ping"></span> Live Margin Trajectory</p>
                               <div class="flex flex-col gap-2">
                                    <div class="text-5xl font-black tracking-tighter">{{ currency(animatedMargin) }}</div>
                                    <div class="text-xs font-semibold text-slate-400">Total Profit &bull; {{ period.label }}</div>
                               </div>
                               
                               <div class="mt-8 space-y-4 border-t border-white/10 pt-6">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-400 font-medium">Revenue</span>
                                        <span class="font-bold text-white">{{ currency(stats.periodPerformance.revenue) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-400 font-medium">Expenses</span>
                                        <span class="font-bold text-rose-300">{{ currency(stats.periodPerformance.expenses) }}</span>
                                    </div>
                               </div>

                               <div class="mt-8">
                                    <div class="flex justify-between mb-2">
                                        <span class="text-[10px] font-black uppercase tracking-widest" :class="comparisonTextTone(pComps.margin)">{{ comparisonPrefix(pComps.margin) }}{{ pComps.margin.toFixed(1) }}% Growth</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-indigo-500 to-sky-400 rounded-full transition-all duration-[2000ms] ease-out" :style="{ width: Math.min(100, Math.max(0, (stats.periodPerformance.margin / stats.periodPerformance.revenue) * 100 || 0)) + '%' }"></div>
                                    </div>
                               </div>
                           </div>
                        </div>

                        <!-- CRM Snippet -->
                        <div class="bg-white rounded-3xl border border-slate-200/60 p-6 shadow-xl shadow-slate-200/40">
                             <div class="flex justify-between items-center mb-6">
                                <h3 class="text-sm font-black uppercase text-slate-900 tracking-wider">Top Accounts</h3>
                                <Link :href="route('contacts.index')" class="text-[10px] font-black tracking-widest uppercase text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-3 py-1 rounded-full">View CRM</Link>
                             </div>
                             <div class="space-y-4">
                                <Link :href="route('contacts.show', c.id)" v-for="c in topCustomers" :key="c.id" class="flex items-center gap-4 group">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-slate-100 border border-slate-200 flex items-center justify-center font-black text-indigo-700 text-xs shadow-sm group-hover:scale-110 transition-transform">{{ c.name.charAt(0) }}</div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-slate-900 truncate group-hover:text-indigo-600 transition-colors">{{ c.name }}</h4>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest truncate mt-0.5">{{ c.company_name || 'Individual' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-black text-slate-900">{{ currency(c.revenue) }}</div>
                                    </div>
                                </Link>
                             </div>
                        </div>

                        <!-- Inventory Warning -->
                        <div class="bg-white rounded-3xl border border-slate-200/60 p-6 shadow-xl shadow-slate-200/40">
                            <h3 class="text-sm font-black uppercase text-slate-900 tracking-wider mb-6 flex items-center gap-2"><svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg> Stock Watch Alerts</h3>
                            <div class="space-y-3">
                                <div v-for="item in stockWatch" :key="item.id" class="flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-slate-50">
                                    <div class="min-w-0 flex-1 pr-4">
                                        <div class="text-sm font-bold text-slate-800 truncate">{{ item.name }}</div>
                                        <div class="text-[9px] font-black uppercase tracking-widest text-slate-400 mt-1">{{ item.sku || 'No SKU' }}</div>
                                    </div>
                                    <div class="px-3 py-1 rounded-lg border bg-white" :class="item.current_stock < 5 ? 'border-rose-200 text-rose-600' : 'border-amber-200 text-amber-600'">
                                        <span class="text-sm font-black">{{ item.current_stock }}</span> <span class="text-[9px] font-black uppercase tracking-widest">Left</span>
                                    </div>
                                </div>
                                <div v-if="stockWatch.length === 0" class="text-center py-6 text-xs font-bold text-emerald-600 bg-emerald-50 rounded-2xl border border-emerald-100">All inventory levels are optimal.</div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Floating Tooltip -->
                <div v-if="selectedDay" class="fixed inset-0 z-40 bg-slate-900/20 backdrop-blur-[2px] transition-all" @click="closeTooltip"></div>
                <div v-if="selectedDay" class="fixed z-50 min-w-[320px] max-w-sm rounded-3xl border border-slate-200/80 bg-white/95 backdrop-blur-xl p-6 shadow-2xl shadow-indigo-500/20 animate-in zoom-in-95 duration-200" :style="{ left: tooltipPosition.x + 'px', top: tooltipPosition.y + 'px' }">
                    <div class="mb-5 flex items-center justify-between">
                        <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900">{{ selectedDay.date.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) }}</h4>
                        <button @click="closeTooltip" class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                    </div>
                    <div class="max-h-72 space-y-3 overflow-y-auto pr-2">
                        <div v-for="ev in selectedDay.events" :key="ev.title + ev.date" class="cursor-pointer rounded-2xl p-3 border transition-all hover:scale-105" :class="[getEventTypeConfig(ev.type).bg]" @click="navigateToUrl(ev.url)">
                            <div class="text-xs font-black text-slate-900 mb-1 leading-tight">{{ ev.title }}</div>
                            <div class="flex justify-between items-center text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-2">
                                <span class="px-2 py-0.5 rounded border bg-white/50" :class="getEventTypeConfig(ev.type).text">{{ getEventTypeConfig(ev.type).label }}</span>
                                <span v-if="ev.time">{{ formatTime(ev.time) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
