<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import QrcodeVue from 'qrcode.vue';
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
    si_employee_rate: props.workspace.si_employee_rate ?? 8.8,
    si_employer_rate: props.workspace.si_employer_rate ?? 8.8,
    gesi_employee_rate: props.workspace.gesi_employee_rate ?? 2.65,
    gesi_employer_rate: props.workspace.gesi_employer_rate ?? 2.90,
    provident_employee_rate: props.workspace.provident_employee_rate ?? 0,
    provident_employer_rate: props.workspace.provident_employer_rate ?? 0,
    redundancy_rate: props.workspace.redundancy_rate ?? 1.20,
    training_rate: props.workspace.training_rate ?? 0.50,
    cohesion_rate: props.workspace.cohesion_rate ?? 2.00,
    holiday_rate: props.workspace.holiday_rate ?? 0,
    annual_tax_threshold: props.workspace.annual_tax_threshold ?? 22000,
    tax_brackets: props.workspace.tax_brackets || [
        {threshold: 0, rate: 0},
        {threshold: 22000, rate: 20},
        {threshold: 32000, rate: 25},
        {threshold: 42000, rate: 30},
        {threshold: 72000, rate: 35}
    ],
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
            <!-- Node Subscription & Trial Monitor -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden animate-in fade-in duration-700">
                <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-indigo-50/10">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 leading-none mb-1">Node License & Renewal</h3>
                        <p class="text-[11px] text-slate-500 font-medium">Monitoring your business node's evaluation lifecycle and tier limits.</p>
                    </div>
                    <div class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-400">
                        Node ID: #{{ workspace.id }}
                    </div>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Current Tier -->
                    <div class="space-y-2">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Active Service Tier</div>
                        <div class="flex items-center gap-2">
                            <span :class="[
                                workspace.tier === 'enterprise' ? 'bg-amber-100 text-amber-700 border-amber-200' : 
                                workspace.tier === 'professional' ? 'bg-purple-100 text-purple-700 border-purple-200' : 
                                'bg-indigo-100 text-indigo-700 border-indigo-200'
                            ]" class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest border">
                                {{ workspace.tier === 'starter' ? 'Freelancer Node' : workspace.tier === 'professional' ? 'Small Biz Node' : 'Enterprise Node' }}
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-500 font-medium">Node capped at {{ workspace.tier === 'starter' ? '1 Staff' : workspace.tier === 'professional' ? '5 Staff' : 'Unlimited' }} capacity.</p>
                    </div>

                    <!-- License Clock -->
                    <div class="space-y-2">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">License Heartbeat</div>
                        <div v-if="workspace.tier === 'enterprise'" class="flex items-center gap-2 text-emerald-600 font-bold text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-7.618 3.040A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2" stroke-linecap="round"/></svg>
                            Lifetime Managed Node
                        </div>
                        <div v-else class="flex items-center gap-3">
                            <div class="px-3 py-1.5 bg-slate-900 text-white rounded-xl text-xs font-black tabular-nums">
                                {{ ($page.props as any).workspace.trial_days_left }} Days Left
                            </div>
                            <div class="text-xs font-bold text-slate-700">Renews: {{ workspace.trial_ends_at ? new Date(workspace.trial_ends_at).toLocaleDateString('en-GB') : '---' }}</div>
                        </div>
                        <p class="text-[10px] text-slate-500 font-medium leading-relaxed" v-if="workspace.tier !== 'enterprise'">
                            Your current license term is active. Infrastructure lockout occurs when the clock reaches 0.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end">
                        <button disabled class="px-6 py-2.5 bg-slate-50 border border-slate-200 text-slate-400 rounded-xl text-[10px] font-black uppercase tracking-widest cursor-not-allowed">
                            Upgrade Managed License
                        </button>
                    </div>
                </div>
            </div>

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

            <!-- Financial Settings -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden text-sm">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Payroll & Legal Configuration (Cyprus Law)</h3>
                    <p class="text-xs text-slate-500 mt-1">Configure Social Insurance, GESI, and Tax thresholds for staff wages</p>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Social Insurance (Employee %)</label>
                                <input v-model.number="form.si_employee_rate" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none', form.errors.si_employee_rate)" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Social Insurance (Employer %)</label>
                                <input v-model.number="form.si_employer_rate" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none', form.errors.si_employer_rate)" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">GESY (Employee %)</label>
                                <input v-model.number="form.gesi_employee_rate" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none', form.errors.gesi_employee_rate)" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">GESY (Employer %)</label>
                                <input v-model.number="form.gesi_employer_rate" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none', form.errors.gesi_employer_rate)" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Provident Fund (Employee %)</label>
                                <input v-model.number="form.provident_employee_rate" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none', form.errors.provident_employee_rate)" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Provident Fund (Employer %)</label>
                                <input v-model.number="form.provident_employer_rate" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none', form.errors.provident_employer_rate)" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Redundancy Fund (%)</label>
                                <input v-model.number="form.redundancy_rate" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none', form.errors.redundancy_rate)" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Social Cohesion Fund (%)</label>
                                <input v-model.number="form.cohesion_rate" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none', form.errors.cohesion_rate)" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Industrial Training Fund (%)</label>
                                <input v-model.number="form.training_rate" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none', form.errors.training_rate)" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Holiday Fund (%)</label>
                                <input v-model.number="form.holiday_rate" type="number" step="0.01" :class="withValidation('w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500/20 outline-none', form.errors.holiday_rate)" />
                            </div>
                        </div>
                        <div class="pt-6 border-t border-slate-100">
                            <div class="mb-4 flex justify-between items-center">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase">Progressive Tax Brackets (Annual Thresholds)</label>
                                <button type="button" @click="form.tax_brackets.push({threshold: 0, rate: 0})" class="bg-indigo-50 text-indigo-600 font-bold px-3 py-1 rounded text-[9px] uppercase hover:bg-indigo-100 transition-all border border-indigo-100">+ Add Bracket</button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="(bracket, index) in form.tax_brackets" :key="index" class="flex items-center gap-3 bg-slate-50/50 p-3 rounded-xl border border-slate-100 group transition-all hover:border-indigo-200">
                                    <div class="flex-grow grid grid-cols-2 gap-2">
                                        <div class="relative">
                                            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[8px] font-bold text-slate-400">OVER (€)</div>
                                            <input v-model.number="bracket.threshold" type="number" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 pl-14 pr-3 text-xs font-bold focus:ring-2 focus:ring-indigo-500/20 outline-none" />
                                        </div>
                                        <div class="relative">
                                            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[8px] font-bold text-slate-400">RATE</div>
                                            <input v-model.number="bracket.rate" type="number" step="0.1" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 pl-12 pr-6 text-xs font-bold focus:ring-2 focus:ring-indigo-500/20 outline-none" />
                                            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">%</div>
                                        </div>
                                    </div>
                                    <button v-if="form.tax_brackets.length > 1" @click="form.tax_brackets.splice(index, 1)" type="button" class="text-rose-300 hover:text-rose-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <p class="mt-4 text-[10px] text-slate-400 font-medium italic">Tax is calculated on (Annual Gross - Social Insurance - GESY). Brackets are applied progressively.</p>
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
                <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Developer & API Infrastructure</h3>
                        <p class="text-xs text-slate-500 mt-1">Generate personal access tokens to build external integrations</p>
                    </div>
                    <div class="flex items-center gap-2">
                         <span :class="[workspace.tier === 'enterprise' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700']" class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest border border-current/10">
                             {{ workspace.tier === 'starter' ? 'Freelancer: Locked' : workspace.tier === 'professional' ? 'Small Biz: 2 Slots' : 'Enterprise' }}
                         </span>
                    </div>
                </div>
                <!-- Locked State for Freelancer -->
                <div v-if="workspace.tier === 'starter'" class="p-12 text-center bg-slate-50/50">
                    <div class="w-12 h-12 bg-white rounded-xl border border-slate-200 flex items-center justify-center mx-auto mb-4 shadow-sm">
                        <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-800 tracking-tight uppercase">API Infrastructure Locked</h4>
                    <p class="text-xs text-slate-400 mt-2 max-w-xs mx-auto leading-relaxed font-medium">Headless API nodes and external app pairings require a <span class="text-indigo-600 font-bold">Small Biz</span> license upgrade.</p>
                </div>

                <div v-else class="p-6 space-y-6">
                    <!-- API URL Info -->
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-4">
                        <div class="text-[10px] font-bold text-slate-400 uppercase mb-1">Backend API Base URL</div>
                        <code class="text-xs text-indigo-600 font-mono select-all">{{ $page.props.app_url }}/api</code>
                        <div class="mt-2 text-[10px] text-slate-400">
                             <span class="font-bold">Local Tip:</span> For Emulators use <code class="text-indigo-500">http://10.0.2.2:8000/api</code>. For physical devices, use your computer's local IP (e.g., <code class="text-indigo-500">http://192.168.1.5:8000/api</code>).
                        </div>
                    </div>

                    <!-- New Token Box (If generated) -->
                    <div v-if="flash?.plain_text_token" class="bg-indigo-50 border border-indigo-200 rounded-xl p-6 shadow-sm ring-4 ring-indigo-500/10 mb-6">
                        <div class="flex flex-col md:flex-row gap-8 items-center">
                            <div class="bg-white p-3 rounded-lg shadow-sm border border-indigo-100 flex-shrink-0">
                                <QrcodeVue 
                                    :value="JSON.stringify({ 
                                        url: $page.props.app_url + '/api', 
                                        token: flash.plain_text_token 
                                    })" 
                                    :size="160" 
                                    level="H" 
                                    render-as="svg"
                                />
                                <div class="text-[10px] text-center font-bold text-slate-400 mt-2 uppercase tracking-tight">Scan with mobile app</div>
                            </div>
                            <div class="flex-grow">
                                <div class="text-indigo-800 text-sm font-bold uppercase mb-2 flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-indigo-500 animate-ping"></div>
                                    New Pairing Token Created!
                                </div>
                                <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                                    Your personal access token has been generated. Use the QR code to quickly pair your mobile device or manually copy the token below. 
                                    <span class="font-bold text-indigo-600 block mt-1 underline decoration-indigo-200 underline-offset-4">You won't see this token again after you leave this page!</span>
                                </p>
                                <div class="flex items-center gap-2 mb-2">
                                    <code class="bg-white border border-indigo-200 px-3 py-2 rounded-md text-sm font-mono text-indigo-900 select-all flex-grow shadow-inner break-all">{{ flash.plain_text_token }}</code>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="flex-grow">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5 ml-1">Token Description</label>
                            <input v-model="tokenForm.token_name" type="text" placeholder="e.g., Mobile App, Zapier Integration" class="w-full rounded-lg border border-slate-200 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
                        </div>
                        <button 
                            @click="generateToken" 
                            :disabled="tokenForm.processing || !tokenForm.token_name || (workspace.tier === 'professional' && tokens.length >= 2)" 
                            class="bg-slate-900 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-black transition-colors disabled:opacity-50"
                        >
                            {{ (workspace.tier === 'professional' && tokens.length >= 2) ? 'Limit Reached' : 'Generate Token' }}
                        </button>
                    </div>
                    <p v-if="workspace.tier === 'professional' && tokens.length >= 2" class="text-[10px] font-black text-rose-500 uppercase tracking-widest text-center mt-2 animate-pulse">Node Capacity Reached (2/2 Tokens Used)</p>

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
