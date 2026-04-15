<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { withValidation } from '@/utils/validation';

const form = useForm({
    name: '',
    company_name: '',
    email: '',
    mobile_number: '',
    vat_number: '',
    address: '',
    contact_type: 'customer',
    general_info: '',
});

const submit = () => {
    form.post(route('contacts.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Add Contact" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('contacts.index')" class="text-gray-400 hover:text-gray-600">
                    ← Back to CRM
                </Link>
                <h2 class="text-2xl font-bold text-gray-800">New Contact</h2>
            </div>
        </template>

        <div class="py-12 px-6">
            <div class="max-w-3xl mx-auto bg-white rounded-3xl shadow-sm border border-gray-100 p-10">
                <form @submit.prevent="submit" class="space-y-8">
                    <FormErrorSummary :errors="form.errors" />

                    <!-- Name & Company -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest">Full Name</label>
                            <input v-model="form.name" type="text" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold', form.errors.name)" required />
                            <div v-if="form.errors.name" class="text-rose-600 text-xs mt-2">{{ form.errors.name }}</div>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest">Company (Optional)</label>
                            <input v-model="form.company_name" type="text" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold', form.errors.company_name)" />
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest">Email Address</label>
                            <input v-model="form.email" type="email" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold', form.errors.email)" />
                            <div v-if="form.errors.email" class="text-rose-600 text-xs mt-2">{{ form.errors.email }}</div>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest">Mobile Number</label>
                            <input v-model="form.mobile_number" type="text" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold', form.errors.mobile_number)" />
                        </div>
                    </div>

                    <!-- Business Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest">VAT / Tax ID</label>
                            <input v-model="form.vat_number" type="text" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold', form.errors.vat_number)" />
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest">Contact Group</label>
                            <select v-model="form.contact_type" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold appearance-none', form.errors.contact_type)">
                                <option value="customer">Customer</option>
                                <option value="lead">Pipeline Lead</option>
                                <option value="vendor">Equipment Vendor</option>
                                <option value="individual">Individual Professional</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest italic">Billing Address</label>
                        <textarea v-model="form.address" :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold', form.errors.address)" rows="3"></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase font-black text-gray-400 mb-3 tracking-widest italic">Internal Background / Biography</label>
                        <textarea v-model="form.general_info" placeholder="Historical context, specific preferences, or behavioral notes..." :class="withValidation('w-full rounded-2xl border-2 border-gray-50 bg-gray-50/50 py-3 px-5 focus:border-indigo-500 outline-none transition-all font-bold', form.errors.general_info)" rows="3"></textarea>
                    </div>

                    <div class="pt-10 flex justify-end border-t border-gray-50 mt-10">
                        <div class="flex gap-4">
                            <Link :href="route('contacts.index')" class="px-6 py-3 text-gray-500 font-bold hover:text-gray-900 transition-colors">Discard</Link>
                            <button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 px-10 rounded-2xl shadow-xl shadow-indigo-100 transition-all disabled:opacity-50">
                                {{ form.processing ? 'Saving...' : 'Add to Database' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
