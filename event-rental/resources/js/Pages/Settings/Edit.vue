<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { withValidation } from '@/utils/validation';

const props = defineProps<{
    workspace: any,
    tokens: Array<any>,
    flash?: any
}>();

const logoPreview = ref(props.workspace.logo_url || null);

const form = useForm({
    _method: 'PUT',
    company_name: props.workspace.company_name || '',
    vat_number: props.workspace.vat_number || '',
    address: props.workspace.address || '',
    phone: props.workspace.phone || '',
    email: props.workspace.email || '',
    currency: props.workspace.currency || 'EUR',
    iban: props.workspace.iban || '',
    bic: props.workspace.bic || '',
    tic_number: props.workspace.tic_number || '',
    logo: null as File | null,
    brand_color: props.workspace.brand_color || '#4F46E5',
    invoice_prefix: props.workspace.invoice_prefix || 'INV-',
    next_invoice_number: props.workspace.next_invoice_number || 1001,
    default_invoice_notes: props.workspace.default_invoice_notes || '',
    features: {
        maintenance: props.workspace.features.find((f: any) => f.feature_name === 'maintenance')?.is_enabled ?? true,
        crm_reminders: props.workspace.features.find((f: any) => f.feature_name === 'crm_reminders')?.is_enabled ?? true,
        call_intelligence: props.workspace.features.find((f: any) => f.feature_name === 'call_intelligence')?.is_enabled ?? false,
    }
});

const onFileChange = (e: any) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('settings.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
};

const tokenForm = useForm({
    token_name: ''
});

const generateToken = () => {
    tokenForm.post(route('settings.tokens.generate'), {
        preserveScroll: true,
        onSuccess: () => {
            tokenForm.reset();
        }
    });
};

const revokeToken = (id: number) => {
    if (confirm('Are you sure you want to revoke this API token? Access will be immediately cut off.')) {
        useForm({}).delete(route('settings.tokens.revoke', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Workspace Settings" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-4">
                <h2 class="text-xl font-bold text-slate-800">Workspace Settings</h2>
                <div class="text-xs text-slate-400 font-medium">Workspace ID: {{ workspace.id }}</div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- General Profile -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Company Information</h3>
                    <p class="text-xs text-slate-500 mt-1">Your company details and legal information</p>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <FormErrorSummary :errors="form.errors" />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Company Name</label>
                                <input v-model="form.company_name" type="text" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.company_name)" required />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">VAT Number</label>
                                <input v-model="form.vat_number" type="text" placeholder="EU123456789" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.vat_number)" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">TIC Number</label>
                                <input v-model="form.tic_number" type="text" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.tic_number)" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Address</label>
                                <input v-model="form.address" type="text" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.address)" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Phone</label>
                                <input v-model="form.phone" type="text" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.phone)" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Email</label>
                                <input v-model="form.email" type="email" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.email)" />
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Branding -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Branding</h3>
                    <p class="text-xs text-slate-500 mt-1">Logo and brand colors for invoices and quotes</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="flex items-start gap-6">
                            <div class="w-20 h-20 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
                                <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-2" />
                                <div v-else class="text-xs text-slate-400 font-medium">No Logo</div>
                            </div>
                            <div class="flex-grow">
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Company Logo</label>
                                <input type="file" @change="onFileChange" accept="image/*" class="block w-full text-sm text-slate-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-lg file:border-0
                                    file:text-xs file:font-medium
                                    file:bg-slate-100 file:text-slate-700
                                    hover:file:bg-slate-200 cursor-pointer" />
                                <p class="mt-2 text-xs text-slate-400">PNG, JPG or SVG - Max 2MB</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-2">Brand Color</label>
                            <div class="flex items-center gap-4">
                                <div class="relative w-12 h-12 rounded-lg overflow-hidden shadow-sm border border-slate-200">
                                    <input v-model="form.brand_color" type="color" class="absolute -inset-2 w-16 h-16 border-none cursor-pointer p-0 bg-transparent" />
                                </div>
                                <input v-model="form.brand_color" type="text" :class="withValidation('flex-1 rounded-lg border border-slate-200 py-2.5 px-4 text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.brand_color)" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Settings -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Financial Settings</h3>
                    <p class="text-xs text-slate-500 mt-1">Currency, banking, and invoice configuration</p>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Currency</label>
                                <select v-model="form.currency" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all bg-white', form.errors.currency)">
                                    <option value="EUR">Euro (€)</option>
                                    <option value="USD">US Dollar ($)</option>
                                    <option value="GBP">British Pound (£)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">IBAN</label>
                                <input v-model="form.iban" type="text" placeholder="CY..." :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.iban)" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">BIC / SWIFT</label>
                                <input v-model="form.bic" type="text" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.bic)" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-slate-100">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Invoice Prefix</label>
                                <input v-model="form.invoice_prefix" type="text" placeholder="INV-" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.invoice_prefix)" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Next Invoice Number</label>
                                <input v-model.number="form.next_invoice_number" type="number" :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.next_invoice_number)" />
                            </div>
                            <div class="md:col-span-3 pt-6 border-t border-slate-100">
                                <label class="block text-xs font-semibold text-slate-600 mb-2">Default Invoice Terms (Footer Notes)</label>
                                <textarea v-model="form.default_invoice_notes" rows="4" placeholder="e.g., Final ownership transfer contingent upon full settlement clearance..." :class="withValidation('w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all', form.errors.default_invoice_notes)"></textarea>
                                <p class="mt-2 text-[10px] text-slate-400">This text will appear at the bottom of every new invoice generated. You can still add custom notes per invoice.</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Features -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Features</h3>
                    <p class="text-xs text-slate-500 mt-1">Enable or disable workspace features</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="(enabled, name) in form.features" :key="name" 
                             class="flex items-center justify-between p-4 rounded-lg border border-slate-200 hover:border-indigo-300 transition-all cursor-pointer" 
                             @click="form.features[name] = !form.features[name]">
                            <div>
                                <div class="text-sm font-semibold text-slate-800 capitalize">{{ name.replace('_', ' ') }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ enabled ? 'Enabled' : 'Disabled' }}</div>
                            </div>
                            <div :class="enabled ? 'bg-indigo-600' : 'bg-slate-200'" class="w-11 h-6 rounded-full relative transition-colors">
                                <div :class="enabled ? 'translate-x-5' : 'translate-x-0.5'" class="absolute top-1 w-4 h-4 bg-white rounded-full transition-transform shadow-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Access -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Developer API Tokens</h3>
                    <p class="text-xs text-slate-500 mt-1">Generate personal access tokens to build external integrations</p>
                </div>
                <div class="p-6 space-y-6">
                    <!-- API URL Info -->
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-4">
                        <div class="text-[10px] font-bold text-slate-400 uppercase mb-1">Backend API Base URL</div>
                        <code class="text-xs text-indigo-600 font-mono select-all">{{ $page.props.app_url }}/api</code>
                        <div class="mt-2 text-[10px] text-slate-400">
                             <span class="font-bold">Local Tip:</span> For Emulators use <code class="text-indigo-500">http://10.0.2.2:8000/api</code>. For physical devices, use your computer's local IP (e.g., <code class="text-indigo-500">http://192.168.1.5:8000/api</code>).
                        </div>
                    </div>

                    <!-- New Token Box (If generated) -->
                    <div v-if="flash?.plain_text_token" class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 animate-pulse">
                        <div class="text-emerald-800 text-xs font-bold uppercase mb-2">New Token Created!</div>
                        <p class="text-xs text-emerald-600 mb-3">Copy this now. You won't see it again!</p>
                        <div class="flex items-center gap-2">
                            <code class="bg-white border border-emerald-200 px-3 py-1.5 rounded-md text-sm font-mono text-emerald-900 select-all flex-grow truncate">{{ flash.plain_text_token }}</code>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="flex-grow">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5 ml-1">Token Description</label>
                            <input v-model="tokenForm.token_name" type="text" placeholder="e.g., Mobile App, Zapier Integration" class="w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
                        </div>
                        <button @click="generateToken" :disabled="tokenForm.processing || !tokenForm.token_name" class="bg-slate-900 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-black transition-colors disabled:opacity-50">
                            Generate New Token
                        </button>
                    </div>

                    <!-- Existing Tokens List -->
                    <div v-if="tokens && tokens.length" class="border rounded-lg divide-y bg-slate-50 border-slate-200 overflow-hidden">
                        <div v-for="token in tokens" :key="token.id" class="p-4 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">{{ token.name }}</div>
                                <div class="text-[10px] text-slate-500 font-medium font-mono lowercase">Last used: {{ token.last_used_at || 'Never' }}</div>
                            </div>
                            <button @click="revokeToken(token.id)" class="text-rose-500 hover:text-rose-700 text-xs font-bold uppercase tracking-wider">Revoke</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4">
                <div class="flex items-center gap-2 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"></path></svg>
                    <span class="text-xs font-medium">Changes are saved automatically</span>
                </div>
                <button @click="submit" :disabled="form.processing" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
