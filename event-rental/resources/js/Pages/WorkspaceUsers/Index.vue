<script setup lang="ts">
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
const workspace = (page.props as any).workspace;

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
    </AuthenticatedLayout>
</template>
