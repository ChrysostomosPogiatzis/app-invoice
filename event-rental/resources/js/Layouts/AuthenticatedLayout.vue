<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const workspace = computed(() => (page.props as any).workspace);
const flash = computed(() => (page.props as any).flash || { success: '', error: '' });
const isUserMenuOpen = ref(false);

// Auto-dismiss flash messages after 5 seconds
watch(flash, (newFlash) => {
    if (newFlash.success || newFlash.error) {
        setTimeout(() => {
            router.reload({ only: ['flash'] });
        }, 5000);
    }
});

const toggleUserMenu = () => {
    isUserMenuOpen.value = !isUserMenuOpen.value;
};

const closeUserMenu = () => {
    isUserMenuOpen.value = false;
};

const handleWindowClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement | null;

    if (!target?.closest('[data-user-menu]')) {
        closeUserMenu();
    }
};

onMounted(() => {
    window.addEventListener('click', handleWindowClick);
});

onBeforeUnmount(() => {
    window.removeEventListener('click', handleWindowClick);
});

const navItems = [
    { name: 'Dashboard', route: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', active: 'dashboard' },
    { name: 'Inventory', route: 'products.index', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', active: 'products.*' },
    { name: 'CRM', route: 'contacts.index', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', active: 'contacts.*' },
    { name: 'Call Logs', route: 'communications.index', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.129a11.042 11.042 0 005.516 5.516l1.129-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', active: 'communications.*' },
    { name: 'Team', route: 'workspace-users.index', icon: 'M17 20h5v-2a3 3 0 00-4.874-2.326M9 20H4v-2a3 3 0 015.126-2.326M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', active: 'workspace-users.*', requiresWorkspaceAdmin: true },
    { name: 'Quotes', route: 'quotes.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', active: 'quotes.*' },
    { name: 'Billing', route: 'invoices.index', icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', active: 'invoices.*' },
    { name: 'Expenses', route: 'expenses.index', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', active: 'expenses.*' },
    { name: 'Banking', route: 'banking.index', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', active: 'banking.*' },
    { name: 'Reports', route: 'reports.index', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', active: 'reports.*' },
];
const adminNavItem = { name: 'Admin Console', route: 'admin.index', icon: 'M9 12h6M9 16h6M9 8h6M4 6.5A2.5 2.5 0 016.5 4h11A2.5 2.5 0 0120 6.5v11a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 17.5v-11z', active: 'admin.*' };
const visibleNavItems = computed(() => navItems.filter((item) => !item.requiresWorkspaceAdmin || user.value.can_manage_workspace_users));
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex font-sans selection:bg-indigo-100 selection:text-indigo-900">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 fixed inset-y-0 left-0 z-50 flex flex-col">
            <!-- Brand -->
            <div class="h-16 px-6 flex items-center border-b border-slate-100">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                </div>
                <span class="font-bold text-slate-800 tracking-tight">Gravity ERP</span>
            </div>

            <!-- Navigation -->
            <nav class="flex-grow p-4 space-y-1">
                <Link 
                    v-for="item in visibleNavItems" 
                    :key="item.name" 
                    :href="route(item.route)"
                    :class="[
                        route().current(item.active) 
                        ? 'bg-indigo-50 text-indigo-700' 
                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                    ]"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                >
                    <svg class="w-4 h-4 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="route().current(item.active) ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600'"><path :d="item.icon" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    {{ item.name }}
                </Link>

                <div v-if="user.is_super_admin" class="pt-4 mt-4 border-t border-slate-100">
                    <Link
                        :href="route(adminNavItem.route)"
                        :class="[
                            route().current(adminNavItem.active)
                            ? 'bg-rose-50 text-rose-700'
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                        ]"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                    >
                        <svg class="w-4 h-4 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="route().current(adminNavItem.active) ? 'text-rose-600' : 'text-slate-400 group-hover:text-slate-600'"><path :d="adminNavItem.icon" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        {{ adminNavItem.name }}
                    </Link>
                </div>

                <div v-if="workspace?.features?.['crm_reminders']" class="pt-4 mt-4 border-t border-slate-100">
                    <Link 
                        :href="route('reminders.index')"
                        :class="[
                            route().current('reminders.index') 
                            ? 'bg-amber-50 text-amber-700' 
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                        ]"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                    >
                        <svg class="w-4 h-4 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="route().current('reminders.index') ? 'text-amber-600' : 'text-slate-400 group-hover:text-slate-600'"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        Reminders
                    </Link>
                </div>
            </nav>

            <!-- User -->
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                <div class="relative" data-user-menu>
                    <button
                        type="button"
                        @click.stop="toggleUserMenu"
                        class="w-full flex items-center gap-3 rounded-xl px-2 py-2 text-left transition-colors hover:bg-white/80"
                    >
                        <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center text-xs font-bold text-slate-500">
                            {{ user.name.charAt(0) }}
                        </div>
                        <div class="flex-grow min-w-0">
                            <div class="text-xs font-bold text-slate-900 truncate">{{ user.name }}</div>
                            <div class="text-[10px] text-slate-500 truncate">{{ user.email }}</div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="{ 'rotate-180': isUserMenuOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>

                    <div
                        v-if="isUserMenuOpen"
                        class="absolute bottom-full left-0 right-0 mb-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                    >
                        <Link
                            v-if="user.is_super_admin"
                            :href="route('admin.index')"
                            class="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50"
                            @click="closeUserMenu"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12h6M9 16h6M9 8h6M4 6.5A2.5 2.5 0 016.5 4h11A2.5 2.5 0 0120 6.5v11a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 17.5v-11z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            Admin Console
                        </Link>
                        <Link
                            :href="route('profile.edit')"
                            class="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50"
                            @click="closeUserMenu"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            Profile
                        </Link>
                        <Link
                            :href="route('settings.edit')"
                            class="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50"
                            @click="closeUserMenu"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M10.325 4.317a1 1 0 011.35-.936l.636.242a1 1 0 00.948-.129l.56-.421a1 1 0 011.42.183l.378.52a1 1 0 00.885.41l.64-.03a1 1 0 011.041.982l.02.643a1 1 0 00.498.815l.557.327a1 1 0 01.365 1.386l-.318.56a1 1 0 000 .988l.318.56a1 1 0 01-.365 1.386l-.557.327a1 1 0 00-.498.815l-.02.643a1 1 0 01-1.041.982l-.64-.03a1 1 0 00-.885.41l-.378.52a1 1 0 01-1.42.183l-.56-.421a1 1 0 00-.948-.129l-.636.242a1 1 0 01-1.35-.936l-.076-.697a1 1 0 00-.607-.805l-.61-.259a1 1 0 01-.593-1.306l.214-.61a1 1 0 00-.124-.939l-.415-.566a1 1 0 010-1.176l.415-.566a1 1 0 00.124-.939l-.214-.61a1 1 0 01.593-1.306l.61-.259a1 1 0 00.607-.805l.076-.697z"></path>
                                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            Workspace Settings
                        </Link>
                        <div class="border-t border-slate-100">
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="flex w-full items-center gap-3 px-4 py-3 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                Sign Out
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <main class="flex-grow pl-64">
            <!-- Flash Messages -->
            <div v-if="flash.success" class="fixed top-20 right-8 z-50 max-w-md animate-pulse">
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg shadow-lg flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                    <span class="text-sm font-medium">{{ flash.success }}</span>
                </div>
            </div>
            <div v-if="flash.error" class="fixed top-20 right-8 z-50 max-w-md">
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg shadow-lg flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                    <span class="text-sm font-medium">{{ flash.error }}</span>
                </div>
            </div>

            <!-- Header -->
            <header v-if="$slots.header" class="h-16 bg-white border-b border-slate-200 sticky top-0 z-40 flex items-center px-8">
                <slot name="header" />
            </header>

            <!-- Body -->
            <div class="p-8 min-h-screen">
                <slot />
            </div>
        </main>
    </div>
</template>
