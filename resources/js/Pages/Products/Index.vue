<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    products: {
        data: any[],
        links: any[],
        total: number
    },
    categories: Array<{ id: number, name: string }>,
    filters: { search: string, category_id: string, sort?: string, direction?: string }
}>();

const search = ref(props.filters.search || '');
const categoryId = ref(props.filters.category_id || '');
const sortField = ref(props.filters.sort || 'name');
const sortDirection = ref(props.filters.direction || 'asc');

const triggerSort = (field: string) => {
    const newDirection = sortField.value === field && sortDirection.value === 'asc' ? 'desc' : 'asc';
    sortField.value = field;
    sortDirection.value = newDirection;
    router.get(route('products.index'), {
        search: search.value,
        category_id: categoryId.value,
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

watch([search, categoryId], () => {
    router.get(route('products.index'), { 
        search: search.value, 
        category_id: categoryId.value,
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
    <Head title="Inventory" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-4">
                <h2 class="text-xl font-bold text-slate-800">Inventory Management</h2>
                
                <div class="flex items-center gap-3 w-full lg:w-auto">
                    <!-- Search -->
                    <div class="relative flex-grow lg:flex-grow-0">
                        <input v-model="search" type="text" placeholder="Search SKU or Name..." class="bg-white border border-slate-200 rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none w-full lg:w-64 transition-all" />
                        <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round"></path></svg>
                    </div>

                    <!-- Category -->
                    <select v-model="categoryId" class="bg-white border border-slate-200 rounded-lg py-2 pl-3 pr-8 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>

                    <Link :href="route('products.create')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap">
                        New Product
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
                                Product Info
                                <span class="text-slate-400 text-[10px]">{{ sortIcon('name') }}</span>
                            </span>
                        </th>
                        <th 
                            class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition-colors select-none"
                            @click="triggerSort('sku')"
                        >
                            <span class="flex items-center gap-1">
                                SKU
                                <span class="text-slate-400 text-[10px]">{{ sortIcon('sku') }}</span>
                            </span>
                        </th>
                        <th 
                            class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider text-center cursor-pointer hover:bg-slate-100 transition-colors select-none"
                            @click="triggerSort('current_stock')"
                        >
                            <span class="flex items-center justify-center gap-1">
                                Stock
                                <span class="text-slate-400 text-[10px]">{{ sortIcon('current_stock') }}</span>
                            </span>
                        </th>
                        <th 
                            class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider text-right cursor-pointer hover:bg-slate-100 transition-colors select-none"
                            @click="triggerSort('unit_price_gross')"
                        >
                            <span class="flex items-center justify-end gap-1">
                                Unit Price
                                <span class="text-slate-400 text-[10px]">{{ sortIcon('unit_price_gross') }}</span>
                            </span>
                        </th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="p in products.data" :key="p.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-500 font-bold border border-slate-200">
                                    {{ p.name.charAt(0) }}
                                </div>
                                <div>
                                    <Link :href="route('products.show', p)" class="text-sm font-semibold text-slate-900 hover:text-indigo-600 transition-colors">
                                        {{ p.name }}
                                    </Link>
                                    <div v-if="p.category" class="text-[11px] text-slate-400 font-medium">{{ p.category.name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ p.sku }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="inline-flex flex-col">
                                <span class="text-sm font-bold" :class="p.current_stock < 5 ? 'text-rose-500' : 'text-slate-900'">
                                    {{ p.current_stock }}
                                </span>
                                <span class="text-[10px] text-slate-400 font-medium">available</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-900">€{{ Number(p.unit_price_gross || 0).toFixed(2) }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">gross</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <Link :href="route('products.edit', p)" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round"></path></svg>
                                </Link>
                                <Link :href="route('products.show', p)" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="2" stroke-linecap="round"></path></svg>
                                </Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="products.data.length === 0">
                        <td colspan="5" class="px-6 py-20 text-center text-sm text-slate-400 italic">No products found.</td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">Showing {{ products.data.length }} of {{ products.total }} items</span>
                <div class="flex gap-2">
                    <Link v-for="link in products.links" :key="link.label" :href="link.url || '#'" 
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