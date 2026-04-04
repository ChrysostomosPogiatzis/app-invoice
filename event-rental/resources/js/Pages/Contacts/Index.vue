<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    contacts: {
        data: any[],
        links: any[],
        total: number
    },
    filters: { search: string, type: string, sort?: string, direction?: string }
}>();

const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');
const sortField = ref(props.filters.sort || 'name');
const sortDirection = ref(props.filters.direction || 'asc');

const triggerSort = (field: string) => {
    const newDirection = sortField.value === field && sortDirection.value === 'asc' ? 'desc' : 'asc';
    sortField.value = field;
    sortDirection.value = newDirection;
    router.get(route('contacts.index'), {
        search: search.value,
        type: typeFilter.value,
        sort: field,
        direction: newDirection
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

const sortIcon = (field: string) => {
    if (sortField.value !== field) return '↕';
    return sortDirection.value === 'asc' ? '↑' : '↓';
};

watch([search, typeFilter], () => {
    router.get(route('contacts.index'), { 
        search: search.value, 
        type: typeFilter.value,
        sort: sortField.value,
        direction: sortDirection.value
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
});
</script>

<template>
    <Head title="CRM - Contacts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-4">
                <h2 class="text-xl font-bold text-slate-800">Contact Management</h2>
                
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <!-- Search -->
                    <div class="relative flex-grow lg:flex-grow-0">
                        <input v-model="search" type="text" placeholder="Search contacts..." class="bg-white border border-slate-200 rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none w-full lg:w-64 transition-all" />
                        <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round"></path></svg>
                    </div>

                    <!-- Type Filter -->
                    <select v-model="typeFilter" class="bg-white border border-slate-200 rounded-lg py-2 pl-3 pr-8 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                        <option value="">All Types</option>
                        <option value="customer">Customers</option>
                        <option value="lead">Leads</option>
                        <option value="vendor">Vendors</option>
                    </select>

                    <Link :href="route('contacts.create')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap">
                        New Contact
                    </Link>
                </div>
            </div>
        </template>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th 
                            class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition-colors select-none"
                            @click="triggerSort('name')"
                        >
                            <span class="flex items-center gap-1">
                                Contact Info
                                <span class="text-slate-400 text-[10px]">{{ sortIcon('name') }}</span>
                            </span>
                        </th>
                        <th 
                            class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition-colors select-none"
                            @click="triggerSort('contact_type')"
                        >
                            <span class="flex items-center gap-1">
                                Type
                                <span class="text-slate-400 text-[10px]">{{ sortIcon('contact_type') }}</span>
                            </span>
                        </th>
                        <th 
                            class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition-colors select-none"
                            @click="triggerSort('email')"
                        >
                            <span class="flex items-center gap-1">
                                Communication
                                <span class="text-slate-400 text-[10px]">{{ sortIcon('email') }}</span>
                            </span>
                        </th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider text-right">Summary</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="contact in contacts.data" :key="contact.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 font-bold border border-slate-200">
                                    {{ contact.name.charAt(0) }}
                                </div>
                                <div>
                                    <Link :href="route('contacts.show', contact)" class="text-sm font-semibold text-slate-900 hover:text-indigo-600 transition-colors">
                                        {{ contact.name }}
                                    </Link>
                                    <div class="text-[11px] text-slate-400 font-medium line-clamp-1">{{ contact.company_name || 'Individual' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 capitalize">
                                {{ contact.contact_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            <div class="flex flex-col gap-0.5">
                                <div class="flex items-center gap-2">
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2"></path></svg>
                                    <span class="text-[11px]">{{ contact.email || '—' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-width="2"></path></svg>
                                    <span class="text-[11px]">{{ contact.mobile_number || '—' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                             <div class="flex flex-col items-end gap-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ contact.invoices_count || 0 }} Invoices</span>
                                <span v-if="contact.reminders_count > 0" class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">{{ contact.reminders_count }} Reminders</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <Link :href="route('contacts.show', contact)" class="p-2 text-slate-400 hover:text-slate-900 transition-colors inline-block">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="2" stroke-linecap="round"></path></svg>
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="contacts.data.length === 0">
                        <td colspan="5" class="px-6 py-20 text-center text-sm text-slate-400 italic">No contacts found.</td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">Showing {{ contacts.data.length }} of {{ contacts.total }} items</span>
                <div class="flex gap-2">
                    <Link v-for="link in contacts.links" :key="link.label" :href="link.url || '#'" 
                          v-html="link.label"
                          :class="[
                              link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 hover:text-slate-700 border-slate-200',
                              !link.url ? 'opacity-30 cursor-not-allowed' : ''
                          ]"
                          class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all border shadow-sm" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>