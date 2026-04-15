<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    connection: {
        id: number;
        provider: string;
        label: string;
        is_active: boolean;
        credentials: Record<string, any>;
    };
}>();

const credentialFields: Record<string, Array<{ key: string; label: string; type: string; hint?: string }>> = {
    vivawallet: [
        { key: 'client_id',     label: 'Client ID',     type: 'text' },
        { key: 'client_secret', label: 'Client Secret', type: 'password', hint: 'Leave blank to keep existing' },
        { key: 'merchant_id',   label: 'Merchant ID',   type: 'text', hint: 'Optional' },
        { key: 'is_demo',       label: 'Use Demo / Sandbox environment', type: 'checkbox' },
    ],
    mypos: [
        { key: 'merchant_client_id',     label: 'Merchant Client ID',     type: 'text',     hint: 'Starts with cli_*' },
        { key: 'merchant_client_secret', label: 'Merchant Client Secret', type: 'password', hint: 'Leave blank to keep existing. Starts with sec_*' },
        { key: 'is_demo',                label: 'Use Demo / Sandbox environment', type: 'checkbox' },
    ],
    eurobank: [
        // No manual credentials needed - uses global App ID
        { key: 'account_id',    label: 'Account ID / IBAN', type: 'text', hint: 'Optional' },
    ],
    boc: [
        // No manual credentials needed - uses global App ID
        { key: 'account_id',      label: 'Account ID / IBAN', type: 'text',   hint: 'Optional' },
    ],
};

const fields = computed(() => credentialFields[props.connection.provider] ?? []);

const form = useForm({
    label:       props.connection.label,
    is_active:   props.connection.is_active,
    credentials: { ...props.connection.credentials },
});

const submit = () => {
    form.put(route('banking.update', props.connection.id));
};

const providerLabel: Record<string, string> = {
    vivawallet: 'Viva Wallet',
    mypos:      'myPOS',
    eurobank:   'Eurobank (Hellenic)',
    boc:        'Bank of Cyprus',
};
</script>

<template>
    <Head title="Edit Banking Connection" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('banking.index')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </Link>
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Edit Connection</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ providerLabel[connection.provider] ?? connection.provider }} — {{ connection.label }}</p>
                </div>
            </div>
        </template>

        <div class="max-w-2xl mx-auto pt-8 px-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Label -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Connection Label</label>
                        <input v-model="form.label" type="text" class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
                        <p v-if="form.errors.label" class="mt-1 text-xs text-rose-500">{{ form.errors.label }}</p>
                    </div>

                    <!-- Active toggle -->
                    <label class="flex items-center gap-3 cursor-pointer p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm font-medium text-slate-700">Connection Active</span>
                    </label>

                    <!-- Credential fields -->
                    <div v-if="fields.length > 0" class="space-y-4 pt-2 border-t border-slate-100">
                        <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pt-2">API Credentials</h3>
                        <p class="text-[10px] text-slate-400">Password fields show masked. Leave blank to keep the stored value unchanged.</p>

                        <template v-for="field in fields" :key="field.key">
                            <label v-if="field.type === 'checkbox'" class="flex items-center gap-3 cursor-pointer p-3 bg-amber-50 rounded-lg border border-amber-100">
                                <input type="checkbox" v-model="form.credentials[field.key]" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm font-medium text-amber-800">{{ field.label }}</span>
                            </label>
                            <div v-else>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">
                                    {{ field.label }}
                                    <span v-if="field.hint" class="font-normal text-slate-400 ml-1">— {{ field.hint }}</span>
                                </label>
                                <input
                                    :type="field.type"
                                    v-model="form.credentials[field.key]"
                                    :placeholder="field.type === 'password' ? '••••••••' : ''"
                                    class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all"
                                    autocomplete="off"
                                />
                            </div>
                        </template>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                        <p class="text-[10px] text-slate-400 max-w-xs">Credentials are re-encrypted on save.</p>
                        <div class="flex gap-3">
                            <Link :href="route('banking.index')" class="px-4 py-2 text-slate-500 text-sm font-medium hover:text-slate-700 transition-colors">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white text-sm font-bold py-2.5 px-8 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                                {{ form.processing ? 'Saving…' : 'Save Changes' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
