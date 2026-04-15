<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDate, formatMoney } from '@/utils/helpers';

const props = defineProps<{
    product: any,
    movements: any
}>();
</script>

<template>
    <Head :title="`Movements - ${product.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center gap-4">
                    <Link :href="route('products.show', product.id)" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">{{ product.name }}</h2>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Stock Movement History</div>
                    </div>
                </div>
                <div class="bg-indigo-50 px-4 py-2 rounded-xl border border-indigo-100 flex items-center gap-3">
                     <div class="label uppercase text-[9px] font-black text-indigo-400 tracking-widest">Available Balance</div>
                     <div class="text-sm font-black text-indigo-700 tabular-nums">{{ product.current_stock }} Units</div>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8 italic">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date / Time</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Type</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Direction</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Reference</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 italic">
                        <tr v-for="mv in movements.data" :key="mv.id" class="hover:bg-slate-50/50 transition-all group font-medium">
                            <td class="px-6 py-5 text-xs font-black text-slate-900 tabular-nums">
                                {{ formatDate(mv.created_at) }} <span class="text-[9px] text-slate-400 ml-1">{{ new Date(mv.created_at).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) }}</span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-xs font-bold text-slate-700 tracking-tight">{{ mv.movement_type }}</div>
                                <div v-if="mv.notes" class="text-[10px] text-slate-400 font-bold uppercase tracking-tight mt-1 line-clamp-1 italic">{{ mv.notes }}</div>
                            </td>
                            <td class="px-6 py-5 uppercase text-[9px] font-black tracking-widest">
                                <span v-if="mv.direction === 'in'" class="text-emerald-500 bg-emerald-50 border border-emerald-100 px-2 py-1 rounded">Stock Inflow</span>
                                <span v-else class="text-rose-500 bg-rose-50 border border-rose-100 px-2 py-1 rounded">Stock Outflow</span>
                            </td>
                            <td class="px-6 py-5">
                                <span v-if="mv.reference_id" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest italic">REF #{{ mv.reference_id }}</span>
                                <span v-else class="text-[9px] text-slate-300 font-bold uppercase tracking-widest italic">No Reference</span>
                            </td>
                            <td class="px-6 py-5 text-right font-black tabular-nums" :class="mv.direction === 'in' ? 'text-emerald-600' : 'text-rose-600'">
                                {{ mv.direction === 'in' ? '+' : '-' }}{{ mv.quantity }}
                            </td>
                        </tr>
                        <tr v-if="movements.data.length === 0">
                            <td colspan="5" class="px-6 py-20 text-center text-slate-300 font-black uppercase tracking-widest text-[10px] italic">No stock movements recorded in digital log.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

             <!-- Pagination -->
            <div v-if="movements.links && movements.links.length > 3" class="mt-8 flex justify-center gap-1">
                <Link v-for="(link, k) in movements.links" :key="k" 
                    :href="link.url || '#'" 
                    v-html="link.label"
                    :class="[
                        'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border font-bold',
                        link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-lg shadow-indigo-500/20' : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50',
                        !link.url ? 'opacity-50 cursor-not-allowed hidden' : ''
                    ]"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
