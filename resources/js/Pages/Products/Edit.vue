<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { withValidation } from '@/utils/validation';

const props = defineProps<{
    product: any,
    categories: Array<any>
}>();

const form = useForm({
    name: props.product.name,
    sku: props.product.sku,
    product_type: props.product.product_type,
    product_category_id: props.product.product_category_id,
    unit_price_gross: props.product.unit_price_gross,
    vat_rate: props.product.vat_rate,
    current_stock: props.product.current_stock,
    purchase_price: props.product.purchase_price || 0,
    acquisition_date: props.product.acquisition_date || new Date().toISOString().split('T')[0],
});

const calculatedNet = computed(() => {
    if (!form.unit_price_gross) return '0.00';
    return (form.unit_price_gross / (1 + (form.vat_rate / 100))).toFixed(2);
});

const submit = () => {
    form.put(route('products.update', props.product.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('products.index')" class="text-gray-400 hover:text-gray-600 font-medium italic">
                    ← Back to Inventory
                </Link>
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight italic uppercase">Refine Asset Profile</h2>
            </div>
        </template>

        <div class="py-12 px-6">
            <div class="max-w-4xl mx-auto bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-10">
                <form @submit.prevent="submit" class="space-y-8">
                    <FormErrorSummary :errors="form.errors" />

                    <!-- Basic Identifiers -->
                    <div class="flex flex-col md:flex-row gap-8">
                        <div class="flex-grow">
                            <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest italic">Item Identification</label>
                            <input v-model="form.name" type="text" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold', form.errors.name)" required />
                            <div v-if="form.errors.name" class="text-rose-600 text-[10px] mt-2 italic font-bold uppercase">{{ form.errors.name }}</div>
                        </div>
                        <div class="w-full md:w-48">
                            <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest italic">Inventory SKU</label>
                            <input v-model="form.sku" type="text" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-mono font-bold', form.errors.sku)" />
                        </div>
                    </div>

                    <!-- Logistics & Classification -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                             <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest italic">Asset Classification</label>
                             <select v-model="form.product_category_id" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold appearance-none', form.errors.product_category_id)">
                                <option :value="null">Uncategorized</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                             </select>
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest italic">Asset Logic</label>
                            <select v-model="form.product_type" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold appearance-none', form.errors.product_type)">
                                <option value="physical">Physical Inventory (Stocked)</option>
                                <option value="service">Labor & Services (External)</option>
                            </select>
                        </div>
                         <div>
                            <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest italic">Acquisition Date</label>
                            <input v-model="form.acquisition_date" type="date" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold', form.errors.acquisition_date)" />
                        </div>
                    </div>

                    <!-- Financial & Stock Management -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-indigo-50 p-8 rounded-[2.5rem] border border-indigo-100/50 flex flex-col justify-center">
                            <label class="block text-[10px] uppercase font-black text-indigo-500 mb-4 tracking-widest italic font-bold">Unit Final Price (Gross €)</label>
                            <input v-model.number="form.unit_price_gross" type="number" step="0.01" :class="withValidation('w-full rounded-2xl border-2 border-indigo-200 bg-white py-4 px-6 focus:border-indigo-600 outline-none transition-all font-black text-xl text-indigo-700', form.errors.unit_price_gross)" required placeholder="0.00" />
                            <div class="mt-4 text-[9px] text-indigo-400 font-bold uppercase tracking-widest italic">
                                Calculated Net: €{{ calculatedNet }} (Excl. {{ form.vat_rate }}% VAT)
                            </div>
                        </div>
                        <div class="bg-gray-50 p-8 rounded-[2.5rem] border border-gray-100 space-y-6">
                            <div>
                                <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest italic">VAT Directive (%)</label>
                                <input v-model.number="form.vat_rate" type="number" step="0.1" :class="withValidation('w-full rounded-2xl border-2 border-gray-200 bg-white py-4 px-6 focus:border-indigo-500 outline-none transition-all font-bold', form.errors.vat_rate)" required />
                            </div>
                             <div>
                                <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest italic">Asset Acquisition Cost (€)</label>
                                <input v-model.number="form.purchase_price" type="number" step="0.01" :class="withValidation('w-full rounded-2xl border-2 border-gray-200 bg-white py-4 px-6 focus:border-emerald-600 outline-none transition-all font-bold', form.errors.purchase_price)" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                         <div class="md:col-span-1">
                            <label class="block text-[8px] uppercase font-black text-gray-400 mb-3 tracking-widest italic">Stock Quantity</label>
                            <input v-model.number="form.current_stock" type="number" step="1" :class="withValidation('w-full rounded-2xl border-2 border-slate-900 bg-slate-900 py-3 px-5 focus:border-indigo-500 outline-none text-white transition-all font-black', form.errors.current_stock)" required />
                        </div>
                    </div>

                    <div class="pt-10 flex justify-between items-center border-t border-gray-50 mt-10">
                        <div class="text-[10px] text-gray-300 font-bold uppercase tracking-widest italic">
                            Modified: {{ new Date(product.updated_at).toLocaleDateString() }}
                        </div>
                        <div class="flex gap-4">
                            <Link :href="route('products.index')" class="px-6 py-3 text-gray-400 font-bold hover:text-gray-900 transition-colors uppercase text-xs tracking-widest font-black">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-black text-white font-black py-4 px-10 rounded-2xl shadow-xl shadow-indigo-100 transition-all disabled:opacity-50 uppercase text-xs tracking-widest">
                                {{ form.processing ? 'Syncing...' : 'Update Inventory Record' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
