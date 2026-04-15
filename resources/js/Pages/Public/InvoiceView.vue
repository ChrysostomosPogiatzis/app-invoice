<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import SignaturePad from '@/Components/SignaturePad.vue';

const props = defineProps<{
    invoice: any,
    token: string
}>();

const form = useForm({
    customer_name: '',
    signature_base64: ''
});

const isSigned = ref(false);

const handleSigned = (base64: string) => {
    form.signature_base64 = base64;
    submit();
};

const submit = () => {
    form.post(route('public.invoice.sign', props.token), {
        onSuccess: () => {
            isSigned.value = true;
        }
    });
};
</script>

<template>
    <Head title="Digital Invoice - Witbo Rental" />

    <div class="min-h-screen bg-gray-50 py-12 px-6 font-sans text-gray-900">
        <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
            <!-- Header Section -->
            <div class="p-10 border-b border-gray-50 flex justify-between items-center bg-white">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 mb-1 tracking-tight">WITBO RENTAL</h1>
                    <div class="text-xs uppercase font-bold text-indigo-600 tracking-widest">Digital Financial Document</div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] uppercase font-black text-gray-400 mb-1">Document Number</div>
                    <div class="text-2xl font-mono font-bold text-gray-800">{{ invoice.invoice_number }}</div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="p-10 space-y-12">
                <!-- Success State -->
                <div v-if="invoice.customer_signature_png" class="bg-emerald-50 border border-emerald-100 p-6 rounded-2xl flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-500 text-white rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <div class="font-black text-emerald-800 uppercase text-xs tracking-wider">Document Executed</div>
                        <div class="text-sm text-emerald-600">This invoice has been digitally signed and is now legally binding.</div>
                    </div>
                </div>

                <!-- Client/Company Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 border-b border-gray-50 pb-12">
                    <div>
                        <h4 class="text-[10px] font-black uppercase text-gray-400 mb-4 tracking-widest">Client Recipient</h4>
                        <div class="text-xl font-bold text-gray-900">{{ invoice.contact?.name || 'N/A' }}</div>
                        <div class="text-gray-500 text-sm mt-1 font-medium">{{ invoice.contact?.email }}</div>
                    </div>
                    <div class="md:text-right">
                        <h4 class="text-[10px] font-black uppercase text-gray-400 mb-4 tracking-widest">Issuance Details</h4>
                        <div class="text-lg font-bold text-gray-800">{{ new Date(invoice.date).toLocaleDateString() }}</div>
                        <div class="text-rose-500 text-sm font-bold mt-1">Due Date: {{ new Date(invoice.due_date).toLocaleDateString() }}</div>
                    </div>
                </div>

                <!-- Product Table -->
                <div>
                    <h4 class="text-[10px] font-black uppercase text-gray-400 mb-4 tracking-widest">Line Items</h4>
                    <div class="overflow-hidden border border-gray-100 rounded-2xl bg-gray-50/30">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50 text-[10px] uppercase font-black tracking-widest text-gray-400">
                                    <th class="px-6 py-4">Description</th>
                                    <th class="px-6 py-4 text-center">Qty</th>
                                    <th class="px-6 py-4 text-right">Unit Price</th>
                                    <th class="px-6 py-4 text-right pr-8">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <tr v-for="item in invoice.items" :key="item.id" class="hover:bg-white transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-800">{{ item.description }}</td>
                                    <td class="px-6 py-4 text-center text-gray-500 font-medium">{{ item.quantity }}</td>
                                    <td class="px-6 py-4 text-right text-gray-500 font-medium">€{{ item.unit_price_net }}</td>
                                    <td class="px-6 py-4 text-right font-black text-indigo-600 pr-8">€{{ item.total_gross }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="flex justify-end pr-8">
                    <div class="w-72 space-y-3">
                        <div class="flex justify-between text-xs font-bold text-gray-400 uppercase tracking-widest">
                            <span>Subtotal (Net)</span>
                            <span class="text-gray-900">€{{ invoice.subtotal_net }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-bold text-gray-400 uppercase tracking-widest pb-3 border-b border-gray-100">
                            <span>Total VAT</span>
                            <span class="text-gray-900">€{{ invoice.total_vat_amount }}</span>
                        </div>
                        <div class="flex justify-between items-baseline pt-2">
                            <span class="text-sm font-black uppercase tracking-widest text-gray-400">Amount Due</span>
                            <span class="text-3xl font-black text-indigo-600 tracking-tighter">€{{ invoice.grand_total_gross }}</span>
                        </div>
                    </div>
                </div>

                <!-- Legal Signature Pad -->
                <div v-if="!invoice.customer_signature_png" class="pt-12 border-t border-gray-100">
                    <div class="mb-10">
                        <label class="block text-[10px] font-black text-gray-400 mb-3 uppercase tracking-widest">Full Legal Name</label>
                        <input v-model="form.customer_name" type="text" 
                               class="w-full bg-white border-2 border-gray-100 focus:border-indigo-500 rounded-2xl p-4 text-lg outline-none transition-all placeholder-gray-300 font-bold"
                               placeholder="Type your name to unlock signature..." />
                    </div>
                    
                    <SignaturePad @signed="handleSigned" />
                    
                    <div class="mt-6 text-[10px] text-gray-400 leading-relaxed max-w-2xl">
                        By signing this document, I acknowledge receipt of the services/goods listed above and agree to the terms and conditions of Witbo Rental ERP. This digital signature is legally binding under applicable electronic commerce laws.
                    </div>
                </div>

                <div v-else class="pt-12 border-t border-gray-100">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-8 bg-gray-50 p-8 rounded-3xl border border-gray-100">
                        <div class="text-center md:text-left">
                            <h4 class="text-[10px] font-black text-emerald-600 mb-2 uppercase tracking-widest">Digital Authentication</h4>
                            <div class="text-xl font-bold text-gray-900">{{ invoice.customer_signature_name }}</div>
                            <div class="text-[10px] text-gray-400 mt-1 uppercase font-bold tracking-tighter">
                                Signed on {{ new Date(invoice.signature_timestamp).toLocaleString() }}
                            </div>
                            <div class="mt-4 font-mono text-[8px] text-gray-300 uppercase truncate max-w-[200px]">CRC-ID: {{ token }}</div>
                        </div>
                        <div class="p-4 bg-white rounded-2xl shadow-sm border border-gray-100">
                            <img :src="invoice.customer_signature_png" class="h-24 object-contain" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Metadata -->
            <div class="p-8 bg-gray-50 text-[9px] text-gray-400 flex justify-between border-t border-gray-100 font-bold uppercase tracking-widest">
                <div>Source IP: {{ invoice.signature_ip || 'AUDIT_PENDING' }}</div>
                <div>WITBO LTD SECURE DOCUMENT SUITE v2.0</div>
            </div>
        </div>
    </div>
</template>
