<script setup lang="ts">
import { computed, ref } from 'vue';
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
        periodPerformance?: {
            revenue: number;
            expenses: number;
            margin: number;
        };
        monthlyPerformance?: {
            revenue: number;
            expenses: number;
            margin: number;
        };
        comparisonPerformance?: {
            revenue: number;
            expenses: number;
            margin: number;
            payments: number;
        };
    };
    period?: {
        key: string;
        label: string;
        currentLabel: string;
        comparisonLabel: string;
        options: { key: string; label: string }[];
    };
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
    periodPerformance: props.stats?.periodPerformance ?? props.stats?.monthlyPerformance ?? {
        revenue: 0,
        expenses: 0,
        margin: 0,
    },
    comparisonPerformance: props.stats?.comparisonPerformance ?? {
        revenue: 0,
        expenses: 0,
        margin: 0,
        payments: 0,
    },
}));

const period = computed(() => props.period ?? {
    key: '30d',
    label: 'Last 30 Days',
    currentLabel: 'Current period',
    comparisonLabel: 'Previous period',
    options: [
        { key: '30d', label: 'Last 30 Days' },
        { key: '3m', label: 'Last 3 Months' },
        { key: 'ytd', label: 'This Year' },
    ],
});

const periodComparisonSummary = computed(() => {
    if (period.value.key === 'ytd') {
        return 'This Year vs Last Year';
    }

    if (period.value.key === '3m') {
        return 'Last 3 Months vs Previous 3 Months';
    }

    return 'Last 30 Days vs Previous 30 Days';
});

const currentDate = ref(new Date());
const viewMode = ref<'month' | 'week'>('month');
const selectedDay = ref<{ date: Date; events: any[] } | null>(null);
const tooltipPosition = ref({ x: 0, y: 0 });

const currency = (value: number) => `EUR ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const shortDate = (dateStr: string) => new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
const fullDate = (dateStr: string) => new Date(dateStr).toLocaleString('en-GB');
const formatTime = (timeStr: string | null) => {
    if (!timeStr) return '';
    return timeStr.substring(0, 5); // HH:MM format
};
const formatDateTime = (dateStr: string, timeStr: string | null) => {
    const time = formatTime(timeStr);
    return time ? `${time}` : '';
};

const percentageChange = (current: number, previous: number) => {
    if (!previous && !current) return 0;
    if (!previous) return 100;
    return ((current - previous) / previous) * 100;
};

const amountChange = (current: number, previous: number) => current - previous;

const comparisonTone = (change: number) => {
    if (change > 0) return 'text-emerald-600';
    if (change < 0) return 'text-rose-600';
    return 'text-slate-400';
};

const comparisonPrefix = (change: number) => (change > 0 ? '+' : '');
const signedCurrency = (value: number) => `${value > 0 ? '+' : value < 0 ? '-' : ''}${currency(Math.abs(value))}`;

const periodComparisons = computed(() => ({
    revenue: percentageChange(stats.value.periodPerformance.revenue, stats.value.comparisonPerformance.revenue),
    expenses: percentageChange(stats.value.periodPerformance.expenses, stats.value.comparisonPerformance.expenses),
    payments: percentageChange(stats.value.paymentsThisPeriod, stats.value.comparisonPerformance.payments),
    margin: percentageChange(stats.value.periodPerformance.margin, stats.value.comparisonPerformance.margin),
}));

const periodDeltas = computed(() => ({
    revenue: amountChange(stats.value.periodPerformance.revenue, stats.value.comparisonPerformance.revenue),
    expenses: amountChange(stats.value.periodPerformance.expenses, stats.value.comparisonPerformance.expenses),
    payments: amountChange(stats.value.paymentsThisPeriod, stats.value.comparisonPerformance.payments),
    margin: amountChange(stats.value.periodPerformance.margin, stats.value.comparisonPerformance.margin),
}));

const kpis = computed(() => [
    {
        label: `${period.value.label} Revenue`,
        value: currency(stats.value.periodPerformance.revenue),
        tone: 'text-slate-900',
        comparison: `${signedCurrency(periodDeltas.value.revenue)} · ${comparisonPrefix(periodComparisons.value.revenue)}${periodComparisons.value.revenue.toFixed(1)}% vs ${period.value.comparisonLabel}`,
        comparisonTone: comparisonTone(periodComparisons.value.revenue),
    },
    {
        label: `${period.value.label} Expenses`,
        value: currency(stats.value.periodPerformance.expenses),
        tone: 'text-slate-900',
        comparison: `${signedCurrency(periodDeltas.value.expenses)} · ${comparisonPrefix(periodComparisons.value.expenses)}${periodComparisons.value.expenses.toFixed(1)}% vs ${period.value.comparisonLabel}`,
        comparisonTone: comparisonTone(-periodComparisons.value.expenses),
    },
    { label: 'Receivables', value: currency(stats.value.openReceivables), tone: stats.value.openReceivables > 0 ? 'text-amber-600' : 'text-slate-900' },
    { label: 'Quote Pipeline', value: currency(stats.value.quotePipeline), tone: 'text-indigo-600' },
    {
        label: `${period.value.label} Payments`,
        value: currency(stats.value.paymentsThisPeriod),
        tone: 'text-emerald-600',
        comparison: `${signedCurrency(periodDeltas.value.payments)} · ${comparisonPrefix(periodComparisons.value.payments)}${periodComparisons.value.payments.toFixed(1)}% vs ${period.value.comparisonLabel}`,
        comparisonTone: comparisonTone(periodComparisons.value.payments),
    },
    { label: 'Active Contacts', value: stats.value.activeContacts.toLocaleString(), tone: 'text-slate-900' },
    { label: 'Overdue Invoices', value: stats.value.overdueInvoices.toLocaleString(), tone: stats.value.overdueInvoices > 0 ? 'text-rose-600' : 'text-slate-900' },
    { label: 'Reminders Due Soon', value: stats.value.remindersDueSoon.toLocaleString(), tone: stats.value.remindersDueSoon > 0 ? 'text-amber-600' : 'text-slate-900' },
]);

const daysInMonth = (date: Date) => new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
const firstDayOfMonth = (date: Date) => {
    let day = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
    return day === 0 ? 6 : day - 1;
};

const startOfWeek = (date: Date) => {
    const copy = new Date(date);
    const day = copy.getDay();
    const diff = day === 0 ? -6 : 1 - day;
    copy.setDate(copy.getDate() + diff);
    copy.setHours(0, 0, 0, 0);
    return copy;
};

const weekDays = computed(() => {
    const start = startOfWeek(currentDate.value);
    return Array.from({ length: 7 }, (_, index) => {
        const date = new Date(start);
        date.setDate(start.getDate() + index);
        return date;
    });
});

const currentMonthLabel = computed(() => currentDate.value.toLocaleString('default', { month: 'long', year: 'numeric' }));
const currentWeekLabel = computed(() => {
    const first = weekDays.value[0];
    const last = weekDays.value[6];
    return `${first.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })} - ${last.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}`;
});

const nextPeriod = () => {
    currentDate.value = viewMode.value === 'month'
        ? new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
        : new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), currentDate.value.getDate() + 7);
    selectedDay.value = null;
};

const prevPeriod = () => {
    currentDate.value = viewMode.value === 'month'
        ? new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
        : new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), currentDate.value.getDate() - 7);
    selectedDay.value = null;
};

const getEventsForDate = (date: Date) => {
    const d = date;
    const yr = d.getFullYear();
    const mo = String(d.getMonth() + 1).padStart(2, '0');
    const da = String(d.getDate()).padStart(2, '0');
    const dStr = `${yr}-${mo}-${da}`;
    return props.calendarEvents
        .filter(event => event.date.startsWith(dStr))
        .sort((a, b) => {
            const first = formatTime(a.time || null) || '23:59';
            const second = formatTime(b.time || null) || '23:59';
            return first.localeCompare(second);
        });
};

const getEventsForDay = (day: number) => getEventsForDate(new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), day));

const handleDayClick = (date: Date, event: MouseEvent) => {
    const events = getEventsForDate(date);
    if (events.length > 0) {
        const rect = (event.target as HTMLElement).getBoundingClientRect();
        tooltipPosition.value = { x: rect.left, y: rect.bottom + 8 };
        selectedDay.value = { date, events };
    }
};

const closeTooltip = () => {
    selectedDay.value = null;
};

const navigateToUrl = (url: string | null | undefined) => {
    if (url) {
        window.location.href = url;
    }
};

const getEventTypeConfig = (type: string) => {
    const configs: Record<string, { bg: string; lightBg: string; text: string; label: string }> = {
        invoice: { bg: 'bg-indigo-500', lightBg: 'bg-indigo-50', text: 'text-indigo-700', label: 'Invoice' },
        quote: { bg: 'bg-sky-500', lightBg: 'bg-sky-50', text: 'text-sky-700', label: 'Quote' },
        reminder: { bg: 'bg-amber-500', lightBg: 'bg-amber-50', text: 'text-amber-700', label: 'Reminder' },
        comm: { bg: 'bg-emerald-500', lightBg: 'bg-emerald-50', text: 'text-emerald-700', label: 'Communication' },
        expense: { bg: 'bg-rose-500', lightBg: 'bg-rose-50', text: 'text-rose-700', label: 'Expense' },
    };

    return configs[type] || { bg: 'bg-slate-500', lightBg: 'bg-slate-50', text: 'text-slate-700', label: type };
};

const marginPercentage = computed(() => {
    const revenue = stats.value.periodPerformance.revenue || 0;
    if (revenue <= 0) return 0;
    return Math.max(0, Math.min(100, (stats.value.periodPerformance.margin / revenue) * 100));
});

const selectedDayLabel = computed(() => {
    if (!selectedDay.value) return '';
    return selectedDay.value.date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
});

const isToday = (date: Date) => {
    const today = new Date();
    return date.toDateString() === today.toDateString();
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="grid w-full gap-4 xl:grid-cols-[minmax(0,1fr),auto,auto] xl:items-center">
                <div class="min-w-0">
                    <h2 class="text-2xl font-bold text-slate-900">Dashboard Overview</h2>
                    <p class="text-sm text-slate-500">Live workspace health across billing, pipeline, team follow-ups, and inventory pressure.</p>
                </div>

                <div class="flex flex-col items-start gap-2 xl:min-w-[380px] xl:items-center">
                    <div class="text-sm font-semibold text-slate-700 xl:text-center">
                        {{ periodComparisonSummary }}
                    </div>
                    <div class="inline-flex flex-wrap rounded-full border border-slate-200 bg-white p-1 shadow-sm">
                        <Link
                            v-for="option in period.options"
                            :key="option.key"
                            :href="route('dashboard', { period: option.key })"
                            preserve-scroll
                            class="rounded-full px-3 py-2 text-xs font-black uppercase tracking-[0.12em] transition"
                            :class="period.key === option.key ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        >
                            {{ option.label }}
                        </Link>
                    </div>
                    <div class="text-xs text-slate-500 xl:text-center">
                        {{ period.currentLabel }}
                    </div>
                </div>

                <div class="flex gap-3 xl:justify-end">
                    <Link :href="route('quotes.create')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Create Quote
                    </Link>
                    <Link :href="route('invoices.create')" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700">
                        Create Invoice
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="item in kpis" :key="item.label" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ item.label }}</div>
                    <div class="mt-3 text-2xl font-black tabular-nums" :class="item.tone">{{ item.value }}</div>
                    <div v-if="item.comparison" class="mt-2 text-xs font-semibold" :class="item.comparisonTone">
                        {{ item.comparison }}
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.5fr,0.9fr]">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Calendar Intelligence</h3>
                            <p class="mt-1 text-sm text-slate-500">Invoices, quotes, reminders, calls, and expenses in one place.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                                <button
                                    @click="viewMode = 'month'"
                                    class="rounded-md px-3 py-1.5 text-xs font-bold uppercase tracking-[0.14em] transition"
                                    :class="viewMode === 'month' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                >
                                    Month
                                </button>
                                <button
                                    @click="viewMode = 'week'"
                                    class="rounded-md px-3 py-1.5 text-xs font-bold uppercase tracking-[0.14em] transition"
                                    :class="viewMode === 'week' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                >
                                    Week
                                </button>
                            </div>
                            <button @click="prevPeriod" class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2"></path></svg>
                            </button>
                            <span class="w-40 text-center text-xs font-bold uppercase tracking-[0.16em] text-slate-700">
                                {{ viewMode === 'month' ? currentMonthLabel : currentWeekLabel }}
                            </span>
                            <button @click="nextPeriod" class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="2"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-4 text-xs">
                        <div v-for="type in ['invoice', 'quote', 'reminder', 'comm', 'expense']" :key="type" class="flex items-center gap-1.5">
                            <span class="inline-block h-2.5 w-2.5 rounded-full" :class="getEventTypeConfig(type).bg"></span>
                            <span class="text-slate-600">{{ getEventTypeConfig(type).label }}</span>
                        </div>
                    </div>

                    <div v-if="viewMode === 'month'" class="mt-5 grid grid-cols-7 gap-px overflow-hidden rounded-xl border border-slate-100 bg-slate-100">
                        <div v-for="dayName in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" :key="dayName" class="bg-slate-50 py-2 text-center text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">
                            {{ dayName }}
                        </div>
                        <div v-for="i in firstDayOfMonth(currentDate)" :key="'empty-' + i" class="min-h-[84px] bg-white"></div>
                        <div
                            v-for="day in daysInMonth(currentDate)"
                            :key="day"
                            class="min-h-[84px] cursor-pointer bg-white p-2 transition hover:bg-slate-50"
                            @click="handleDayClick(new Date(currentDate.getFullYear(), currentDate.getMonth(), day), $event)"
                        >
                            <div class="text-xs font-bold text-slate-400">{{ day }}</div>
                            <div class="mt-2 space-y-1">
                                <div
                                    v-for="event in getEventsForDay(day).slice(0, 3)"
                                    :key="event.title + event.date"
                                    class="flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] cursor-pointer"
                                    :class="getEventTypeConfig(event.type).lightBg"
                                    @click.stop="navigateToUrl(event.url)"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="getEventTypeConfig(event.type).bg"></span>
                                    <span class="truncate" :class="getEventTypeConfig(event.type).text">{{ event.title }}</span>
                                </div>
                                <div v-if="getEventsForDay(day).length > 3" class="pl-1 text-[10px] font-semibold text-slate-400">
                                    +{{ getEventsForDay(day).length - 3 }} more
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="mt-5 space-y-3">
                        <div
                            v-for="dayDate in weekDays"
                            :key="dayDate.toISOString()"
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
                        >
                            <div class="grid gap-0 lg:grid-cols-[220px,1fr]">
                                <button
                                    class="flex items-center gap-4 border-b border-slate-200 bg-white px-5 py-4 text-left transition hover:bg-slate-50 lg:border-b-0 lg:border-r"
                                    @click="handleDayClick(dayDate, $event)"
                                >
                                    <div
                                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-center"
                                        :class="isToday(dayDate) ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-900'"
                                    >
                                        <div>
                                            <div class="text-[10px] font-black uppercase tracking-[0.14em] opacity-70">
                                                {{ dayDate.toLocaleDateString('en-GB', { month: 'short' }) }}
                                            </div>
                                            <div class="text-xl font-black leading-none">
                                                {{ dayDate.toLocaleDateString('en-GB', { day: 'numeric' }) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-black uppercase tracking-[0.16em]" :class="isToday(dayDate) ? 'text-indigo-600' : 'text-slate-400'">
                                            {{ isToday(dayDate) ? 'Today' : dayDate.toLocaleDateString('en-GB', { weekday: 'long' }) }}
                                        </div>
                                        <div class="mt-1 text-base font-bold text-slate-900">
                                            {{ dayDate.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }) }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ getEventsForDate(dayDate).length }} {{ getEventsForDate(dayDate).length === 1 ? 'scheduled item' : 'scheduled items' }}
                                        </div>
                                    </div>
                                </button>

                                <div class="p-4">
                                    <div v-if="getEventsForDate(dayDate).length > 0" class="grid gap-3 xl:grid-cols-2">
                                        <button
                                            v-for="event in getEventsForDate(dayDate)"
                                            :key="event.title + event.date + (event.time || '')"
                                            class="block rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
                                            @click="navigateToUrl(event.url)"
                                        >
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="h-2.5 w-2.5 rounded-full" :class="getEventTypeConfig(event.type).bg"></span>
                                                    <span
                                                        class="rounded-full px-2 py-1 text-[10px] font-black uppercase tracking-[0.14em]"
                                                        :class="[getEventTypeConfig(event.type).lightBg, getEventTypeConfig(event.type).text]"
                                                    >
                                                        {{ getEventTypeConfig(event.type).label }}
                                                    </span>
                                                </div>
                                                <span v-if="event.time" class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600">
                                                    {{ formatTime(event.time) }}
                                                </span>
                                            </div>
                                            <div class="mt-3 text-sm font-semibold leading-5 text-slate-900">{{ event.title }}</div>
                                            <div v-if="event.amount" class="mt-2 text-xs font-semibold text-slate-500">
                                                {{ currency(event.amount) }}
                                            </div>
                                        </button>
                                    </div>
                                    <div v-else class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-5 text-sm text-slate-400">
                                        No scheduled items for this day.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedDay" class="fixed inset-0 z-40" @click="closeTooltip"></div>
                    <div
                        v-if="selectedDay"
                        class="fixed z-50 min-w-[320px] max-w-sm rounded-xl border border-slate-200 bg-white p-4 shadow-xl"
                        :style="{ left: tooltipPosition.x + 'px', top: tooltipPosition.y + 'px' }"
                    >
                        <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2">
                            <h4 class="text-sm font-bold text-slate-900">{{ selectedDayLabel }}</h4>
                            <button @click="closeTooltip" class="text-slate-400 transition hover:text-slate-600">x</button>
                        </div>
                        <div class="max-h-72 space-y-2 overflow-y-auto">
                            <div v-for="event in selectedDay.events" :key="event.title + event.date" class="cursor-pointer rounded-lg p-2 transition hover:bg-slate-50" @click="navigateToUrl(event.url)">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-slate-800">{{ event.title }}</div>
                                    <div v-if="event.time" class="text-[10px] font-bold text-slate-400">{{ event.time }}</div>
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ getEventTypeConfig(event.type).label }}
                                    <span v-if="event.amount"> · {{ currency(event.amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-slate-900 p-6 text-white shadow-sm">
                        <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-indigo-300">{{ period.label }} Margin</div>
                        <div class="mt-3 text-3xl font-black">{{ currency(stats.periodPerformance.margin) }}</div>
                        <div class="mt-2 text-sm text-slate-300">
                            Revenue {{ currency(stats.periodPerformance.revenue) }} · Expenses {{ currency(stats.periodPerformance.expenses) }}
                        </div>
                        <div class="mt-2 text-xs font-semibold" :class="comparisonTone(periodComparisons.margin)">
                            {{ signedCurrency(periodDeltas.margin) }} · {{ comparisonPrefix(periodComparisons.margin) }}{{ periodComparisons.margin.toFixed(1) }}% vs {{ period.comparisonLabel }}
                        </div>
                        <div class="mt-5 h-2 w-full overflow-hidden rounded-full bg-slate-800">
                            <div class="h-full bg-indigo-500" :style="{ width: marginPercentage + '%' }"></div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Quotes Expiring Soon</h3>
                            <Link :href="route('quotes.index')" class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-600">All Quotes</Link>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div v-for="quote in quotesExpiring" :key="quote.id" class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <Link :href="route('quotes.show', quote.id)" class="text-sm font-semibold text-slate-900 hover:text-indigo-600">{{ quote.quote_number }}</Link>
                                <div class="mt-1 text-xs text-slate-500">{{ quote.contact?.name || 'Unknown contact' }} · valid {{ shortDate(quote.valid_until) }}</div>
                                <div class="mt-2 text-sm font-bold text-slate-800">{{ currency(quote.grand_total_gross) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Recent Invoices</h3>
                        <Link :href="route('invoices.index')" class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-600">All Invoices</Link>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div v-for="invoice in recentInvoices" :key="invoice.id" class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <Link :href="route('invoices.show', invoice.id)" class="text-sm font-semibold text-slate-900 hover:text-indigo-600">{{ invoice.invoice_number }}</Link>
                            <div class="mt-1 text-xs text-slate-500">{{ invoice.contact?.name || 'Unknown contact' }} · {{ shortDate(invoice.date) }}</div>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-800">{{ currency(invoice.grand_total_gross) }}</span>
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-black uppercase tracking-[0.14em] text-slate-600">{{ invoice.status }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Recent Payments</h3>
                        <Link :href="route('invoices.index')" class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-600">Billing</Link>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div v-for="payment in recentPayments" :key="payment.id" class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <div class="text-sm font-semibold text-slate-900">{{ payment.invoice_number || 'Payment' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ payment.contact_name || 'Unknown contact' }} · {{ fullDate(payment.payment_date) }}</div>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-sm font-bold text-emerald-600">{{ currency(payment.amount) }}</span>
                                <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ payment.reference || 'No ref' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Top Customers</h3>
                        <Link :href="route('contacts.index')" class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-600">CRM</Link>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div v-for="customer in topCustomers" :key="customer.id" class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <Link :href="route('contacts.show', customer.id)" class="text-sm font-semibold text-slate-900 hover:text-indigo-600">{{ customer.name }}</Link>
                            <div class="mt-1 text-xs text-slate-500">{{ customer.company_name || 'Individual' }}</div>
                            <div class="mt-2 text-sm font-bold text-slate-800">{{ currency(customer.revenue) }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Newest Contacts</h3>
                        <Link :href="route('contacts.index')" class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-600">All Contacts</Link>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div v-for="contact in recentContacts" :key="contact.id" class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-sm font-bold text-slate-500">
                                    {{ contact.name.charAt(0) }}
                                </div>
                                <div class="min-w-0">
                                    <Link :href="route('contacts.show', contact.id)" class="block truncate text-sm font-semibold text-slate-900 hover:text-indigo-600">{{ contact.name }}</Link>
                                    <div class="truncate text-xs text-slate-500">{{ contact.company_name || 'Individual' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Stock Watch</h3>
                        <Link :href="route('products.index')" class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-600">Inventory</Link>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div v-for="item in stockWatch" :key="item.id" class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ item.name }}</div>
                                <div class="text-xs text-slate-500">{{ item.sku || 'No SKU' }} · {{ item.product_type }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-black" :class="Number(item.current_stock) < 5 ? 'text-rose-600' : 'text-amber-600'">{{ item.current_stock }}</div>
                                <div class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">units</div>
                            </div>
                        </div>
                        <div v-if="stockWatch.length === 0" class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-700">
                            No stock alerts right now.
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
