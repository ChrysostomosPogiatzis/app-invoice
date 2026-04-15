<script setup lang="ts">
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { withValidation } from '@/utils/validation';

const props = defineProps<{
    roles: string[];
    workspaceUsers: Array<{
        id: number;
        name: string;
        email: string;
        is_super_admin: boolean;
        current_workspace_id: number | null;
        role: string;
    }>;
}>();

const page = usePage();
const workspace = computed(() => (page.props as any).workspace);

const tierLimits = {
    starter: 1,
    professional: 3,
    enterprise: 99
};

const maxUsers = computed(() => tierLimits[workspace.value?.tier as keyof typeof tierLimits] || 1);
const userCount = computed(() => props.workspaceUsers.length);
const remainingSlots = computed(() => typeof maxUsers.value === 'number' ? Math.max(0, maxUsers.value - userCount.value) : 'Unlimited');
const usagePercentage = computed(() => (userCount.value / (typeof maxUsers.value === 'number' ? maxUsers.value : 100)) * 100);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'staff',
});

const roleLabels: Record<string, string> = {
    owner: 'Owner',
    admin: 'Admin',
    staff: 'Staff',
    viewer: 'Viewer',
};

const submit = () => {
    form.post(route('workspace-users.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'email', 'password'),
    });
};

const updateRole = (userId: number, role: string) => {
    router.patch(route('workspace-users.update', userId), { role }, { preserveScroll: true });
};

const removeUser = (userId: number) => {
    router.delete(route('workspace-users.destroy', userId), { preserveScroll: true });
};
</script>

<template>
    <Head title="Team Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Team Management</h2>
                    <p class="text-sm text-slate-500">Add workspace users, assign roles, and control access.</p>
                </div>
                <div class="rounded-full bg-slate-100 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-700">
                    {{ workspace?.name }}
                </div>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Subscription Capacity Tracker -->
            <div class="bg-white rounded-3xl border border-slate-200 p-8 flex items-center justify-between shadow-sm overflow-hidden relative group transition-all hover:shadow-md">
                <div class="flex items-center gap-6">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center border-2" :class="[usagePercentage >= 100 ? 'border-rose-100 bg-rose-50 text-rose-600' : 'border-indigo-100 bg-indigo-50 text-indigo-600']">
                         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-base font-bold text-slate-900 tracking-tight">Authority Capacity</h3>
                            <span :class="[workspace?.tier === 'enterprise' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700']" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-current/10">
                                {{ workspace?.tier === 'starter' ? 'Freelancer' : workspace?.tier === 'professional' ? 'Small Biz' : 'Enterprise' }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-slate-500 mt-1">{{ userCount }} / {{ maxUsers >= 99 ? '∞' : maxUsers }} Licensed Seats</p>
                    </div>
                </div>
                
                <div class="flex flex-col items-end gap-2 text-right">
                    <div class="flex items-center gap-4">
                        <div class="space-y-1.5 font-bold uppercase tracking-widest">
                             <div class="flex justify-between text-[10px]" :class="[usagePercentage >= 100 ? 'text-rose-600' : 'text-slate-400']">
                                 <span>{{ Math.round(usagePercentage) }}% Cap</span>
                                 <span v-if="workspace?.tier !== 'enterprise'" class="ml-6">{{ remainingSlots }} available</span>
                             </div>
                             <div class="w-[240px] h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                 <div 
                                    class="h-full rounded-full transition-all duration-1000 shadow-sm shadow-black/5" 
                                    :class="[usagePercentage >= 90 ? 'bg-rose-500' : usagePercentage >= 70 ? 'bg-amber-500' : 'bg-indigo-600']"
                                    :style="`width: ${Math.min(100, usagePercentage)}%`"
                                 ></div>
                             </div>
                        </div>
                    </div>
                    <p v-if="usagePercentage >= 90 && workspace?.tier !== 'enterprise'" class="text-[10px] font-black text-rose-600 uppercase tracking-[0.1em] animate-pulse">Max Authorities reached: Node Locked</p>
                    <p v-else-if="workspace?.tier !== 'enterprise'" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest opacity-60 italic">Authority limits are strictly audited across the Gravity cluster</p>
                </div>
            </div>

            <div class="grid gap-8 xl:grid-cols-[0.95fr,1.05fr]">
            <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-slate-900">Add Workspace User</h3>
                    <p class="mt-1 text-sm text-slate-500">Create a new user or attach an existing account to this workspace.</p>
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <FormErrorSummary :errors="form.errors" />

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Full Name</label>
                        <input v-model="form.name" type="text" :class="withValidation('w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500', form.errors.name)" />
                        <div v-if="form.errors.name" class="mt-2 text-sm text-rose-600">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Email</label>
                        <input v-model="form.email" type="email" :class="withValidation('w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500', form.errors.email)" />
                        <div v-if="form.errors.email" class="mt-2 text-sm text-rose-600">{{ form.errors.email }}</div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Temporary Password</label>
                        <input v-model="form.password" type="password" :class="withValidation('w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500', form.errors.password)" />
                        <p class="mt-2 text-xs text-slate-500">Required only when the email does not already belong to an existing user.</p>
                        <div v-if="form.errors.password" class="mt-2 text-sm text-rose-600">{{ form.errors.password }}</div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Role</label>
                        <select v-model="form.role" :class="withValidation('w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500', form.errors.role)">
                            <option v-for="role in roles" :key="role" :value="role">{{ roleLabels[role] }}</option>
                        </select>
                        <div v-if="form.errors.role" class="mt-2 text-sm text-rose-600">{{ form.errors.role }}</div>
                    </div>

                    <button type="submit" :disabled="form.processing" class="inline-flex items-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                        {{ form.processing ? 'Adding User...' : 'Add User To Workspace' }}
                    </button>
                </form>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-8 py-6">
                    <h3 class="text-lg font-bold text-slate-900">Current Team</h3>
                    <p class="mt-1 text-sm text-slate-500">Owners and admins can adjust roles for the active workspace.</p>
                </div>

                <div class="divide-y divide-slate-100">
                    <div v-for="member in workspaceUsers" :key="member.id" class="px-8 py-6">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-base font-bold text-slate-900">{{ member.name }}</h4>
                                    <span v-if="member.is_super_admin" class="rounded-full bg-rose-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.15em] text-rose-700">
                                        Super Admin
                                    </span>
                                    <span v-if="member.current_workspace_id === workspace?.id" class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.15em] text-emerald-700">
                                        Active Here
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-slate-500">{{ member.email }}</div>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <select
                                    :value="member.role"
                                    class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-900 outline-none transition focus:border-indigo-500"
                                    @change="updateRole(member.id, ($event.target as HTMLSelectElement).value)"
                                >
                                    <option v-for="role in roles" :key="role" :value="role">{{ roleLabels[role] }}</option>
                                </select>

                                <button
                                    type="button"
                                    class="rounded-2xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50"
                                    @click="removeUser(member.id)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</AuthenticatedLayout>
</template>
