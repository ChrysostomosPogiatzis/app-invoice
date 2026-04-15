<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const features = [
    {
        title: 'Centralized CRM',
        desc: 'Advanced customer relationship mapping with integrated communication logs and fuzzy search.',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    },
    {
        title: 'Financial Engine',
        desc: 'Professional multi-currency invoicing, expense management, and automated PDF delivery.',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        title: 'Cyprus Payroll',
        desc: 'Compliant payroll cycles with SI, GESI, and PAYE (Tax) calculations specifically for 2026 regulations.',
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        title: 'Banking Sync',
        desc: 'Direct synchronization with payment providers for real-time transaction reconciliation.',
        icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    },
    {
        title: 'Inventory Control',
        desc: 'Real-time stock management with SKUs, digital movement logs, and automated low-stock alerts.',
        icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    },
    {
        title: 'Cloud Cluster',
        desc: 'Secure, multi-tier node infrastructure ensuring high availability and encrypted platform data.',
        icon: 'M13 10V3L4 14h7v7l9-11h-7z',
    }
];

const tiers = [
    {
        name: 'Freelancer',
        price: '35',
        desc: 'Optimized for solo operators and independent contractors.',
        features: ['1 Personnel Node', 'Basic Invoicing', 'CRM Essentials', 'Banking Sync', 'Cyprus Pay Compliance'],
        cta: 'Start Free Trial',
        popular: false
    },
    {
        name: 'Small Biz',
        price: '75',
        desc: 'The industry standard for growing small teams and family businesses.',
        features: ['5 Personnel Nodes', 'Unlimited CRM', 'Expense Management', 'Team Collaboration', 'Priority API Access'],
        cta: 'Launch Node',
        popular: true
    },
    {
        name: 'Enterprise',
        price: 'Custom',
        desc: 'Bespoke infrastructure for high-scale organizations.',
        features: ['Unlimited Scale', 'Advanced Analytics', 'Full Audit Logging', 'Bespoke Integration', 'Dedicated Manager'],
        cta: 'Contact Sales',
        popular: false
    }
];
</script>

<template>
    <Head title="Gravity ERP - Business Management Reimagined" />

    <div class="min-h-screen bg-[#FDFDFD] text-slate-900 font-sans selection:bg-indigo-600 selection:text-white">
        <!-- Minimal Navigation -->
        <nav class="h-20 border-b border-slate-100 px-6 md:px-12 flex items-center justify-between sticky top-0 bg-white/80 backdrop-blur-md z-50">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                </div>
                <span class="font-bold text-lg tracking-tight uppercase">Gravity <span class="text-indigo-600">ERP</span></span>
            </div>
            <div class="flex items-center gap-6">
                <Link :href="route('login')" class="text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-slate-900 transition-colors">Sign In</Link>
                <Link :href="route('register')" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-slate-900 transition-all shadow-sm">
                    Register Node
                </Link>
            </div>
        </nav>

        <!-- Simplified Hero Section -->
        <header class="pt-24 pb-32 px-6 md:px-12 max-w-6xl mx-auto">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 border border-indigo-100 text-[10px] font-bold uppercase tracking-widest text-indigo-700 mb-12">
                Unified Business Infrastructure &bull; 2026 Edition
            </div>

            <h1 class="text-5xl md:text-7xl font-bold tracking-tight leading-[1.05] text-slate-900 mb-8 max-w-4xl">
                The Operating System for <br/>
                <span class="text-indigo-600">Modern Enterprise.</span>
            </h1>

            <p class="text-xl text-slate-500 font-medium max-w-2xl leading-relaxed mb-12">
                Clean, reliable, and specialized for the Cyprus market. Gravity ERP unifies CRM, Billing, Payroll, and Inventory into a single digital core.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 mb-24">
                <Link :href="route('register')" class="bg-slate-900 text-white px-10 py-5 rounded-2xl text-xs font-bold uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl shadow-slate-900/10">
                    Get Free Access
                </Link>
                <div class="bg-white border border-slate-200 text-slate-600 px-10 py-5 rounded-2xl text-xs font-bold uppercase tracking-widest hover:bg-slate-50 transition-all cursor-pointer">
                    View Demo
                </div>
            </div>

            <!-- Single Line Divider -->
            <div class="w-full h-px bg-slate-100"></div>
        </header>

        <!-- Features Grid - Simple & Professional -->
        <section class="py-24 px-6 md:px-12 max-w-6xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                <div v-for="f in features" :key="f.title" class="group">
                    <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 mb-6 transition-all group-hover:bg-indigo-600 group-hover:text-white group-hover:border-indigo-600 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path :d="f.icon" stroke-width="2" stroke-linecap="round"></path></svg>
                    </div>
                    <h4 class="text-lg font-bold text-slate-900 mb-3 tracking-tight">{{ f.title }}</h4>
                    <p class="text-sm text-slate-500 leading-relaxed font-medium">{{ f.desc }}</p>
                </div>
            </div>
        </section>

        <!-- Tiered Pricing - Professional Cards -->
        <section class="py-32 px-6 md:px-12 max-w-7xl mx-auto border-t border-slate-100">
            <div class="text-center mb-24">
                <h2 class="text-xs font-bold uppercase tracking-[0.4em] text-slate-400 mb-4">Scalable Infrastructure</h2>
                <h3 class="text-4xl font-bold tracking-tight text-slate-900">Choose your node capacity.</h3>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                <div v-for="tier in tiers" :key="tier.name" 
                    :class="[tier.popular ? 'border-2 border-indigo-600 bg-white ring-8 ring-indigo-50 shadow-2xl' : 'border border-slate-100 bg-slate-50/30 shadow-sm']"
                    class="p-10 rounded-[2.5rem] transition-all relative"
                >
                    <div v-if="tier.popular" class="absolute -top-4 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-[0.2em] px-4 py-1.5 rounded-full shadow-lg">Most Popular</div>
                    
                    <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-8">{{ tier.name }}</h4>
                    
                    <div v-if="tier.price !== 'Custom'" class="flex items-baseline gap-1 mb-8">
                        <span class="text-xs font-bold text-slate-400">€</span>
                        <span class="text-5xl font-bold tracking-tighter text-slate-900">{{ tier.price }}</span>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">/mo</span>
                    </div>
                    <div v-else class="mb-8">
                        <span class="text-3xl font-bold tracking-tight text-slate-900 italic">Enterprise</span>
                    </div>

                    <p class="text-xs text-slate-500 font-medium mb-10 leading-relaxed">{{ tier.desc }}</p>
                    
                    <div class="space-y-4 mb-12">
                        <div v-for="feat in tier.features" :key="feat" class="flex items-center gap-3 text-xs font-bold text-slate-700 tracking-tight">
                            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3"></path></svg>
                            {{ feat }}
                        </div>
                    </div>

                    <button :class="[tier.popular ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-600/20' : 'bg-slate-900 text-white']" class="w-full py-4.5 rounded-xl text-xs font-bold uppercase tracking-widest hover:scale-[1.02] transition-transform active:scale-95 shadow-lg">
                        {{ tier.cta }}
                    </button>
                </div>
            </div>
        </section>

        <!-- Compliant Footer -->
        <footer class="py-24 px-6 md:px-12 max-w-7xl mx-auto border-t border-slate-100">
            <div class="flex flex-col md:flex-row justify-between items-start gap-12">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-indigo-600 rounded flex items-center justify-center text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round"></path></svg>
                        </div>
                        <span class="font-bold text-sm uppercase tracking-tight">Gravity <span class="text-indigo-600">ERP</span></span>
                    </div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-400 font-medium">
                        Gravity ERP Ltd.<br/>
                        Limassol Avenue 15, 2112 Nicosia, Cyprus<br/>
                        Reg. HE 123456 &bull; VAT CY12345678Z
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:flex gap-8 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">
                    <div class="flex flex-col gap-3">
                        <Link :href="route('legal.show', 'terms')" class="hover:text-slate-900 transition-colors">Terms & Conditions</Link>
                        <Link :href="route('legal.show', 'privacy')" class="hover:text-slate-900 transition-colors">Privacy Policy</Link>
                        <Link :href="route('legal.show', 'delivery')" class="hover:text-slate-900 transition-colors">Delivery Terms</Link>
                    </div>
                    <div class="flex flex-col gap-3">
                        <Link :href="route('legal.show', 'refund')" class="hover:text-slate-900 transition-colors">Refund Policy</Link>
                        <Link :href="route('legal.show', 'return')" class="hover:text-slate-900 transition-colors">Return Policy</Link>
                        <Link :href="route('legal.show', 'cancellation')" class="hover:text-slate-900 transition-colors">Cancellation Policy</Link>
                    </div>
                </div>
            </div>
            
            <div class="mt-16 text-center text-[10px] font-bold uppercase tracking-widest text-slate-300">
                &copy; {{ new Date().getFullYear() }} Gravity ERP Ltd. All rights reserved.
            </div>
        </footer>
    </div>
</template>

<style scoped>
.py-4\.5 { padding-top: 1.125rem; padding-bottom: 1.125rem; }
</style>
