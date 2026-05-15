<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
    status: String,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedUser = ref(null);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    is_admin: false,
});

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    is_admin: false,
});

const openCreateModal = () => {
    createForm.reset();
    showCreateModal.value = true;
};

const openEditModal = (user) => {
    selectedUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.is_admin = user.is_admin;
    showEditModal.value = true;
};

const submitCreate = () => {
    createForm.post(route('admin.users.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

const submitEdit = () => {
    editForm.patch(route('admin.users.update', selectedUser.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
        },
    });
};

const deleteUser = (user) => {
    if (confirm(`Are you sure you want to delete ${user.name}?`)) {
        useForm({}).delete(route('admin.users.destroy', user.id));
    }
};
</script>

<template>
    <AdminLayout title="Users Management">
        <div class="max-w-7xl animate-fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div>
                    <h3 class="text-2xl font-bold text-white mb-2">User Directory</h3>
                    <p class="text-gray-400 text-sm font-medium">Manage administrative and member access across the platform.</p>
                </div>
                <button 
                    @click="openCreateModal"
                    class="inline-flex items-center gap-x-2 rounded-2xl bg-purple-600 px-6 py-3 text-sm font-bold text-white shadow-xl shadow-purple-600/20 hover:bg-purple-500 transition-all duration-200"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New User
                </button>
            </div>

            <div v-if="status" class="mb-8 rounded-2xl bg-emerald-500/10 p-4 text-sm text-emerald-400 border border-emerald-500/20 flex items-center gap-x-3">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-bold">{{ status }}</span>
            </div>

            <!-- Users Table -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 shadow-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-800/80">
                                <th class="px-6 py-5 text-[11px] font-bold text-gray-500 uppercase tracking-widest">User Profile</th>
                                <th class="px-6 py-5 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Status/Role</th>
                                <th class="px-6 py-5 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Joined Date</th>
                                <th class="px-6 py-5 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/40">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-800/20 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-x-4">
                                        <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-purple-600/20 to-indigo-600/20 border border-purple-500/20 flex items-center justify-center font-bold text-purple-400">
                                            {{ user.name[0] }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-white">{{ user.name }}</div>
                                            <div class="text-xs text-gray-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-y-1.5">
                                        <span v-if="user.is_admin" class="inline-flex w-fit px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20 uppercase tracking-wider">
                                            Administrator
                                        </span>
                                        <span v-else class="inline-flex w-fit px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-500/10 text-gray-400 border border-gray-800 uppercase tracking-wider">
                                            Member
                                        </span>
                                        <span v-if="user.email_verified_at" class="text-[10px] text-emerald-400 font-medium">Verified Account</span>
                                        <span v-else class="text-[10px] text-amber-400 font-medium">Pending Verification</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400 font-medium">
                                    {{ user.created_at }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-x-2">
                                        <button 
                                            @click="openEditModal(user)"
                                            class="p-2 rounded-lg bg-gray-800/50 text-gray-400 hover:text-white hover:bg-gray-700 transition-all"
                                            title="Edit User"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            v-if="user.id !== $page.props.auth.user.id"
                                            @click="deleteUser(user)"
                                            class="p-2 rounded-lg bg-red-500/10 text-red-400 hover:text-white hover:bg-red-500 transition-all"
                                            title="Delete User"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Placeholder (Simplistic for now) -->
                <div class="px-6 py-5 border-t border-gray-800/80 flex items-center justify-between">
                    <p class="text-xs text-gray-500 font-medium">Showing {{ users.from }} to {{ users.to }} of {{ users.total }} entries</p>
                    <div class="flex gap-x-2">
                        <button 
                            v-for="link in users.links" 
                            :key="link.label"
                            :disabled="!link.url || link.active"
                            @click="$inertia.visit(link.url)"
                            class="px-3 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-widest transition-all"
                            :class="[
                                link.active ? 'bg-purple-600 text-white' : 'text-gray-400 hover:bg-gray-800',
                                !link.url ? 'opacity-30 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" maxWidth="lg">
            <div class="p-8 bg-[#121826] border border-gray-700/50 rounded-2xl overflow-hidden relative">
                <div class="absolute top-0 right-0 p-4">
                    <button @click="showCreateModal = false" class="text-gray-500 hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-8">
                    <h2 class="text-xl font-bold text-white mb-1">Onboard User</h2>
                    <p class="text-xs text-gray-400 font-medium">Create a new access profile for the platform ecosystem.</p>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-5">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Full Identity Name</label>
                        <input 
                            v-model="createForm.name" 
                            type="text" 
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-3 px-4 text-sm text-white focus:border-purple-500 transition-all outline-none"
                            placeholder="e.g. Sarah Jenkins"
                        />
                        <div v-if="createForm.errors.name" class="text-[10px] text-red-400 font-bold mt-1">{{ createForm.errors.name }}</div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email Endpoint</label>
                        <input 
                            v-model="createForm.email" 
                            type="email" 
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-3 px-4 text-sm text-white focus:border-purple-500 transition-all outline-none"
                            placeholder="sarah@fluxmedia.space"
                        />
                        <div v-if="createForm.errors.email" class="text-[10px] text-red-400 font-bold mt-1">{{ createForm.errors.email }}</div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Access Credentials</label>
                        <input 
                            v-model="createForm.password" 
                            type="password" 
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-3 px-4 text-sm text-white focus:border-purple-500 transition-all outline-none"
                            placeholder="Secure Passphrase"
                        />
                        <div v-if="createForm.errors.password" class="text-[10px] text-red-400 font-bold mt-1">{{ createForm.errors.password }}</div>
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center gap-x-3 cursor-pointer group">
                            <input type="checkbox" v-model="createForm.is_admin" class="rounded border-gray-700 bg-gray-900 accent-purple-500 text-purple-600 h-5 w-5" />
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-gray-200 group-hover:text-white transition-colors">Grant Administrative Privileges</span>
                                <span class="text-[10px] text-gray-500 font-medium">Allows full access to cluster configuration and user management.</span>
                            </div>
                        </label>
                    </div>

                    <div class="pt-6">
                        <button 
                            type="submit" 
                            :disabled="createForm.processing"
                            class="w-full bg-purple-600 hover:bg-purple-500 text-white font-bold py-3.5 rounded-xl text-sm transition-all shadow-lg shadow-purple-600/20 disabled:opacity-50"
                        >
                            Provision Account
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit User Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false" maxWidth="lg">
            <div class="p-8 bg-[#121826] border border-gray-700/50 rounded-2xl overflow-hidden relative">
                <div class="absolute top-0 right-0 p-4">
                    <button @click="showEditModal = false" class="text-gray-500 hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-8">
                    <h2 class="text-xl font-bold text-white mb-1">Modify Access Profile</h2>
                    <p class="text-xs text-gray-400 font-medium">Adjusting credentials and roles for {{ selectedUser?.name }}.</p>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-5">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Full Identity Name</label>
                        <input 
                            v-model="editForm.name" 
                            type="text" 
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-3 px-4 text-sm text-white focus:border-purple-500 transition-all outline-none"
                        />
                        <div v-if="editForm.errors.name" class="text-[10px] text-red-400 font-bold mt-1">{{ editForm.errors.name }}</div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email Endpoint</label>
                        <input 
                            v-model="editForm.email" 
                            type="email" 
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-3 px-4 text-sm text-white focus:border-purple-500 transition-all outline-none"
                        />
                        <div v-if="editForm.errors.email" class="text-[10px] text-red-400 font-bold mt-1">{{ editForm.errors.email }}</div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Update Password (Leave blank to keep current)</label>
                        <input 
                            v-model="editForm.password" 
                            type="password" 
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-3 px-4 text-sm text-white focus:border-purple-500 transition-all outline-none"
                        />
                        <div v-if="editForm.errors.password" class="text-[10px] text-red-400 font-bold mt-1">{{ editForm.errors.password }}</div>
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center gap-x-3 cursor-pointer group">
                            <input type="checkbox" v-model="editForm.is_admin" class="rounded border-gray-700 bg-gray-900 accent-purple-500 text-purple-600 h-5 w-5" />
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-gray-200 group-hover:text-white transition-colors">Grant Administrative Privileges</span>
                                <span class="text-[10px] text-gray-500 font-medium">Allows full access to cluster configuration and user management.</span>
                            </div>
                        </label>
                    </div>

                    <div class="pt-6">
                        <button 
                            type="submit" 
                            :disabled="editForm.processing"
                            class="w-full bg-purple-600 hover:bg-purple-500 text-white font-bold py-3.5 rounded-xl text-sm transition-all shadow-lg shadow-purple-600/20 disabled:opacity-50"
                        >
                            Sync Profile Updates
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>
