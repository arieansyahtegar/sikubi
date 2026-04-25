<script setup>
import { ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

defineProps({ rules: Array, categories: Array });
const page = usePage();
const canManage = page.props.permissions?.canManageSettings;
const showForm = ref(false);
const showHelp = ref(false);
const form = useForm({ category_id: '', pattern: '', match_type: 'CONTAINS', priority: 10 });

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

function submit() {
    form.post('/settings/rules', { preserveScroll: true, onSuccess: () => { form.reset(); showForm.value = false; } });
}
function confirmDelete(rule) {
    deleteTarget.value = rule;
    showDeleteModal.value = true;
}
function executeDelete() {
    if (!deleteTarget.value) return;
    router.delete(`/settings/rules/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => { showDeleteModal.value = false; deleteTarget.value = null; },
    });
}

const matchTypeLabels = {
    CONTAINS: 'Contains',
    EXACT: 'Exact',
    REGEX: 'Regex',
};
</script>

<template>
    <Head title="Aturan Klasifikasi — SIKUBI" />
    <AppLayout>
        <div class="space-y-6 animate-fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="page-title">Aturan Klasifikasi</h1>
                    <p class="text-sm text-surface-600 mt-1">Basis pengetahuan untuk klasifikasi otomatis transaksi</p>
                </div>
                <div class="flex gap-2">
                    <button @click="showHelp = !showHelp" class="btn-secondary" title="Bantuan">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>
                        Bantuan
                    </button>
                    <button v-if="canManage" @click="showForm = !showForm" class="btn-primary">+ Tambah Aturan</button>
                </div>
            </div>


            <!-- Help Panel -->
            <Transition name="slide-up">
                <div v-if="showHelp" class="glass-card p-5 border-l-4 border-blue-400">
                    <h3 class="font-semibold text-plum mb-3">📖 Panduan Aturan Klasifikasi</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-surface-700">
                        <div>
                            <h4 class="font-semibold text-plum mb-1.5">🔤 Kata Kunci (Pattern)</h4>
                            <p class="text-surface-600">Kata atau frasa yang dicari di dalam deskripsi transaksi bank. Contoh: <code class="bg-cream-200 px-1 rounded">SHOPEE</code>, <code class="bg-cream-200 px-1 rounded">BIAYA ADM</code></p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-plum mb-1.5">🎯 Tipe Pencocokan</h4>
                            <ul class="space-y-1.5 text-surface-600">
                                <li><span class="font-medium text-plum">Contains</span> — Deskripsi mengandung kata kunci. <em class="text-surface-500">Paling sering dipakai.</em></li>
                                <li><span class="font-medium text-plum">Exact</span> — Deskripsi harus sama persis 100% dengan kata kunci.</li>
                                <li><span class="font-medium text-plum">Regex</span> — Pencocokan pola fleksibel, bisa mencocokkan beberapa kata sekaligus. <em class="text-surface-500">Contoh: <code class="bg-cream-200 px-1 rounded">(GAJI|THR|PAYROLL)</code> cocok dengan salah satu kata tersebut.</em></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold text-plum mb-1.5">⚡ Prioritas</h4>
                            <p class="text-surface-600">Angka lebih kecil = diproses lebih dulu. Jika ada dua aturan cocok, yang prioritas lebih kecil yang menang.</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-plum mb-1.5">📊 Jumlah Cocok</h4>
                            <p class="text-surface-600">Berapa kali aturan ini berhasil mencocokkan transaksi.</p>
                        </div>
                    </div>
                    <button @click="showHelp = false" class="mt-3 text-xs text-surface-500 hover:text-plum">Tutup bantuan ×</button>
                </div>
            </Transition>

            <!-- Add Form (Admin only) -->
            <div v-if="showForm && canManage" class="glass-card p-6">
                <h3 class="text-sm font-semibold text-plum mb-4">Tambah Aturan Baru</h3>
                <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="label-text">Kategori</label>
                        <select v-model="form.category_id" class="input-field" required>
                            <option value="" disabled>Pilih kategori...</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }} ({{ c.type === 'DEBIT' ? 'Pemasukan' : 'Pengeluaran' }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="label-text">Kata Kunci</label>
                        <input v-model="form.pattern" class="input-field" placeholder="cth: SHOPEE" required />
                        <p class="text-[10px] text-surface-500 mt-1">Kata yang dicari di deskripsi transaksi</p>
                    </div>
                    <div>
                        <label class="label-text">Tipe Pencocokan</label>
                        <select v-model="form.match_type" class="input-field">
                            <option value="CONTAINS">Contains</option>
                            <option value="EXACT">Exact</option>
                            <option value="REGEX">Regex</option>
                        </select>
                        <p class="text-[10px] text-surface-500 mt-1">Cara mencocokkan kata kunci</p>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" :disabled="form.processing" class="btn-primary w-full">Simpan</button>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="glass-card overflow-hidden">
                <div class="hidden sm:block table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kata Kunci</th>
                                <th>Tipe Pencocokan</th>
                                <th>Kategori</th>
                                <th>Jumlah Cocok</th>
                                <th>Prioritas</th>
                                <th v-if="canManage"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in rules" :key="r.id">
                                <td class="font-mono text-sm">{{ r.pattern }}</td>
                                <td><span class="badge-blue">{{ matchTypeLabels[r.match_type] || r.match_type }}</span></td>
                                <td>
                                    <span v-if="r.category" class="badge" :style="{ background: r.category.color + '15', color: r.category.color, border: '1px solid ' + r.category.color + '40' }">{{ r.category.name }}</span>
                                </td>
                                <td>{{ r.hit_count }}×</td>
                                <td>{{ r.priority }}</td>
                                <td v-if="canManage">
                                    <button @click="confirmDelete(r)" class="text-surface-500 hover:text-red-500 p-1 rounded-lg hover:bg-red-50 transition-colors" title="Hapus aturan">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="sm:hidden p-4 space-y-3">
                    <div v-for="r in rules" :key="r.id" class="mobile-card">
                        <div class="flex items-center justify-between mb-1">
                            <p class="font-mono text-sm font-semibold">{{ r.pattern }}</p>
                            <button v-if="canManage" @click="confirmDelete(r)" class="text-surface-500 hover:text-red-500 p-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                        </div>
                        <div class="flex gap-2">
                            <span class="badge-blue text-[10px]">{{ matchTypeLabels[r.match_type] || r.match_type }}</span>
                            <span v-if="r.category" class="badge text-[10px]" :style="{ background: r.category.color + '15', color: r.category.color }">{{ r.category.name }}</span>
                        </div>
                        <p class="text-[10px] text-surface-500 mt-1">Cocok: {{ r.hit_count }}× · Prioritas: {{ r.priority }}</p>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Hapus Aturan?"
            :message="`Aturan '${deleteTarget?.pattern}' akan dihapus dari sistem klasifikasi.`"
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
