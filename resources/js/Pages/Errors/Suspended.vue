<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps<{
    message: string
}>();

const { props } = usePage();

const switchWorkspace = (e: Event) => {
    const target = e.target as HTMLSelectElement;
    if (target.value) {
        router.post(route('workspaces.switch'), { workspace_id: target.value });
    }
};

/**
 * Standard Form Submission for myPOS.
 * We use a hidden form to bypass Inertia's XHR handling, 
 * which ensures the browser can handle the POST redirect correctly.
 */
const submitPayment = () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('billing.checkout');
    
    // Add CSRF token
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
    }

    document.body.appendChild(form);
    form.submit();
};
</script>

<template>
    <Head title="Account Inactive" />

    <div class="min-h-screen bg-slate-950 flex items-center justify-center p-6 text-center">
        <div class="max-w-md w-full animate-in zoom-in-95 duration-700">
            <div class="w-24 h-24 bg-rose-500/20 rounded-full flex items-center justify-center mx-auto mb-8 border border-rose-500/30">
                <svg class="w-10 h-10 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </div>
            
            <h1 class="text-3xl font-black text-white tracking-tight uppercase mb-4 italic leading-none">Business <span class="text-rose-500 underline decoration-8 underline-offset-4">Suspended</span>.</h1>
            <p class="text-slate-400 font-bold mb-10 leading-relaxed italic">{{ message }}</p>

            <div class="space-y-4">
                 <button 
                    @click="submitPayment"
                    class="block w-full bg-emerald-500 text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-xl shadow-emerald-500/20 mb-6 flex items-center justify-center gap-2"
                 >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    Pay & Unlock Node Instantly
                 </button>
                 <div v-if="($page.props.auth?.user?.workspaces?.length ?? 0) > 1" class="bg-slate-900 p-6 rounded-3xl border border-slate-800 mb-6 text-left">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Switch to Active Node</label>
                    <div class="relative group">
                        <select 
                            @change="switchWorkspace"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-xs font-bold text-slate-300 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all cursor-pointer appearance-none shadow-xl"
                        >
                            <option disabled selected>Select another business...</option>
                            <option v-for="w in $page.props.auth.user.workspaces" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </div>
                    </div>
                 </div>

                 <a href="mailto:support@witbo.com.cy" class="block w-full bg-slate-100 text-slate-900 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-white transition-all shadow-xl">Contact Billing Support</a>
                 <Link :href="route('logout')" method="post" as="button" class="block w-full bg-slate-900 text-slate-500 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition-all border border-slate-800">Sign Out</Link>
            </div>
            
            <div class="mt-20">
                <span class="text-[9px] font-black text-slate-700 uppercase tracking-[0.5em] group cursor-default">Powered by <span class="text-indigo-500">Gravity ERP</span></span>
            </div>
        </div>
    </div>
</template>
