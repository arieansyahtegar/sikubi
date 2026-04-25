<script setup>
import { ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DateRangePicker from '@/Components/DateRangePicker.vue';

const props = defineProps({ transactions: Object, filters: Object, categories: Array, accounts: Array });
const page = usePage();
const isAdmin = page.props.auth?.user?.role === 'ADMIN_KEUANGAN';

const search = ref(props.filters?.search || '');
const type = ref(props.filters?.type || '');
const categoryId = ref(props.filters?.category_id || '');
const accountId = ref(props.filters?.account_id || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

function buildParams() {
    return {
        search: search.value || undefined,
        type: type.value || undefined,
        category_id: categoryId.value || undefined,
        account_id: accountId.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    };
}

function applyFilters() {
    router.get('/transactions', buildParams(), { preserveState: true });
}

function onDateUpdate(val) {
    dateFrom.value = val.date_from || '';
    dateTo.value = val.date_to || '';
    if (val.preset) {
        // For presets, let the backend resolve dates
        router.get('/transactions', { ...buildParams(), preset: val.preset, date_from: undefined, date_to: undefined }, { preserveState: true });
    } else {
        applyFilters();
    }
}

function exportCsv() {
    const params = new URLSearchParams(buildParams());
    window.open('/transactions/export?' + params.toString(), '_blank');
}

function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v); }
function formatDate(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); }
</script>

<template>
    <Head title="Transaksi — SIKUBI" />
    <AppLayout>
        <div class="space-y-6 animate-fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="page-title">Transaksi</h1>
                    <p class="text-sm text-surface-600 mt-1">Riwayat seluruh transaksi keuangan</p>
                </div>
                <button v-if="isAdmin" @click="exportCsv" class="btn-secondary text-xs gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12M12 16.5V3" /></svg>
                    Export CSV
                </button>
            </div>

            <div class="glass-card p-4 sm:p-6">
                <!-- Filters -->
                <div class="space-y-3 mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <input v-model="search" @keyup.enter="applyFilters" type="text" placeholder="Cari deskripsi..." class="input-field" />
                        <select v-model="type" @change="applyFilters" class="input-field">
                            <option value="">Semua Tipe</option>
                            <option value="DEBIT">Pemasukan</option>
                            <option value="CREDIT">Pengeluaran</option>
                        </select>
                        <select v-if="categories?.length" v-model="categoryId" @change="applyFilters" class="input-field">
                            <option value="">Semua Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <select v-if="accounts?.length" v-model="accountId" @change="applyFilters" class="input-field">
                            <option value="">Semua Rekening</option>
                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.account_alias || acc.bank_name }}</option>
                        </select>
                        <button @click="applyFilters" class="btn-primary w-full h-full">Cari</button>
                    </div>
                    <DateRangePicker :initial-from="filters?.date_from" :initial-to="filters?.date_to" @update="onDateUpdate" />
                </div>

                <!-- Summary -->
                <div v-if="transactions.total" class="mb-4 text-xs text-surface-500">
                    Menampilkan {{ transactions.from }}–{{ transactions.to }} dari {{ transactions.total }} transaksi
                </div>

                <!-- Not Found State -->
                <div v-if="!transactions.data.length" class="text-center py-12 text-surface-500">
                    <svg class="w-12 h-12 text-surface-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    <p class="font-medium text-surface-600">Tidak ada transaksi yang ditemukan.</p>
                    <p class="text-xs mt-1">Coba sesuaikan filter pencarian atau rentang tanggal.</p>
                </div>

                <template v-else>
                    <!-- Desktop Table -->
                    <div class="hidden sm:block table-container">
                        <table class="data-table">
                            <thead><tr><th>Tanggal</th><th>Deskripsi</th><th>Kategori</th><th>Metode</th><th>Rekening</th><th class="text-right">Jumlah</th></tr></thead>
                            <tbody>
                                <tr v-for="tx in transactions.data" :key="tx.id">
                                    <td class="whitespace-nowrap">{{ formatDate(tx.transaction_date) }}</td>
                                    <td class="max-w-xs truncate">{{ tx.description }}</td>
                                    <td>
                                        <span v-if="tx.category" class="badge" :style="{ background: tx.category.color + '15', color: tx.category.color, border: '1px solid ' + tx.category.color + '40' }">
                                            {{ tx.category.name }}
                                        </span>
                                        <span v-else class="text-amber-600 text-xs italic">Belum Terkategori</span>
                                    </td>
                                    <td class="text-xs">
                                        <span v-if="tx.classification_method === 'RULE_BASED'" class="badge-green text-[10px]">Aturan</span>
                                        <span v-else-if="tx.classification_method === 'PATTERN_MATCH'" class="badge-blue text-[10px]">Pola</span>
                                        <span v-else-if="tx.classification_method === 'HISTORICAL'" class="badge-yellow text-[10px]">Historis</span>
                                        <span v-else-if="tx.classification_method === 'MANUAL'" class="badge-rose text-[10px]">Manual</span>
                                        <span v-else-if="tx.classification_method === 'AUTO_SUGGESTED'" class="badge-yellow text-[10px]">Saran</span>
                                        <span v-else class="text-surface-400 text-[10px]">—</span>
                                    </td>
                                    <td class="text-xs">{{ tx.bank_account?.account_alias || tx.bank_account?.bank_name }}</td>
                                    <td :class="['text-right font-bold whitespace-nowrap', tx.type === 'DEBIT' ? 'text-emerald-600' : 'text-red-500']">
                                        {{ tx.type === 'DEBIT' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="sm:hidden space-y-3">
                        <div v-for="tx in transactions.data" :key="tx.id" class="mobile-card">
                            <div class="flex justify-between items-start">
                                <p class="text-sm font-semibold text-plum truncate flex-1">{{ tx.description }}</p>
                                <p :class="['text-sm font-bold ml-2', tx.type === 'DEBIT' ? 'text-emerald-600' : 'text-red-500']">
                                    {{ tx.type === 'DEBIT' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
                                </p>
                            </div>
                            <p class="text-xs text-surface-500">{{ formatDate(tx.transaction_date) }}</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="transactions.last_page > 1" class="flex justify-center gap-2 mt-6">
                        <template v-for="link in transactions.links" :key="link.label">
                            <button v-if="link.url"
                                @click="router.get(link.url)"
                                :class="['px-3 py-1.5 text-sm rounded-lg transition-colors', link.active ? 'bg-gradient-rose text-white' : 'text-surface-600 hover:bg-rose-50']"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
