<script setup>
import { ref, inject } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

defineProps({ accounts: Array });
const addToast = inject('addToast');
const showForm = ref(false);
const form = useForm({ bank_name: '', account_number: '', account_alias: '', currency: 'IDR' });

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

function submit() {
    form.post('/accounts', {
        preserveScroll: true,
        onSuccess: () => { form.reset(); showForm.value = false; addToast?.('Rekening berhasil ditambahkan', 'success'); },
    });
}

function confirmDelete(acc) {
    deleteTarget.value = acc;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!deleteTarget.value) return;
    router.delete(`/accounts/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onSuccess: () => addToast?.('Rekening berhasil dihapus', 'success'),
        onFinish: () => { showDeleteModal.value = false; deleteTarget.value = null; },
    });
}
</script>

<template>
    <Head title="Rekening Bank — SIKUBI" />
    <AppLayout>
        <div class="space-y-6 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="page-title">Rekening Bank</h1>
                    <p class="text-sm text-surface-600 mt-1">Kelola rekening bank yang terhubung</p>
                </div>
                <button @click="showForm = !showForm" class="btn-primary">+ Tambah</button>
            </div>
            <Transition name="slide-up">
                <div v-if="showForm" class="glass-card p-6">
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label class="label-text">Nama Bank</label><input v-model="form.bank_name" class="input-field" placeholder="BCA" required /></div>
                        <div><label class="label-text">No. Rekening</label><input v-model="form.account_number" class="input-field" placeholder="1234567890" required /></div>
                        <div><label class="label-text">Alias</label><input v-model="form.account_alias" class="input-field" placeholder="BCA Utama" /></div>
                        <div class="flex items-end"><button type="submit" :disabled="form.processing" class="btn-primary w-full">Simpan</button></div>
                    </form>
                </div>
            </Transition>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="acc in accounts" :key="acc.id" class="glass-card-hover p-5">
                    <div class="flex items-start justify-between">
                        <div class="w-10 h-10 rounded-xl bg-gradient-rose flex items-center justify-center text-white font-bold text-sm">
                            {{ acc.bank_name.substring(0, 2) }}
                        </div>
                        <button @click="confirmDelete(acc)" class="text-surface-500 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50" title="Hapus rekening">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                        </button>
                    </div>
                    <h3 class="text-lg font-semibold text-plum mt-3">{{ acc.account_alias || acc.bank_name }}</h3>
                    <p class="text-sm text-surface-500">{{ acc.bank_name }} · {{ acc.account_number }}</p>
                    <p class="text-xs text-surface-500 mt-2">{{ acc.transactions_count || 0 }} transaksi</p>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Hapus Rekening Bank?"
            :message="`Rekening '${deleteTarget?.account_alias || deleteTarget?.bank_name}' (${deleteTarget?.account_number}) akan dihapus beserta semua transaksi terkait.`"
            confirmText="Ya, Hapus"
            variant="danger"
            @confirm="executeDelete"
            @cancel="showDeleteModal = false"
        />
    </AppLayout>
</template>

<style scoped>
.slide-up-enter-active { transition: all 0.3s ease-out; }
.slide-up-leave-active { transition: all 0.2s ease-in; }
.slide-up-enter-from { opacity: 0; transform: translateY(16px); }
.slide-up-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
