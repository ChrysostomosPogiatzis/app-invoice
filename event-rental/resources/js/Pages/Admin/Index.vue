<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    stats: {
        users: number;
        super_admins: number;
        workspaces: number;
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
        users_count: number;
        products_count: number;
        created_at: string | null;
    }>;
}>();
</script>

<template>
    <Head title="Admin Console" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Admin Console</h2>
                    <p class="text-sm text-slate-500">Platform-wide visibility for super admins.</p>
                </div>
                <div class="rounded-full bg-rose-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-rose-600">
                    Super Admin
                </div>
            </div>
        </template>

        <div class="space-y-8">
            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Users</div>
                    <div class="mt-3 text-4xl font-black text-slate-900">{{ stats.users }}</div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Super Admins</div>
                    <div class="mt-3 text-4xl font-black text-slate-900">{{ stats.super_admins }}</div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Workspaces</div>
                    <div class="mt-3 text-4xl font-black text-slate-900">{{ stats.workspaces }}</div>
                </div>
            </section>

            <section class="grid gap-8 xl:grid-cols-[1.2fr,0.8fr]">
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="text-lg font-bold text-slate-900">Users</h3>
                        <p class="text-sm text-slate-500">See who has platform access and which workspaces they belong to.</p>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="user in users" :key="user.id" class="px-6 py-5">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <h4 class="text-base font-bold text-slate-900">{{ user.name }}</h4>
                                        <span
                                            v-if="user.is_super_admin"
                                            class="rounded-full bg-rose-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.15em] text-rose-700"
                                        >
                                            Super Admin
                                        </span>
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">{{ user.email }}</div>
                                </div>
                                <div class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-400">
                                    Current Workspace ID: {{ user.current_workspace_id ?? 'None' }}
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span
                                    v-for="workspace in user.workspaces"
                                    :key="workspace.id"
                                    class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700"
                                >
                                    {{ workspace.company_name }} · {{ workspace.role }}
                                </span>
                                <span
                                    v-if="user.workspaces.length === 0"
                                    class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700"
                                >
                                    No workspace memberships
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="text-lg font-bold text-slate-900">Workspaces</h3>
                        <p class="text-sm text-slate-500">A high-level overview of every workspace in the system.</p>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="workspace in workspaces" :key="workspace.id" class="px-6 py-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-base font-bold text-slate-900">{{ workspace.company_name }}</h4>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ workspace.email || 'No workspace email set' }}
                                    </div>
                                </div>
                                <div class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.15em] text-emerald-700">
                                    {{ workspace.currency }}
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3 text-sm text-slate-600">
                                <div>
                                    <span class="font-semibold text-slate-900">{{ workspace.users_count }}</span>
                                    users
                                </div>
                                <div>
                                    <span class="font-semibold text-slate-900">{{ workspace.products_count }}</span>
                                    products
                                </div>
                                <div class="col-span-2 text-xs uppercase tracking-[0.15em] text-slate-400">
                                    Created {{ workspace.created_at || 'Unknown' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
