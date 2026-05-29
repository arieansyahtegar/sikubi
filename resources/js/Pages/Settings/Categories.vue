<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({ categories: Array, accounts: Array, filters: Object, hasData: { type: Boolean, default: true }, availableMonths: { type: Array, default: () => [] } });
const page = usePage();
const canManage = page.props.permissions?.canManageSettings;
const showForm = ref(false);
const showHelp = ref(false);
const selectedAccountId = ref(props.filters?.account_id || '');
const selectedMonth = ref(props.filters?.month || '');
const form = useForm({ name: '', type: 'CREDIT', color: '#E8637A', icon: 'folder', bank_account_id: '' });

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

const totalCategories = computed(() => props.categories?.length || 0);
const debitCategoriesCount = computed(() => props.categories?.filter(c => c.type === 'DEBIT').length || 0);
const creditCategoriesCount = computed(() => props.categories?.filter(c => c.type === 'CREDIT').length || 0);
const totalCategorizedTransactions = computed(() => props.categories?.reduce((sum, c) => sum + (c.transactions_count || 0), 0) || 0);

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

// Bank account filter
watch(selectedAccountId, (val) => {
    form.bank_account_id = val;
    router.get('/settings/categories', {
        account_id: selectedAccountId.value || undefined,
        month: selectedMonth.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}, { immediate: true });

// Month filter
function selectMonth(m) {
    selectedMonth.value = m;
    router.get('/settings/categories', {
        account_id: selectedAccountId.value || undefined,
        month: m || undefined,
    }, { preserveState: true, preserveScroll: true });
}

// Quick link to Transactions with automatic contextual filters
function goToTransactions(cat) {
    const params = {
        category_id: cat.id
    };
    
    if (cat.bank_account_id) {
        params.account_id = cat.bank_account_id;
    } else if (selectedAccountId.value) {
        params.account_id = selectedAccountId.value;
    }
    
    if (selectedMonth.value) {
        const [y, mo] = selectedMonth.value.split('-');
        params.date_from = `${y}-${mo}-01`;
        const lastDay = new Date(y, mo, 0).getDate();
        params.date_to = `${y}-${mo}-${lastDay}`;
    }
    
    router.visit('/transactions', {
        data: params,
        preserveState: false
    });
}

function formatMonth(m) {
    if (!m) return '';
    const [y, mo] = m.split('-');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${months[parseInt(mo) - 1]} ${y}`;
}

function bankLabel(cat) {
    if (!cat.bank_account) return 'Global';
    return cat.bank_account.account_alias || cat.bank_account.bank_name;
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
                    <select v-if="accounts?.length" v-model="selectedAccountId" class="filter-field !w-auto !pr-8">
                        <option value="">Semua Rekening</option>
                        <option value="cash">Transaksi Tunai</option>
                        <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.account_alias || acc.bank_name }}</option>
                    </select>
                    <button @click="showHelp = !showHelp" class="btn-secondary" title="Bantuan">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>
                        Bantuan
                    </button>
                    <button v-if="canManage" @click="showForm = !showForm" class="btn-primary">+ Tambah</button>
                </div>
            </div>

            <!-- Month Filter Panel -->
            <div v-if="availableMonths?.length > 0" class="glass-card p-3 sm:p-4">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-4 h-4 text-surface-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                    <span class="text-xs font-semibold text-surface-600 uppercase tracking-wider">Filter Bulan</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="m in availableMonths" :key="m"
                        @click="selectMonth(m)"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-medium transition-all duration-200',
                            selectedMonth === m
                                ? 'bg-gradient-rose text-white shadow-sm scale-[1.02]'
                                : 'bg-cream-200/50 text-surface-600 hover:bg-cream-300/60 hover:text-plum'
                        ]"
                    >
                        {{ formatMonth(m) }}
                    </button>
                </div>
            </div>

            <!-- Help Panel -->
            <Transition name="slide-up">
                <div v-if="showHelp" class="glass-card p-5 border-l-4 border-blue-400">
                    <h3 class="font-semibold text-plum mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>
                        Panduan Kategori
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-surface-700">
                        <div>
                            <h4 class="font-semibold text-plum mb-1.5 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                                Pemasukan (Uang Masuk)
                            </h4>
                            <p class="text-surface-600">Kategori untuk uang yang <strong>masuk</strong> ke rekening. Di mutasi BCA ditandai dengan <code class="bg-cream-200 px-1 rounded">CR</code>.</p>
                            <p class="text-surface-500 text-xs mt-1">Contoh: Transfer Masuk, Penjualan Langsung, Online Shop</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-plum mb-1.5 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 11.5l-3-3m0 0l-3 3m3-3v9M3 12a9 9 0 1118 0 9 9 0 01-18 0z" /></svg>
                                Pengeluaran (Uang Keluar)
                            </h4>
                            <p class="text-surface-600">Kategori untuk uang yang <strong>keluar</strong> dari rekening. Di mutasi BCA ditandai dengan <code class="bg-cream-200 px-1 rounded">DB</code>.</p>
                            <p class="text-surface-500 text-xs mt-1">Contoh: Transfer Keluar, Biaya Operasional, Gaji</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-plum mb-1.5 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-surface-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                                Rekening Bank
                            </h4>
                            <p class="text-surface-600">Pilih rekening bank untuk membuat kategori khusus per bank. Biarkan kosong untuk kategori global.</p>
                        </div>
                    </div>
                    <button @click="showHelp = false" class="mt-3 text-xs text-surface-500 hover:text-plum">Tutup bantuan ×</button>
                </div>
            </Transition>

            <!-- Add Form (Admin only) -->
            <Transition name="slide-up">
                <div v-if="showForm && canManage" class="glass-card p-6">
                    <h3 class="text-sm font-semibold text-plum mb-4">Tambah Kategori Baru</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div><label class="label-text">Nama Kategori</label><input v-model="form.name" class="input-field" placeholder="cth: Pembelian Produk" required /></div>
                        <div><label class="label-text">Tipe</label>
                            <select v-model="form.type" class="input-field"><option value="DEBIT">Pemasukan (Uang Masuk)</option><option value="CREDIT">Pengeluaran (Uang Keluar)</option></select>
                        </div>
                        <div class="flex items-end gap-2">
                            <div class="flex-shrink-0"><label class="label-text">Warna</label><input v-model="form.color" type="color" class="input-field h-[42px] w-14" /></div>
                            <button type="submit" :disabled="form.processing" class="btn-primary flex-1">Simpan</button>
                        </div>
                    </form>
                </div>
            </Transition>

            <!-- Statistics Cards -->
            <div v-if="hasData && categories?.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div class="glass-card p-4 sm:p-5 accent-left-rose group hover:shadow-card-hover transition-all duration-300">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[10px] sm:text-xs font-semibold text-surface-500 uppercase tracking-wider">Total Kategori</span>
                        <div class="w-7 h-7 rounded-lg bg-rose-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-rose-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z" />
                            </svg>
                        </div>
                    </div>
                    <p class="stat-value text-plum text-xl sm:text-2xl">{{ totalCategories }}</p>
                    <p class="text-[10px] text-surface-500 mt-1">
                        {{ debitCategoriesCount }} Pemasukan · {{ creditCategoriesCount }} Pengeluaran
                    </p>
                </div>

                <div class="glass-card p-4 sm:p-5 accent-left-emerald group hover:shadow-card-hover transition-all duration-300">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[10px] sm:text-xs font-semibold text-surface-500 uppercase tracking-wider">Transaksi Terklasifikasi</span>
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="stat-value text-emerald-600 text-xl sm:text-2xl">{{ totalCategorizedTransactions }}</p>
                    <p class="text-[10px] text-surface-500 mt-1">Mutasi berhasil dipetakan ke kategori</p>
                </div>
            </div>

            <!-- No Data State -->
            <div v-if="!hasData" class="glass-card p-12 text-center">
                <svg class="w-12 h-12 text-surface-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" /></svg>
                <p class="font-medium text-surface-600">Belum ada data transaksi.</p>
                <p class="text-xs mt-1 text-surface-500">Silakan import data mutasi bank terlebih dahulu melalui menu <strong>Import Data</strong> agar kategori dan aturan dapat ditampilkan.</p>
            </div>

            <!-- Table -->
            <div v-else-if="categories.length > 0" class="glass-card overflow-hidden">
                <div class="hidden sm:block table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="w-1/2 sm:w-[45%]">Nama</th>
                                <th class="w-1/4 sm:w-[20%]">Tipe</th>
                                <th class="w-1/4 sm:w-[20%]">Transaksi</th>
                                <th v-if="canManage" class="w-[15%] text-right pr-6">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="cat in categories" :key="cat.id" :class="cat.is_suggested ? 'bg-amber-50/30' : ''">
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: cat.color }" />
                                        {{ cat.name }}
                                        <span v-if="cat.is_suggested" class="badge-yellow text-[10px]">Disarankan Sistem</span>
                                    </div>
                                </td>
                                <td><span :class="cat.type === 'DEBIT' ? 'badge-green' : 'badge-red'">{{ cat.type === 'DEBIT' ? 'Pemasukan' : 'Pengeluaran' }}</span></td>
                                <td>
                                    <button 
                                        @click="goToTransactions(cat)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100/80 hover:text-rose-800 border border-rose-200/40 transition-all duration-300 transform hover:scale-[1.03]"
                                        title="Klik untuk melihat detail transaksi kategori ini"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ cat.transactions_count }} Transaksi
                                    </button>
                                </td>
                                <td v-if="canManage" class="text-right pr-6">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button v-if="cat.is_suggested" @click="approveCategory(cat)" class="text-emerald-500 hover:text-emerald-700 p-1.5 rounded-lg hover:bg-emerald-50 transition-colors" title="Setujui kategori">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        </button>
                                        <button @click="confirmDelete(cat)" class="text-surface-500 hover:text-red-500 p-1.5 rounded-lg hover:bg-red-50 transition-colors" title="Hapus kategori">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="sm:hidden p-4 space-y-3">
                    <div v-for="cat in categories" :key="cat.id" class="mobile-card space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: cat.color }" />
                                <span class="font-medium truncate">{{ cat.name }}</span>
                                <span v-if="cat.is_suggested" class="badge-yellow text-[10px] flex-shrink-0">Disarankan</span>
                            </div>
                            <span :class="[cat.type === 'DEBIT' ? 'badge-green' : 'badge-red', 'flex-shrink-0 ml-2']">{{ cat.type === 'DEBIT' ? 'Pemasukan' : 'Pengeluaran' }}</span>
                        </div>
                        <button 
                            @click="goToTransactions(cat)" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100/80 hover:text-rose-800 border border-rose-200/40 transition-all duration-300 w-full justify-center mt-1"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ cat.transactions_count }} Transaksi
                        </button>
                        <div v-if="canManage" class="flex items-center gap-2 pt-1">
                            <button v-if="cat.is_suggested" @click="approveCategory(cat)" class="text-emerald-500 hover:text-emerald-700 text-xs font-semibold flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-emerald-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                Setujui
                            </button>
                            <button @click="confirmDelete(cat)" class="text-red-400 hover:text-red-600 text-xs font-semibold flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12 text-surface-500">
                <svg class="w-12 h-12 text-surface-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /></svg>
                <p class="font-medium text-surface-600">Tidak ada data kategori.</p>
                <p class="text-xs mt-1">Belum ada kategori yang ditambahkan untuk rekening ini.</p>
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
.slide-up-enter-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-leave-active { transition: all 0.25s cubic-bezier(0.5, 0, 0.75, 0); }
.slide-up-enter-from { opacity: 0; transform: translateY(16px); }
.slide-up-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
