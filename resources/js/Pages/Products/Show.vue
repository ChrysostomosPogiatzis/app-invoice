<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    product: any
}>();

const formatDate = (date: string) => date ? new Date(date).toLocaleDateString('en-GB') : '—';
const formatCurrency = (value: any) => parseFloat(value || 0).toFixed(2);

// SKU
const isEditingSKU = ref(false);
const skuForm = useForm({ sku: props.product.sku || '' });
const submitSKU = () => {
    skuForm.patch(route('products.update-partial', props.product.id), {
        onSuccess: () => isEditingSKU.value = false,
        preserveScroll: true
    });
};

// Stock Adjust
const isAuditModalOpen = ref(false);
const auditForm = useForm({
    quantity: 1,
    direction: 'in',
    movement_type: 'Manual Adjustment',
    notes: ''
});

const submitAudit = () => {
    auditForm.post(route('products.adjust-stock', props.product.id), {
        onSuccess: () => {
            isAuditModalOpen.value = false;
            auditForm.reset();
        },
        preserveScroll: true
    });
};
</script>

<template>
    <Head :title="product.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-4">
                    <Link :href="route('products.index')" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </Link>
                    <h2 class="text-xl font-bold text-slate-800">{{ product.name }}</h2>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="isAuditModalOpen = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                        Adjust Stock
                    </button>
                    <Link :href="route('products.edit', product.id)" class="text-sm font-medium text-slate-500 hover:text-slate-800 px-4">
                        Edit Product
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Product Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Internal SKU</label>
                            <div v-if="!isEditingSKU" @click="isEditingSKU = true" class="text-lg font-bold text-slate-900 uppercase cursor-pointer hover:text-indigo-600 transition-colors flex items-center gap-2">
                                {{ product.sku || 'N/A' }}
                                <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2"></path></svg>
                            </div>
                            <div v-else class="flex gap-1">
                                <input v-model="skuForm.sku" class="bg-slate-50 border border-slate-200 rounded-lg text-sm py-1 px-3 w-full outline-none focus:ring-2 focus:ring-indigo-500/20" @blur="isEditingSKU = false" @keyup.enter="submitSKU" autofocus />
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Unit Price (Gross)</label>
                            <div class="text-2xl font-bold text-indigo-600 tabular-nums">€{{ formatCurrency(product.unit_price_gross) }}</div>
                            <span class="text-[10px] text-slate-400 font-medium">per sale cycle</span>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Product Category</label>
                            <div class="text-sm font-bold text-slate-900 lowercase first-letter:uppercase">{{ product.category?.name || 'Uncategorized' }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 rounded-xl p-8 text-white shadow-lg overflow-hidden relative group">
                    <div class="relative z-10">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Current Inventory</div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-6xl font-bold tabular-nums">{{ product.current_stock }}</span>
                            <span class="text-xs font-medium text-slate-500 uppercase tracking-widest">Units</span>
                        </div>
                        <div class="mt-6 pt-4 border-t border-white/5">
                             <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-wider mb-2">
                                <span class="text-slate-500 whitespace-nowrap">Warehouse Status</span>
                                <span :class="product.current_stock > 10 ? 'text-emerald-400' : 'text-rose-400'">{{ product.current_stock > 10 ? 'Good' : 'Low Stock' }}</span>
                            </div>
                            <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-indigo-500 h-full transition-all duration-1000" :style="`width: ${Math.min((product.current_stock / 100) * 100, 100)}%`"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-8 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Recent Stock Movements</h3>
                    <Link :href="route('products.movements', product.id)" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline italic">View Full History Log</Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50/50 border-b border-slate-100">
                            <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                <th class="px-8 py-3">Date</th>
                                <th class="px-8 py-3 text-center">Direction</th>
                                <th class="px-8 py-3 text-center">Quantity</th>
                                <th class="px-8 py-3 text-right">Reference / Type</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="move in product.stock_movements" :key="move.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-4 text-xs text-slate-500 tabular-nums">{{ formatDate(move.created_at) }}</td>
                                <td class="px-8 py-4 text-center">
                                    <span :class="move.direction === 'in' ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : 'text-rose-600 bg-rose-50 border-rose-100'" class="inline-block px-3 py-1 rounded-full text-[10px] font-bold border capitalize">
                                        {{ move.direction }}
                                    </span>
                                </td>
                                <td class="px-8 py-4 text-center text-sm font-bold text-slate-900 tabular-nums">
                                    {{ move.quantity }}
                                </td>
                                <td class="px-8 py-4 text-right text-[11px] font-medium text-slate-400 uppercase tracking-wider">
                                    {{ move.movement_type.replace('_', ' ') }}
                                </td>
                            </tr>
                            <tr v-if="!product.stock_movements?.length">
                                <td colspan="4" class="px-8 py-12 text-center text-sm text-slate-300 italic">No historical movements found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Adjustment Modal -->
        <div v-if="isAuditModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-sm p-8 shadow-2xl border border-slate-200">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-slate-900">Inventory Adjustment</h3>
                    <p class="text-xs text-slate-400 mt-1">Manual synchronization of warehouse stock levels.</p>
                </div>
                
                <form @submit.prevent="submitAudit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Vector</label>
                            <select v-model="auditForm.direction" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none">
                                <option value="in">In (Stock Add)</option>
                                <option value="out">Out (Stock Remove)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Quantity</label>
                            <input v-model="auditForm.quantity" type="number" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Rationale</label>
                        <textarea v-model="auditForm.notes" placeholder="Note why this adjustment was made..." class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none min-h-[100px] resize-none"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="isAuditModalOpen = false" class="flex-grow bg-white border border-slate-200 text-slate-600 font-bold py-2.5 rounded-lg text-xs uppercase tracking-wider hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="auditForm.processing" class="flex-grow bg-indigo-600 text-white font-bold py-2.5 rounded-lg text-xs uppercase tracking-wider hover:bg-indigo-700 shadow-lg">Save Adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
