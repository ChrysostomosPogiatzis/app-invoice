<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    content: string;
}>();
</script>

<template>
    <Head :title="title + ' - Gravity ERP'" />

    <div class="min-h-screen bg-[#FDFDFD] text-slate-900 font-sans selection:bg-indigo-600 selection:text-white">
        <!-- Minimal Navigation -->
        <nav class="h-20 border-b border-slate-100 px-6 md:px-12 flex items-center justify-between sticky top-0 bg-white/80 backdrop-blur-md z-50">
            <Link :href="route('dashboard')" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                </div>
                <span class="font-bold text-lg tracking-tight uppercase">Gravity <span class="text-indigo-600">ERP</span></span>
            </Link>
            <div class="flex items-center gap-6">
                <Link :href="route('dashboard')" class="text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-slate-900 transition-colors">Home</Link>
            </div>
        </nav>

        <section class="py-24 px-6 md:px-12 max-w-4xl mx-auto">
            <div class="mb-12 border-b border-slate-200 pb-8">
                <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-slate-900 mb-4">{{ title }}</h1>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Last Updated: April 2026</p>
            </div>

            <div class="prose prose-slate prose-lg max-w-none text-slate-600 leading-relaxed font-medium">
                <p>{{ content }}</p>
                <br>
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 text-sm">
                    <h3 class="font-bold text-slate-900 mb-2 uppercase tracking-widest text-xs">Company Details</h3>
                    <p class="mb-1 text-slate-500"><strong>Gravity ERP Ltd.</strong></p>
                    <p class="mb-1 text-slate-500">Limassol Avenue 15, 2112 Nicosia, Cyprus</p>
                    <p class="mb-1 text-slate-500">Company Reg: HE 123456</p>
                    <p class="text-slate-500">VAT Number: CY12345678Z</p>
                </div>
            </div>
        </section>

        <!-- Minimal Footer -->
        <footer class="py-12 px-6 md:px-12 max-w-7xl mx-auto border-t border-slate-100">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="text-[10px] font-bold uppercase tracking-widest text-slate-300">
                    &copy; 2026 Gravity ERP Ltd. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>
