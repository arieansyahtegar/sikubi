<script setup>
import { ref, inject, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    accounts: Array,
    transactions: Object,
    filters: Object
});

const addToast = inject('addToast');
const showForm = ref(false);
const form = useForm({ bank_name: '', account_number: '', account_alias: '', currency: 'IDR' });

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

const selectedAccountId = ref(props.filters?.account_id ? Number(props.filters.account_id) : null);
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

const timelineMonths = computed(() => {
    const list = [];
    const monthsName = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const baseDate = new Date(2026, 5, 23); // June 23, 2026
    for (let i = 0; i < 12; i++) {
        const d = new Date(baseDate.getFullYear(), baseDate.getMonth() - i, 1);
        list.push({
            label: `${monthsName[d.getMonth()]} ${d.getFullYear()}`,
            month: d.getMonth() + 1,
            year: d.getFullYear(),
            key: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
        });
    }
    return list;
});

const activeTimelineKey = computed(() => {
    if (!dateFrom.value || !dateTo.value) return '';
    const fromParts = dateFrom.value.split('-');
    const toParts = dateTo.value.split('-');
    if (fromParts.length === 3 && toParts.length === 3) {
        if (fromParts[0] === toParts[0] && fromParts[1] === toParts[1]) {
            const y = parseInt(fromParts[0]);
            const m = parseInt(fromParts[1]);
            const lastDay = new Date(y, m, 0).getDate();
            if (parseInt(fromParts[2]) === 1 && parseInt(toParts[2]) === lastDay) {
                return `${fromParts[0]}-${fromParts[1]}`;
            }
        }
    }
    return '';
});

function applyFilters() {
    router.get('/accounts', {
        account_id: selectedAccountId.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function selectTimeline(month) {
    if (!month) {
        dateFrom.value = '';
        dateTo.value = '';
        applyFilters();
        return;
    }
    const lastDay = new Date(month.year, month.month, 0).getDate();
    dateFrom.value = `${month.year}-${String(month.month).padStart(2, '0')}-01`;
    dateTo.value = `${month.year}-${String(month.month).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
    applyFilters();
}

function selectAccount(acc) {
    if (selectedAccountId.value === acc.id) {
        selectedAccountId.value = null;
        dateFrom.value = '';
        dateTo.value = '';
        router.get('/accounts', {}, { preserveState: true, preserveScroll: true });
    } else {
        selectedAccountId.value = acc.id;
        dateFrom.value = '';
        dateTo.value = '';
        applyFilters();
    }
}

watch(() => props.filters, (f) => {
    selectedAccountId.value = f?.account_id ? Number(f.account_id) : null;
    dateFrom.value = f?.date_from || '';
    dateTo.value = f?.date_to || '';
}, { deep: true });

const selectedAccount = computed(() => {
    if (!selectedAccountId.value) return null;
    return props.accounts.find(a => Number(a.id) === Number(selectedAccountId.value));
});

function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v); }
function formatDate(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); }

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

const showEditModal = ref(false);
const editForm = useForm({
    id: null,
    bank_name: '',
    account_number: '',
    account_alias: '',
    currency: 'IDR'
});

function openEditModal(acc) {
    editForm.id = acc.id;
    editForm.bank_name = acc.bank_name;
    editForm.account_number = acc.account_number;
    editForm.account_alias = acc.account_alias || '';
    editForm.currency = acc.currency || 'IDR';
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editForm.reset();
}

function submitEdit() {
    editForm.put(`/accounts/${editForm.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            addToast?.('Rekening berhasil diperbarui', 'success');
        }
    });
}
</script>

<template>
    <Head title="Rekening Bank — SIKUBI" />
    <AppLayout>
        <div class="space-y-6 animate-fade-in">
            <div class="flex items-start sm:items-center justify-between gap-2 flex-col sm:flex-row">
                <div>
                    <h1 class="page-title">Rekening Bank</h1>
                    <p class="text-sm text-surface-600 mt-1">Kelola rekening bank yang terhubung</p>
                </div>
                <button @click="showForm = !showForm" class="btn-primary w-full sm:w-auto justify-center">+ Tambah</button>
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
                <div v-for="acc in accounts" :key="acc.id" 
                    @click="selectAccount(acc)"
                    :class="[
                        'glass-card-hover p-5 cursor-pointer transition-all border-2',
                        selectedAccountId === acc.id 
                            ? 'border-plum bg-rose-50/10 shadow-md ring-2 ring-rose-100/50 scale-[1.02]' 
                            : 'border-transparent'
                    ]"
                >
                    <div class="flex items-start justify-between">
                        <div class="w-10 h-10 rounded-xl bg-gradient-rose flex items-center justify-center text-white font-bold text-sm">
                            {{ acc.bank_name.substring(0, 2) }}
                        </div>
                        <div class="flex items-center gap-1">
                            <button @click.stop="openEditModal(acc)" class="text-surface-500 hover:text-plum transition-colors p-1 rounded-lg hover:bg-rose-50" title="Ubah rekening">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                            </button>
                            <button @click.stop="confirmDelete(acc)" class="text-surface-500 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50" title="Hapus rekening">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                            </button>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-plum mt-3">{{ acc.account_alias || acc.bank_name }}</h3>
                    <p class="text-sm text-surface-500">{{ acc.bank_name }} · {{ acc.account_number }}</p>
                    <p class="text-xs text-surface-500 mt-2">{{ acc.transactions_count || 0 }} transaksi</p>
                </div>
            </div>

            <!-- Transactions Section (shown only when an account is selected) -->
            <Transition name="slide-up">
                <div v-if="selectedAccount && transactions" class="glass-card p-5 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-rose-100/40 pb-4">
                        <div>
                            <h2 class="text-lg font-bold text-plum">
                                Transaksi Rekening: {{ selectedAccount.account_alias || selectedAccount.bank_name }}
                            </h2>
                            <p class="text-xs text-surface-500 mt-0.5">
                                {{ selectedAccount.bank_name }} · {{ selectedAccount.account_number }}
                            </p>
                        </div>
                        <button @click="selectAccount(selectedAccount)" class="text-xs text-surface-500 hover:text-plum font-semibold flex items-center gap-1">
                            ✕ Tutup Transaksi
                        </button>
                    </div>

                    <!-- Timeline Filter inside Bank Account page -->
                    <div class="pt-1">
                        <p class="text-[10px] font-bold text-surface-500 uppercase tracking-wider mb-2">Timeline Cepat:</p>
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-2 scrollbar-thin">
                            <button
                                @click="selectTimeline('')"
                                :class="[
                                    'px-3 py-1.5 text-[11px] font-semibold rounded-lg transition-all whitespace-nowrap border',
                                    !activeTimelineKey
                                        ? 'bg-gradient-rose text-white border-rose-400 shadow-soft'
                                        : 'bg-white text-surface-600 border-surface-200 hover:border-rose-300 hover:text-rose-600'
                                ]"
                            >
                                Semua Waktu <span v-if="!activeTimelineKey && transactions" class="ml-1 text-[10px] opacity-90">({{ transactions.total }})</span>
                            </button>
                            <button
                                v-for="month in timelineMonths" :key="month.key"
                                @click="selectTimeline(month)"
                                :class="[
                                    'px-3 py-1.5 text-[11px] font-semibold rounded-lg transition-all whitespace-nowrap border',
                                    activeTimelineKey === month.key
                                        ? 'bg-gradient-rose text-white border-rose-400 shadow-soft'
                                        : 'bg-white text-surface-600 border-surface-200 hover:border-rose-300 hover:text-rose-600'
                                ]"
                            >
                                {{ month.label }} <span v-if="activeTimelineKey === month.key && transactions" class="ml-1 text-[10px] opacity-90">({{ transactions.total }})</span>
                            </button>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div v-if="transactions.total" class="text-xs text-surface-500">
                        Menampilkan {{ transactions.from }}–{{ transactions.to }} dari {{ transactions.total }} transaksi
                    </div>

                    <!-- Empty State -->
                    <div v-if="!transactions.data?.length" class="text-center py-12 text-surface-500">
                        <svg class="w-12 h-12 text-surface-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                        <p class="font-medium text-surface-600">Tidak ada transaksi yang ditemukan.</p>
                        <p class="text-xs mt-1">Coba pilih timeline bulan lain.</p>
                    </div>

                    <template v-else>
                        <!-- Desktop Table -->
                        <div class="hidden sm:block table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left">Tanggal</th>
                                        <th class="px-4 py-3 text-left">Deskripsi</th>
                                        <th class="px-4 py-3 text-left">Kategori</th>
                                        <th class="px-4 py-3 text-right">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="tx in transactions.data" :key="tx.id">
                                        <td class="px-4 py-3.5 whitespace-nowrap">{{ formatDate(tx.transaction_date) }}</td>
                                        <td class="px-4 py-3.5 max-w-md truncate" :title="tx.description">{{ tx.description }}</td>
                                        <td class="px-4 py-3.5">
                                            <span v-if="tx.category" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold shadow-sm" :style="{ background: tx.category.color + '12', color: tx.category.color, border: '1px solid ' + tx.category.color + '30' }">
                                                <span class="w-1.5 h-1.5 rounded-full" :style="{ background: tx.category.color }"></span>
                                                {{ tx.category.name }}
                                            </span>
                                            <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-600 border border-amber-200/60 shadow-sm italic">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                                Belum Terkategori
                                            </span>
                                        </td>
                                        <td :class="['px-4 py-3.5 text-right font-bold whitespace-nowrap', tx.type === 'DEBIT' ? 'text-emerald-600' : 'text-red-500']">
                                            {{ tx.type === 'DEBIT' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="sm:hidden space-y-3">
                            <div v-for="tx in transactions.data" :key="tx.id" class="mobile-card space-y-2.5">
                                <div class="flex justify-between items-start">
                                    <p class="text-sm font-semibold text-plum truncate flex-1" :title="tx.description">{{ tx.description }}</p>
                                    <p :class="['text-sm font-bold ml-2', tx.type === 'DEBIT' ? 'text-emerald-600' : 'text-red-500']">
                                        {{ tx.type === 'DEBIT' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
                                    </p>
                                </div>
                                <div class="flex justify-between items-center text-xs text-surface-500">
                                    <span>{{ formatDate(tx.transaction_date) }}</span>
                                    <div>
                                        <span v-if="tx.category" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold" :style="{ background: tx.category.color + '12', color: tx.category.color, border: '1px solid ' + tx.category.color + '30' }">
                                            <span class="w-1.5 h-1.5 rounded-full" :style="{ background: tx.category.color }"></span>
                                            {{ tx.category.name }}
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-600 border border-amber-200/60 italic">
                                            <span class="w-1 h-1 rounded-full bg-amber-400"></span>
                                            Belum Terkategori
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="transactions.last_page > 1" class="flex justify-center gap-1.5 sm:gap-2 mt-6 flex-wrap">
                            <template v-for="(link, idx) in transactions.links" :key="link.url || 'ellipsis-' + idx">
                                <span v-if="link.url"
                                    @click="router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                                    :class="['px-3 py-1.5 text-sm rounded-lg transition-colors cursor-pointer', link.active ? 'bg-gradient-rose text-white' : 'text-surface-600 hover:bg-rose-50']"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </template>
                </div>
            </Transition>
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

        <!-- Premium Edit Account Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
            <div class="fixed inset-0 bg-surface-900/60 backdrop-blur-sm transition-opacity" @click="closeEditModal"></div>
            
            <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-2xl transition-all border border-rose-50/50 animate-scale-in">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-rose-50/80">
                    <div>
                        <h3 class="text-lg font-bold text-plum">Ubah Rekening Bank</h3>
                        <p class="text-xs text-surface-500 mt-0.5">Ubah nama, nomor, dan alias rekening bank</p>
                    </div>
                    <button @click="closeEditModal" class="text-surface-400 hover:text-plum p-1.5 rounded-lg hover:bg-rose-50/50 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="submitEdit" class="space-y-4 pt-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-surface-600 mb-1">Nama Bank</label>
                        <input v-model="editForm.bank_name" class="input-field !rounded-xl !py-2" placeholder="BCA" required />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-surface-600 mb-1">No. Rekening</label>
                        <input v-model="editForm.account_number" class="input-field !rounded-xl !py-2" placeholder="1234567890" required />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-surface-600 mb-1">Alias Rekening</label>
                        <input v-model="editForm.account_alias" class="input-field !rounded-xl !py-2" placeholder="BCA Utama" />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-rose-50/80">
                        <button type="button" @click="closeEditModal" class="btn-secondary text-xs !py-2 !px-4 !rounded-xl">
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="editForm.processing" 
                            class="btn-primary text-xs !py-2 !px-4 !rounded-xl gap-1.5 flex items-center justify-center"
                        >
                            <svg v-if="editForm.processing" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.slide-up-enter-active { transition: all 0.3s ease-out; }
.slide-up-leave-active { transition: all 0.2s ease-in; }
.slide-up-enter-from { opacity: 0; transform: translateY(16px); }
.slide-up-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
