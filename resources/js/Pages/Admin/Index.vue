<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps<{
    stats: {
        users: number;
        super_admins: number;
        workspaces: number;
        total_revenue: number;
        monthly_revenue: number;
    };
    users: Array<{
        id: number;
        name: string;
        email: string;
        is_super_admin: boolean;
        current_workspace_id: number | null;
        workspaces: Array<{
            id: number;
            company_name: string;
            role: string;
        }>;
    }>;
    workspaces: Array<{
        id: number;
        company_name: string;
        email: string | null;
        currency: string;
        is_active: boolean;
        tier: string;
        owner_id: number | null;
        users_count: number;
        products_count: number;
        trial_ends_at: string | null;
        last_billed_at: string | null;
        trial_expired: boolean;
        created_at: string | null;
        payments: Array<{
            id: number;
            billed_at: string;
            extended_until: string;
            payment_method: string;
        }>;
    }>;
}>();

const isLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { isLoaded.value = true; }, 100);
});

const confirmRenewal = (workspace: any) => {
    if (confirm(`Confirm monthly payment received for ${workspace.company_name}? This will extend their license by 30 days.`)) {
        router.post(route('admin.workspaces.record-payment', workspace.id), {}, {
            preserveScroll: true
        });
    }
};

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showUserModal = ref(false);
const showSubscriptionManager = ref(false);
const showHistoryModal = ref(false);
const activeHistoryWorkspace = ref<any>(null);

const viewHistory = (workspace: any) => {
    activeHistoryWorkspace.value = workspace;
    showHistoryModal.value = true;
};

const editingUser = ref<any>(null);
const activeTab = ref('workspaces');

const formatDate = (date: string | null) => date ? new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '---';

const form = useForm({
    company_name: '',
    email: '',
    currency: 'EUR',
    tier: 'starter',
    owner_id: null as number | null,
});

const editForm = useForm({
    id: 0,
    company_name: '',
    email: '',
    currency: 'EUR',
    tier: 'starter',
    trial_ends_at: null as string | null,
    owner_id: null as number | null,
});

const userForm = useForm({
    id: 0,
    name: '',
    email: '',
    password: '',
    is_super_admin: false,
});

const submit = () => {
    form.post(route('admin.workspaces.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};

const openEditModal = (workspace: any) => {
    editForm.id = workspace.id;
    editForm.company_name = workspace.company_name;
    editForm.email = workspace.email;
    editForm.currency = workspace.currency;
    editForm.tier = workspace.tier;
    editForm.trial_ends_at = workspace.trial_ends_at;
    editForm.owner_id = workspace.owner_id;
    showEditModal.value = true;
};

const updateWorkspace = () => {
    editForm.patch(route('admin.workspaces.update', editForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
        }
    });
};

const openUserModal = (user: any = null) => {
    editingUser.value = user;
    if (user) {
        userForm.id = user.id;
        userForm.name = user.name;
        userForm.email = user.email;
        userForm.password = '';
        userForm.is_super_admin = user.is_super_admin;
    } else {
        userForm.reset();
    }
    showUserModal.value = true;
};

const submitUser = () => {
    if (editingUser.value) {
        userForm.patch(route('admin.users.update', userForm.id), {
            onSuccess: () => {
                showUserModal.value = false;
                userForm.reset();
            }
        });
    } else {
        userForm.post(route('admin.users.store'), {
            onSuccess: () => {
                showUserModal.value = false;
                userForm.reset();
            }
        });
    }
};

const toggleWorkspaceStatus = (id: number) => {
    if (confirm('CAUTION: System override initiated. Toggle workspace activation status?')) {
        router.post(route('admin.workspaces.toggle-status', id));
    }
};

const toggleUserAdmin = (id: number) => {
    if (confirm('AUTHORIZATION ALERT: Modify administrative protocol for this user?')) {
        router.post(route('admin.users.toggle-admin', id));
    }
};

const kpis = computed(() => [
    { label: 'Total Personnel', value: props.stats.users.toString(), icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', tone: 'text-white', bg: 'from-indigo-600 to-indigo-900', status: 'Active Network' },
    { label: 'Global Revenue', value: '€' + props.stats.total_revenue.toLocaleString(), icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', tone: 'text-slate-900', bg: 'from-white to-emerald-50/30', status: 'All-Time' },
    { label: 'Monthly MRR', value: '€' + props.stats.monthly_revenue.toLocaleString(), icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', tone: 'text-slate-900', bg: 'from-white to-indigo-50/30', status: 'Active Month' },
    { label: 'Protocol Nodes', value: props.stats.workspaces.toString(), icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', tone: 'text-rose-600', bg: 'from-white to-rose-50/30', status: 'Provisioned' },
]);
</script>

<template>
    <Head title="Admin Console | Platform Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">System Protocol</h2>
                    <p class="text-xs text-slate-500 font-medium">Global Infrastructure & Entity Node Management</p>
                </div>
                <div class="flex items-center gap-3">
                    <button 
                        @click="openUserModal()"
                        class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round"/></svg>
                        Add Authority
                    </button>
                    <button 
                        @click="showCreateModal = true"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition-all flex items-center gap-2 shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"/></svg>
                        Provision Node
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-8 animate-in fade-in duration-700">
            <!-- Ambient Background Glows (Subtler) -->
            <div class="fixed top-0 right-0 w-[400px] h-[400px] bg-indigo-500/5 blur-[100px] pointer-events-none"></div>
            <div class="fixed bottom-0 left-0 w-[400px] h-[400px] bg-rose-500/5 blur-[100px] pointer-events-none"></div>

            <div class="px-8 w-full max-w-[1600px] mx-auto space-y-8 relative z-10 transition-all duration-1000" :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
                <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <div v-for="(item, i) in kpis" :key="item.label" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm transition-all hover:shadow-md">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path></svg>
                            </div>
                            <span class="text-[10px] font-bold text-indigo-600 px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-100">{{ item.status }}</span>
                        </div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ item.label }}</p>
                        <h3 class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ item.value }}</h3>
                    </div>
                </section>

                <!-- Tab Controller -->
                <div class="mb-8 p-1 bg-slate-100 rounded-2xl w-fit flex gap-2">
                    <button v-for="t in ['workspaces', 'personnel', 'subscriptions']" :key="t" @click="activeTab = t"
                        :class="[activeTab === t ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600']"
                        class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                    >
                        {{ t === 'subscriptions' ? 'Node Subscriptions' : t }}
                    </button>
                </div>

                <div v-if="activeTab === 'workspaces' || activeTab === 'personnel'" class="grid grid-cols-1 xl:grid-cols-[1fr_400px] gap-8 align-top">
                    <!-- Left: Personnel Management -->
                    <div v-if="activeTab === 'personnel'" class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden min-h-[600px] xl:col-span-2">
                        <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 leading-none mb-1">Platform Authorities</h3>
                                <p class="text-xs text-slate-500 font-medium">System Access & Global Permissions</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <button @click="openUserModal()" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all">New Authority</button>
                            </div>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <div v-for="user in users" :key="user.id" class="px-8 py-6 hover:bg-slate-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="relative">
                                            <div class="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-lg shadow-sm">{{ user.name.charAt(0) }}</div>
                                            <div v-if="user.is_super_admin" class="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 rounded-full border-2 border-white shadow-sm"></div>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-bold text-slate-900">{{ user.name }}</h4>
                                                <span v-if="user.is_super_admin" class="bg-rose-50 text-rose-600 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border border-rose-100">Super Admin</span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 font-medium">{{ user.email }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="openUserModal(user)" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round"></path></svg>
                                        </button>
                                        <button @click="toggleUserAdmin(user.id)" 
                                            :class="user.is_super_admin ? 'text-rose-600 border-rose-100 bg-rose-50 hover:bg-rose-100' : 'text-slate-600 border-slate-200 bg-white hover:bg-slate-50'"
                                            class="text-[10px] font-bold uppercase tracking-wider border px-4 py-2 rounded-lg transition-all shadow-sm"
                                        >
                                            {{ user.is_super_admin ? 'Demote' : 'Promote' }}
                                        </button>
                                    </div>
                                </div>
                                <div v-if="user.workspaces.length" class="mt-4 flex flex-wrap gap-2">
                                    <div v-for="w in user.workspaces" :key="w.id" class="px-2.5 py-1 rounded-md bg-slate-50 border border-slate-100 text-[10px] font-medium text-slate-600 flex items-center gap-2">
                                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                            {{ w.company_name }} <span class="text-slate-300">/</span> <span class="text-indigo-600 font-semibold">{{ w.role }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nodes Channel (Workspaces) -->
                    <div v-if="activeTab === 'workspaces'" class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden flex flex-col h-fit xl:col-span-2">
                        <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 uppercase tracking-tight">Active Nodes</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Infrastructure Monitor</p>
                            </div>
                            <button @click="showCreateModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all">Provision Node</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-px bg-slate-100">
                             <div v-for="w in workspaces" :key="w.id" class="bg-white p-8 group transition-all hover:bg-slate-50/50">
                                <div class="flex items-start justify-between mb-6">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ w.company_name }}</h4>
                                            <span v-if="w.trial_expired" class="bg-rose-50 text-rose-600 px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider border border-rose-100">Expired</span>
                                        </div>
                                        <div class="text-[10px] font-medium text-slate-400">{{ w.email || 'No contact email' }}</div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="openEditModal(w)" class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-slate-400 hover:text-indigo-600 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round"></path></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex justify-between items-end">
                                    <div class="space-y-1">
                                        <div class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-300">Operational Tier</div>
                                        <div class="text-xs font-bold text-indigo-600">{{ w.tier === 'starter' ? 'Freelancer' : w.tier === 'professional' ? 'Small Biz' : 'Enterprise' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-300">Personnel</div>
                                        <div class="text-xs font-bold text-slate-900">{{ w.users_count }} Paired</div>
                                    </div>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>

                <!-- NEW: Subscription Manager Tab -->
                <div v-if="activeTab === 'subscriptions'" class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 leading-none mb-1">Subscription Command Center</h3>
                            <p class="text-xs text-slate-500 font-medium">Monitor node evaluation cycles and pairing metrics</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Business Node</th>
                                    <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Service Tier</th>
                                    <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Users Paired</th>
                                    <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Lifecycle Status</th>
                                    <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">License Renewal/End</th>
                                    <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Last Payment</th>
                                    <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="w in workspaces" :key="w.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-8 py-5">
                                        <div class="font-bold text-slate-900 text-sm">{{ w.company_name }}</div>
                                        <div class="text-[10px] font-medium text-slate-400">{{ w.email || 'No primary node email' }}</div>
                                    </td>
                                    <td class="px-8 py-5 text-center">
                                        <span :class="[
                                            w.tier === 'enterprise' ? 'bg-amber-50 text-amber-700 border-amber-100' : 
                                            w.tier === 'professional' ? 'bg-purple-50 text-purple-700 border-purple-100' : 
                                            'bg-indigo-50 text-indigo-700 border-indigo-100'
                                        ]" class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border">
                                            {{ w.tier === 'starter' ? 'Freelancer' : w.tier === 'professional' ? 'Small Biz' : 'Enterprise' }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 text-center">
                                        <div class="text-xs font-black text-slate-700">{{ w.users_count }} Nodes Paired</div>
                                        <div class="text-[9px] font-bold text-slate-400 uppercase tracking-tight">Active Authorities</div>
                                    </td>
                                    <td class="px-8 py-5 text-center">
                                        <div v-if="!w.is_active" class="flex items-center justify-center gap-1.5 text-rose-600 py-2 border border-rose-100 bg-rose-50/50 rounded-xl">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                            <span class="text-[10px] font-black uppercase tracking-widest leading-none">Suspended</span>
                                        </div>
                                        <div v-else-if="w.tier === 'enterprise'" class="flex items-center justify-center gap-1.5 text-emerald-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span class="text-[10px] font-black uppercase tracking-widest">Lifetime Managed</span>
                                        </div>
                                        <div v-else-if="w.trial_expired" class="flex items-center justify-center gap-1.5 text-rose-600 animate-pulse">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span class="text-[10px] font-black uppercase tracking-widest">License Expired</span>
                                        </div>
                                        <div v-else-if="w.trial_ends_at" class="flex items-center justify-center gap-1.5 text-indigo-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-ping"></span>
                                            <span class="text-[10px] font-black uppercase tracking-widest">Active License</span>
                                        </div>
                                        <div v-else class="flex items-center justify-center gap-1.5 text-slate-400">
                                            <span class="text-[10px] font-black uppercase tracking-widest">Manual License</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-right font-mono text-[11px] font-bold text-slate-600 tabular-nums">
                                        {{ w.trial_ends_at ? formatDate(w.trial_ends_at) : 'PERMANENT' }}
                                    </td>
                                    <td class="px-8 py-5 text-right font-mono text-[11px] font-bold text-slate-400 tabular-nums">
                                        {{ w.last_billed_at ? formatDate(w.last_billed_at) : '---' }}
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <button 
                                                @click="toggleWorkspaceStatus(w.id)"
                                                :class="w.is_active ? 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'"
                                                class="px-3 py-1.5 border rounded-lg text-[9px] font-black uppercase tracking-widest transition-all"
                                            >
                                                {{ w.is_active ? 'Suspend Node' : 'Activate Node' }}
                                            </button>
                                            <button 
                                                v-if="w.is_active"
                                                @click="confirmRenewal(w)"
                                                class="px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all"
                                            >
                                                Mark Paid & Renew (+30d)
                                            </button>
                                            <button @click="viewHistory(w)" class="text-slate-500 hover:text-slate-700 text-[10px] font-black uppercase tracking-widest">View History</button>
                                            <button @click="openEditModal(w)" class="text-indigo-600 hover:text-indigo-800 text-[10px] font-black uppercase tracking-widest">Manage Node</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modals -->
            <div v-if="showCreateModal || showEditModal || showUserModal || showHistoryModal" class="fixed inset-0 z-[100] bg-slate-950/20 backdrop-blur-sm transition-opacity" @click="showCreateModal = false; showEditModal = false; showUserModal = false; showHistoryModal = false"></div>

            <!-- Workspace Modals -->
            <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 pointer-events-none">
                <form @submit.prevent="showEditModal ? updateWorkspace() : submit()" class="relative w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl animate-in zoom-in-95 duration-200 border border-slate-200 pointer-events-auto">
                    <div class="mb-8 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 leading-none mb-1">
                                {{ showEditModal ? 'Update Business' : 'Provision New Business' }}
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">Configure workspace parameters and ownership</p>
                        </div>
                        <button type="button" @click="showCreateModal = false; showEditModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"></path></svg>
                        </button>
                    </div>
                    <div class="space-y-4 mb-8">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Business Name</label>
                            <input v-model="(showEditModal ? editForm : form).company_name" type="text" class="w-full rounded-xl border-slate-200 bg-slate-50 py-3 px-4 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium text-sm" placeholder="e.g. Acme Corp" required />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Primary Contact Email</label>
                            <input v-model="(showEditModal ? editForm : form).email" type="email" class="w-full rounded-xl border-slate-200 bg-slate-50 py-3 px-4 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium text-sm" placeholder="admin@acme.com" />
                        </div>
                        <div class="grid grid-cols-1 gap-4">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Service Tier</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button v-for="t in ['starter', 'professional', 'enterprise']" :key="t" type="button" 
                                    @click="(showEditModal ? editForm : form).tier = t"
                                    :class="[(showEditModal ? editForm : form).tier === t ? 'border-indigo-600 bg-indigo-50/50 ring-1 ring-indigo-600' : 'border-slate-200 bg-white hover:border-indigo-300']"
                                    class="flex flex-col items-center justify-center py-3 px-2 rounded-xl border-2 transition-all leading-tight"
                                >
                                    <span class="text-[10px] font-extrabold uppercase tracking-tight" :class="(showEditModal ? editForm : form).tier === t ? 'text-indigo-700' : 'text-slate-600'">
                                        {{ t === 'starter' ? 'Freelancer' : t === 'professional' ? 'Small Biz' : 'Enterprise' }}
                                    </span>
                                    <div class="mt-1 flex gap-0.5">
                                        <div v-for="i in (t === 'starter' ? 1 : t === 'professional' ? 2 : 3)" :key="i" class="w-1 h-1 rounded-full" :class="(showEditModal ? editForm : form).tier === t ? 'bg-indigo-500' : 'bg-slate-300'"></div>
                                    </div>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Node Controller (Owner)</label>
                            <select v-model="(showEditModal ? editForm : form).owner_id" class="w-full rounded-xl border-slate-200 bg-slate-50 py-3 px-4 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium text-sm" required>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                        </div>
                        <div v-if="showEditModal">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Node Expiration (Trial End)</label>
                            <input v-model="editForm.trial_ends_at" type="date" class="w-full rounded-xl border-slate-200 bg-slate-50 py-3 px-4 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium text-sm" />
                            <p class="mt-1.5 text-[9px] text-slate-400 font-medium">Leave empty for a permanent (non-trial) node.</p>
                        </div>
                    </div>
                    <button type="submit" :disabled="(showEditModal ? editForm : form).processing" class="w-full bg-indigo-600 text-white py-3.5 rounded-xl text-sm font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 disabled:opacity-50">
                        {{ showEditModal ? 'Update Workspace' : 'Initialize Workspace' }}
                    </button>
                </form>
            </div>

            <!-- Personnel Modal -->
            <div v-if="showUserModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 py-36">
                <form @submit.prevent="submitUser" class="relative w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl animate-in zoom-in-95 duration-200 border border-slate-200 h-fit">
                    <div class="mb-8 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 leading-none mb-1">
                                {{ editingUser ? 'Update Authority' : 'Create New Authority' }}
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">Manage global access credentials</p>
                        </div>
                        <button type="button" @click="showUserModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"></path></svg>
                        </button>
                    </div>
                    <div class="space-y-4 mb-8">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Full Name</label>
                            <input v-model="userForm.name" type="text" class="w-full rounded-xl border-slate-200 bg-slate-50 py-3 px-4 focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all font-medium text-sm" placeholder="e.g. John Doe" required />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Email Address</label>
                            <input v-model="userForm.email" type="email" class="w-full rounded-xl border-slate-200 bg-slate-50 py-3 px-4 focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all font-medium text-sm" placeholder="user@domain.com" required />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">{{ editingUser ? 'Reset Password (optional)' : 'Initial Password' }}</label>
                            <input v-model="userForm.password" type="password" class="w-full rounded-xl border-slate-200 bg-slate-50 py-3 px-4 focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all font-medium text-sm" :placeholder="editingUser ? 'Leave blank to keep current' : 'Min 8 characters'" :required="!editingUser" />
                        </div>
                    </div>
                    <button type="submit" :disabled="userForm.processing" class="w-full bg-slate-900 text-white py-3.5 rounded-xl text-sm font-bold hover:bg-slate-800 transition-all shadow-lg shadow-slate-100 disabled:opacity-50">
                        {{ editingUser ? 'Save Credentials' : 'Authorize Platform Access' }}
                    </button>
                </form>
            </div>

            <!-- Billing History Modal -->
            <div v-if="showHistoryModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-3xl bg-white p-0 shadow-2xl animate-in zoom-in-95 duration-200 border border-slate-200 overflow-hidden h-fit max-h-[80vh] flex flex-col">
                    <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 leading-none mb-1">Billing Audit History</h3>
                            <p class="text-xs text-slate-500 font-medium italic">Ledger for {{ activeHistoryWorkspace?.company_name }}</p>
                        </div>
                        <button type="button" @click="showHistoryModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"></path></svg>
                        </button>
                    </div>
                    <div class="flex-grow overflow-y-auto p-8 bg-white">
                        <div v-if="!activeHistoryWorkspace?.payments?.length" class="text-center py-12 text-slate-400 italic text-sm">No payment history found for this node.</div>
                        <div v-else class="space-y-4">
                            <div v-for="p in activeHistoryWorkspace.payments" :key="p.id" class="flex items-center justify-between p-5 bg-slate-50 border border-slate-200 rounded-2xl group hover:border-indigo-200 transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-900 tracking-tight">Monthly Renewal Processed</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Method: Manual (Platform Master)</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-[11px] font-black text-slate-900 tabular-nums">{{ formatDate(p.billed_at) }}</p>
                                    <p class="text-[10px] text-emerald-600 font-bold tracking-tight mt-0.5 uppercase">License: +30 Days</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-8 py-6 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                        <div class="text-[10px] text-slate-400 font-black uppercase tracking-widest leading-none">Global Status: Audited</div>
                        <button @click="showHistoryModal = false" class="px-6 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all">Close Ledger</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-in { animation-duration: 0.3s; animation-timing-function: cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes zoom-in-95 { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
