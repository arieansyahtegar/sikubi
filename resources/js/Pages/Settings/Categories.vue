<script setup>
import { ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

defineProps({ categories: Array });
const page = usePage();
const canManage = page.props.permissions?.canManageSettings;
const showForm = ref(false);
const showHelp = ref(false);
const form = useForm({ name: '', type: 'CREDIT', color: '#B76E79', icon: 'folder' });

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

function submit() {
    form.post('/settings/categories', { preserveScroll: true, onSuccess: () => { form.reset(); showForm.value = false; } });
}
function confirmDelete(cat) {
    deleteTarget.value = cat;
    showDeleteModal.value = true;
}
function executeDelete() {
    if (!deleteTarget.value) return;
    router.delete(`/settings/categories/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => { showDeleteModal.value = false; deleteTarget.value = null; },
    });
}
function approveCategory(cat) {
    router.patch(`/settings/categories/${cat.id}/approve`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Kategori — SIKUBI" />
    <AppLayout>
        <div class="space-y-6 animate-fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="page-title">Kategori</h1>
                    <p class="text-sm text-surface-600 mt-1">Kelola kategori transaksi keuangan</p>
                </div>
                <div class="flex gap-2">
                    <button @click="showHelp = !showHelp" class="btn-secondary" title="Bantuan">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>
                        Bantuan
                    </button>
                    <button v-if="canManage" @click="showForm = !showForm" class="btn-primary">+ Tambah</button>
                </div>
            </div>


            <!-- Help Panel -->
            <Transition name="slide-up">
                <div v-if="showHelp" class="glass-card p-5 border-l-4 border-blue-400">
                    <h3 class="font-semibold text-plum mb-3">📖 Panduan Kategori</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-surface-700">
                        <div>
                            <h4 class="font-semibold text-plum mb-1.5">💰 Pemasukan (Uang Masuk)</h4>
                            <p class="text-surface-600">Kategori untuk uang yang <strong>masuk</strong> ke rekening. Di mutasi BCA ditandai dengan <code class="bg-cream-200 px-1 rounded">CR</code>.</p>
                            <p class="text-surface-500 text-xs mt-1">Contoh: Transfer Masuk, Penjualan Langsung, Online Shop</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-plum mb-1.5">💸 Pengeluaran (Uang Keluar)</h4>
                            <p class="text-surface-600">Kategori untuk uang yang <strong>keluar</strong> dari rekening. Di mutasi BCA ditandai dengan <code class="bg-cream-200 px-1 rounded">DB</code>.</p>
                            <p class="text-surface-500 text-xs mt-1">Contoh: Transfer Keluar, Biaya Operasional, Gaji</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-plum mb-1.5">🎨 Warna</h4>
                            <p class="text-surface-600">Warna untuk membedakan kategori di grafik dan tabel.</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-plum mb-1.5">🔗 Aturan & Transaksi</h4>
                            <p class="text-surface-600">Jumlah aturan klasifikasi dan transaksi yang termasuk kategori ini.</p>
                        </div>
                    </div>
                    <button @click="showHelp = false" class="mt-3 text-xs text-surface-500 hover:text-plum">Tutup bantuan ×</button>
                </div>
            </Transition>

            <!-- Add Form (Admin only) -->
            <div v-if="showForm && canManage" class="glass-card p-6">
                <h3 class="text-sm font-semibold text-plum mb-4">Tambah Kategori Baru</h3>
                <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div><label class="label-text">Nama Kategori</label><input v-model="form.name" class="input-field" placeholder="cth: Pembelian Produk" required /></div>
                    <div><label class="label-text">Tipe</label>
                        <select v-model="form.type" class="input-field"><option value="DEBIT">Pemasukan (Uang Masuk)</option><option value="CREDIT">Pengeluaran (Uang Keluar)</option></select>
                    </div>
                    <div><label class="label-text">Warna</label><input v-model="form.color" type="color" class="input-field h-[42px]" /></div>
                    <div class="sm:col-span-3"><button type="submit" :disabled="form.processing" class="btn-primary">Simpan</button></div>
                </form>
            </div>

            <!-- Table -->
            <div class="glass-card overflow-hidden">
                <div class="hidden sm:block table-container">
                    <table class="data-table">
                        <thead><tr><th>Nama</th><th>Tipe</th><th>Transaksi</th><th>Aturan</th><th v-if="canManage"></th></tr></thead>
                        <tbody>
                            <tr v-for="cat in categories" :key="cat.id" :class="cat.is_suggested ? 'bg-amber-50/30' : ''">
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full" :style="{ background: cat.color }" />
                                        {{ cat.name }}
                                        <span v-if="cat.is_suggested" class="badge-yellow text-[10px]">Disarankan Sistem</span>
                                    </div>
                                </td>
                                <td><span :class="cat.type === 'DEBIT' ? 'badge-green' : 'badge-red'">{{ cat.type === 'DEBIT' ? 'Pemasukan' : 'Pengeluaran' }}</span></td>
                                <td>{{ cat.transactions_count }}</td>
                                <td>{{ cat.classification_rules_count }}</td>
                                <td v-if="canManage">
                                    <div class="flex items-center gap-1">
                                        <button v-if="cat.is_suggested" @click="approveCategory(cat)" class="text-emerald-500 hover:text-emerald-700 p-1 rounded-lg hover:bg-emerald-50 transition-colors" title="Setujui kategori">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        </button>
                                        <button @click="confirmDelete(cat)" class="text-surface-500 hover:text-red-500 p-1 rounded-lg hover:bg-red-50 transition-colors" title="Hapus kategori">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="sm:hidden p-4 space-y-3">
                    <div v-for="cat in categories" :key="cat.id" class="mobile-card flex items-center justify-between">
                        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full" :style="{ background: cat.color }" /><span class="font-medium">{{ cat.name }}</span></div>
                        <span :class="cat.type === 'DEBIT' ? 'badge-green' : 'badge-red'">{{ cat.type === 'DEBIT' ? 'Pemasukan' : 'Pengeluaran' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Hapus Kategori?"
            :message="`Kategori '${deleteTarget?.name}' akan dihapus beserta semua aturan klasifikasi terkait.`"
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
