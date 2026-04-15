<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { withValidation } from '@/utils/validation';

const props = defineProps<{
    contacts: Array<any>,
    products: Array<any>,
    workspace: any
}>();

const form = useForm({
    contact_id: null as number | null,
    doc_type: 'invoice',
    date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    items: [
        { product_id: null as number | null, description: '', quantity: 1, unit_price_net: 0, unit_price_gross: 0, vat_rate: 19 }
    ],
    discount: 0,
    notes: '',
    attachment_files: [] as File[]
});

// Searchable Contacts
const contactSearch = ref('');
const showContactDropdown = ref(false);
const filteredContacts = computed(() => {
    if (!contactSearch.value) return props.contacts;
    const s = contactSearch.value.toLowerCase();
    return props.contacts.filter(c => 
        c.name.toLowerCase().includes(s) || 
        (c.company_name && c.company_name.toLowerCase().includes(s))
    );
});

const selectContact = (contact: any) => {
    form.contact_id = contact.id;
    contactSearch.value = contact.name + (contact.company_name ? ` (${contact.company_name})` : '');
    showContactDropdown.value = false;
};

// Searchable Products
const itemSearches = ref(['']);
const itemDropdowns = ref([false]);

const filteredProducts = (index: number) => {
    const s = itemSearches.value[index]?.toLowerCase() || '';
    if (!s) return props.products;
    return props.products.filter(p => 
        p.name.toLowerCase().includes(s) || 
        (p.sku && p.sku.toLowerCase().includes(s))
    );
};

const selectProduct = (index: number, product: any) => {
    const item = form.items[index];
    item.product_id = product.id;
    item.description = product.name;
    item.vat_rate = parseFloat(product.vat_rate || 19);
    
    if (product.unit_price_gross != null) {
        item.unit_price_gross = parseFloat(product.unit_price_gross);
        calculateNet(index);
    } else {
        item.unit_price_net = parseFloat(product.unit_price_net || 0);
        calculateGross(index);
    }
    
    itemSearches.value[index] = product.name;
    itemDropdowns.value[index] = false;
};

const calculateGross = (index: number) => {
    const item = form.items[index];
    item.unit_price_gross = parseFloat((item.unit_price_net * (1 + (item.vat_rate / 100))).toFixed(2));
};

const calculateNet = (index: number) => {
    const item = form.items[index];
    const rate = (item.vat_rate || 0) / 100;
    item.unit_price_net = parseFloat((item.unit_price_gross / (1 + rate)).toFixed(4));
};

const addItem = () => {
    form.items.push({ product_id: null, description: '', quantity: 1, unit_price_net: 0, unit_price_gross: 0, vat_rate: 19 });
    itemSearches.value.push('');
    itemDropdowns.value.push(false);
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
    itemSearches.value.splice(index, 1);
    itemDropdowns.value.splice(index, 1);
};

const subtotal = computed(() => form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price_net), 0));
const totalVat = computed(() => form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price_net * (item.vat_rate / 100)), 0));
const grandTotal = computed(() => (subtotal.value + totalVat.value) - form.discount);

const submit = () => form.post(route('invoices.store'));
</script>

<template>
    <Head title="Create New Invoice" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center gap-4">
                    <Link :href="route('invoices.index')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </Link>
                    <h2 class="text-xl font-semibold text-gray-800">New {{ form.doc_type.replace('_', ' ').toUpperCase() }} #{{ workspace.invoice_prefix }}{{ workspace.next_invoice_number }}</h2>
                </div>
                <button type="submit" form="invoice-form" :disabled="form.processing" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-indigo-700 transition-colors">
                    Issue Invoice
                </button>
            </div>
        </template>

        <div class="py-8 max-w-5xl mx-auto px-4">
            <form id="invoice-form" @submit.prevent="submit" class="space-y-6">
                <FormErrorSummary :errors="form.errors" />
                
                <!-- Basic Info Card -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="relative">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Customer</label>
                        <input 
                            v-model="contactSearch" 
                            @focus="showContactDropdown = true"
                            placeholder="Select client" 
                            :class="withValidation('w-full bg-gray-50 border border-gray-200 rounded-lg py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all text-sm', form.errors.contact_id)"
                        />
                        <div v-if="showContactDropdown" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 shadow-lg rounded-lg overflow-hidden max-h-60 overflow-y-auto">
                            <div 
                                v-for="c in filteredContacts" :key="c.id" 
                                @click="selectContact(c)"
                                class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                            >
                                <div class="text-sm font-medium text-gray-900">{{ c.name }}</div>
                                <div class="text-xs text-gray-500">{{ c.company_name || 'Individual' }}</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Document Type</label>
                        <select v-model="form.doc_type" class="w-full bg-gray-50 border border-gray-200 rounded-lg py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 outline-none text-sm font-bold">
                            <option value="invoice">TAX INVOICE</option>
                            <option value="proforma">PROFORMA INVOICE</option>
                            <option value="credit_note">CREDIT NOTE</option>
                            <option value="quote">OFFICIAL QUOTE</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Invoice Date</label>
                        <input v-model="form.date" type="date" :class="withValidation('w-full bg-gray-50 border border-gray-200 rounded-lg py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 outline-none text-sm', form.errors.date)" required />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Due Date</label>
                        <input v-model="form.due_date" type="date" :class="withValidation('w-full bg-gray-50 border border-gray-200 rounded-lg py-2 px-4 focus:ring-2 focus:ring-indigo-500/20 outline-none text-sm', form.errors.due_date)" />
                    </div>
                </div>

                <!-- Items Table -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Item Description</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase w-20">Qty</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase w-32">Net Price</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase w-32">Gross Price</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase w-20">VAT %</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(item, index) in form.items" :key="index">
                                <td class="px-6 py-4 space-y-2">
                                    <div class="relative">
                                        <input 
                                            v-model="itemSearches[index]" 
                                            @focus="itemDropdowns[index] = true"
                                            placeholder="Search product..." 
                                            class="w-full text-sm border-0 bg-transparent focus:ring-0 p-0 text-gray-900 font-medium placeholder-gray-300"
                                        />
                                        <div v-if="itemDropdowns[index]" class="absolute z-40 w-full mt-2 bg-white border border-gray-200 shadow-xl rounded-lg overflow-hidden max-h-48 overflow-y-auto">
                                            <div 
                                                v-for="p in filteredProducts(index)" 
                                                :key="p.id" 
                                                @click="selectProduct(index, p)"
                                                class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                                            >
                                                <div class="text-xs font-medium">{{ p.name }}</div>
                                                <div class="text-[10px] text-gray-400">SKU: {{ p.sku }} • €{{ p.unit_price_gross || '0' }} Gross</div>
                                            </div>
                                        </div>
                                    </div>
                                    <input v-model="item.description" type="text" placeholder="Entry description..." class="w-full text-xs border-0 bg-transparent focus:ring-0 p-0 text-gray-400 italic" required />
                                </td>
                                <td class="px-6 py-4">
                                    <input v-model.number="item.quantity" type="number" step="0.01" class="w-full text-sm border-0 bg-transparent focus:ring-0 p-0 text-gray-700" required />
                                </td>
                                <td class="px-6 py-4">
                                    <input v-model.number="item.unit_price_net" @input="calculateGross(index)" type="number" step="0.0001" class="w-full text-sm border-0 bg-transparent focus:ring-0 p-0 text-gray-700" required />
                                </td>
                                <td class="px-6 py-4">
                                    <input v-model.number="item.unit_price_gross" @input="calculateNet(index)" type="number" step="0.01" class="w-full text-sm border-0 bg-transparent focus:ring-0 p-0 text-indigo-600 font-semibold" required />
                                </td>
                                <td class="px-6 py-4">
                                    <input v-model.number="item.vat_rate" @input="calculateGross(index)" type="number" class="w-full text-sm border-0 bg-transparent focus:ring-0 p-0 text-gray-700" required />
                                </td>
                                <td class="px-6 py-4">
                                    <button v-if="form.items.length > 1" type="button" @click="removeItem(index)" class="text-gray-300 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="p-4 bg-gray-50/50 border-t border-gray-100">
                        <button type="button" @click="addItem" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 uppercase tracking-wider">
                            + Add Line Item
                        </button>
                    </div>
                </div>

                <!-- Totals & Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                         <label class="block text-xs font-semibold text-gray-500 uppercase">Supporting Files</label>
                         <input type="file" multiple @change="(e: any) => form.attachment_files = Array.from(e.target.files)" :class="withValidation('w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100', form.errors.attachment_files)" />
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Invoice Notes</label>
                        <textarea v-model="form.notes" rows="4" placeholder="Add specific notes for this invoice here..." :class="withValidation('w-full bg-gray-50 border border-gray-200 rounded-lg py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.notes)"></textarea>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-8 space-y-3">
                        <div class="flex justify-between text-sm text-gray-500 uppercase font-medium">
                            <span>Subtotal (Net)</span>
                            <span>€{{ subtotal.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-500 uppercase font-medium">
                            <span>Tax (VAT)</span>
                            <span>€{{ totalVat.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-t border-gray-100 text-rose-500 text-sm italic font-medium">
                            <span>Discount Amount</span>
                            <div class="flex items-center gap-2">
                                <span>-€</span>
                                <input v-model.number="form.discount" type="number" step="0.01" class="w-24 bg-gray-50 border border-gray-200 rounded py-1 px-2 text-right focus:ring-1 focus:ring-rose-500 outline-none text-sm" />
                            </div>
                        </div>
                        <div class="flex justify-between items-end pt-4 border-t border-gray-100">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Grand Total (Gross)</span>
                            <span class="text-4xl font-bold text-gray-900 tabular-nums">€{{ grandTotal.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-4">
                    <Link :href="route('invoices.index')" class="px-6 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Discard</Link>
                    <button type="submit" :disabled="form.processing" class="bg-slate-900 text-white px-10 py-3 rounded-xl font-bold uppercase tracking-widest text-xs hover:bg-black transition-all shadow-lg">
                        Issue Document
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>
