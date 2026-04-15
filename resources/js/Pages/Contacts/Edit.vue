<script setup lang="ts">
import { useForm, Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { withValidation } from '@/utils/validation';

const props = defineProps<{
    contact: any
}>();

const form = useForm({
    name: props.contact.name || '',
    company_name: props.contact.company_name || '',
    email: props.contact.email || '',
    mobile_number: props.contact.mobile_number || '',
    vat_number: props.contact.vat_number || '',
    address: props.contact.address || '',
    contact_type: props.contact.contact_type || 'customer',
    general_info: props.contact.general_info || '',
});

const submit = () => {
    form.put(route('contacts.update', props.contact.id), {
        preserveScroll: true,
    });
};

const deleteContact = () => {
    if (confirm('Are you sure you want to remove this contact from the database? This action cannot be undone.')) {
        router.delete(route('contacts.destroy', props.contact.id));
    }
};
</script>

<template>
    <Head :title="`Edit ${contact.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-4">
                    <Link :href="route('contacts.show', contact.id)" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </Link>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">CRM Profile</div>
                        <h2 class="text-2xl font-bold text-slate-900">Edit {{ contact.name }}</h2>
                    </div>
                </div>
                <div class="rounded-full bg-slate-100 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-700">
                    {{ form.contact_type }}
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="grid gap-6 lg:grid-cols-[1.7fr,0.9fr]">
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-8 py-6">
                        <h3 class="text-lg font-bold text-slate-900">Profile Details</h3>
                        <p class="mt-1 text-sm text-slate-500">Update the contact information used across CRM, invoices, and reminders.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-8 px-8 py-8">
                        <FormErrorSummary :errors="form.errors" />

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Full Name</label>
                                <input v-model="form.name" type="text" :class="withValidation('w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-indigo-500 focus:bg-white', form.errors.name)" required />
                                <div v-if="form.errors.name" class="mt-2 text-sm text-rose-600">{{ form.errors.name }}</div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Company</label>
                                <input v-model="form.company_name" type="text" :class="withValidation('w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-indigo-500 focus:bg-white', form.errors.company_name)" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Email Address</label>
                                <input v-model="form.email" type="email" :class="withValidation('w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-indigo-500 focus:bg-white', form.errors.email)" />
                                <div v-if="form.errors.email" class="mt-2 text-sm text-rose-600">{{ form.errors.email }}</div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Phone / Mobile</label>
                                <input v-model="form.mobile_number" type="text" :class="withValidation('w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-indigo-500 focus:bg-white', form.errors.mobile_number)" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">VAT / Tax ID</label>
                                <input v-model="form.vat_number" type="text" :class="withValidation('w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-indigo-500 focus:bg-white', form.errors.vat_number)" />
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Contact Type</label>
                                <select v-model="form.contact_type" :class="withValidation('w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-indigo-500 focus:bg-white', form.errors.contact_type)">
                                    <option value="customer">Customer</option>
                                    <option value="lead">Lead</option>
                                    <option value="vendor">Vendor</option>
                                    <option value="individual">Individual</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Address</label>
                            <textarea v-model="form.address" rows="3" :class="withValidation('w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-indigo-500 focus:bg-white', form.errors.address)"></textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Internal Notes</label>
                            <textarea v-model="form.general_info" rows="5" :class="withValidation('w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:bg-white', form.errors.general_info)" placeholder="Preferences, relationship history, payment notes, or delivery constraints..."></textarea>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                            <button type="button" @click="deleteContact" class="text-left text-sm font-semibold text-rose-600 transition hover:text-rose-700">
                                Delete Contact
                            </button>
                            <div class="flex gap-3">
                                <Link :href="route('contacts.show', contact.id)" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                                    Cancel
                                </Link>
                                <button type="submit" :disabled="form.processing" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    {{ form.processing ? 'Saving Changes...' : 'Save Changes' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

                <aside class="space-y-6">
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-slate-100 text-xl font-bold text-slate-500">
                                {{ contact.name.charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-slate-900">{{ contact.name }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ contact.company_name || 'Independent contact' }}</div>
                                <div class="mt-3 inline-flex rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-slate-600">
                                    {{ form.contact_type }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-900">Quick View</h3>
                        <div class="mt-5 space-y-4">
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Email</div>
                                <div class="mt-1 text-sm text-slate-700">{{ form.email || 'No email set' }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Phone</div>
                                <div class="mt-1 text-sm text-slate-700">{{ form.mobile_number || 'No phone set' }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Tax ID</div>
                                <div class="mt-1 text-sm text-slate-700">{{ form.vat_number || 'No tax ID set' }}</div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-indigo-100 bg-indigo-50 p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-indigo-900">What This Affects</h3>
                        <p class="mt-3 text-sm leading-6 text-indigo-800">
                            Updates here flow through CRM records, invoice client details, reminders, and communication history for this contact.
                        </p>
                    </section>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
