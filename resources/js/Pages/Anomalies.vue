<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DateRangePicker from '@/Components/DateRangePicker.vue';
import { ref, inject, onMounted } from 'vue';

const props = defineProps({
    anomalies: Object,
    accounts: Array,
    filters: Object,
});

const addToast = inject('addToast');
const page = usePage();

const selectedAccountId = ref(props.filters?.account_id || '');
const dateFilters = ref({
    preset: props.filters?.preset || null,
    date_from: props.filters?.date_from || null,
    date_to: props.filters?.date_to || null,
});

function reloadData() {
    router.get('/anomalies', {
        severity: props.filters?.severity || 'ALL',
        type: props.filters?.type || 'ALL',
        account_id: selectedAccountId.value || undefined,
        ...dateFilters.value,
    }, { preserveState: true, preserveScroll: true });
}

function onDateUpdate(val) {
    dateFilters.value = val;
    reloadData();
}

function onAccountChange() {
    reloadData();
}

// Review modal state
const showReviewModal = ref(false);
const reviewingFlag = ref(null);
const reviewNote = ref('');
const isDismissing = ref(false);
const needsLeaderAction = ref(false);

// Template notes
const noteTemplates = [
    'Sudah dikonfirmasi — transaksi valid.',
    'Perlu ditindaklanjuti oleh pimpinan.',
    'Transaksi sudah diverifikasi dengan bukti transfer.',
    'Nominal sesuai dengan invoice/PO.',
    'Transaksi rutin, bukan anomali.',
    'Perlu klarifikasi ke pihak pengirim/penerima.',
    'Dana sudah dikembalikan/refund.',
];

function setSeverity(severity) {
    router.get('/anomalies', {
        severity,
        type: props.filters?.type || 'ALL',
        account_id: selectedAccountId.value || undefined,
        ...dateFilters.value,
    }, { preserveState: true, preserveScroll: true });
}

function setType(type) {
    router.get('/anomalies', {
        severity: props.filters?.severity || 'ALL',
        type,
        account_id: selectedAccountId.value || undefined,
        ...dateFilters.value,
    }, { preserveState: true, preserveScroll: true });
}

function runDetection() {
    router.post('/anomalies/detect', {
        account_id: selectedAccountId.value || undefined,
    }, {
        preserveScroll: true,
        onSuccess: () => addToast?.('Deteksi anomali selesai', 'success'),
    });
}

function openReview(flag, dismiss = false) {
    reviewingFlag.value = flag;
    isDismissing.value = dismiss;
    reviewNote.value = '';
    needsLeaderAction.value = false;
    showReviewModal.value = true;
}

function submitReview() {
    if (!reviewingFlag.value) return;
    router.patch(`/anomalies/${reviewingFlag.value.id}`, {
        dismiss: isDismissing.value,
        needs_leader_action: needsLeaderAction.value,
        review_note: reviewNote.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showReviewModal.value = false;
            reviewingFlag.value = null;
            addToast?.(isDismissing.value ? 'Anomali diabaikan' : 'Anomali telah ditinjau', 'success');
        },
    });
}

function selectTemplate(tpl) {
    reviewNote.value = tpl;
    if (tpl.toLowerCase().includes('pimpinan') || tpl.toLowerCase().includes('tindak')) {
        needsLeaderAction.value = true;
    } else {
        needsLeaderAction.value = false;
    }
}

function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v); }
function formatDate(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); }

function severityLabel(s) {
    return { HIGH: 'Tinggi', MEDIUM: 'Sedang', LOW: 'Rendah' }[s] || s;
}
function severityClass(s) {
    return { HIGH: 'badge-red', MEDIUM: 'badge-yellow', LOW: 'badge-blue' }[s] || 'badge-rose';
}

function typeLabel(method) {
    if (method?.startsWith('INCOME')) return 'Pemasukan';
    if (method?.startsWith('EXPENSE')) return 'Pengeluaran';
    return method;
}
function typeIcon(method) {
    if (method?.startsWith('INCOME')) return '💰';
    if (method?.startsWith('EXPENSE')) return '💸';
    return '⚠';
}
function subtypeLabel(method) {
    if (method === 'INCOME_INSTANT') return 'Instan ≥ 10jt';
    if (method === 'INCOME_ACCUMULATED') return 'Akumulasi ≥ 10jt';
    if (method === 'EXPENSE_MISMATCH') return 'Tidak Seimbang';
    return method;
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const flagId = params.get('flag_id');
    if (flagId) {
        const id = parseInt(flagId);
        if (!isNaN(id)) {
            setTimeout(() => {
                const el = document.getElementById('flag-card-' + id);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('transition-all', 'duration-500', 'ring-2', 'ring-rose-500/80', 'ring-offset-2', 'scale-[1.01]', 'shadow-lg');
                    setTimeout(() => {
                        el.classList.remove('ring-2', 'ring-rose-500/80', 'ring-offset-2', 'scale-[1.01]', 'shadow-lg');
                    }, 4000);
                }
            }, 300);
        }
    }
});
</script>

<template>
    <Head title="Pengawasan Transaksi — SIKUBI" />
    <AppLayout>
        <div class="space-y-6 animate-fade-in">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="page-title text-lg sm:text-2xl">Pusat Pengawasan Transaksi</h1>
                    <p class="text-xs sm:text-sm text-surface-600 mt-0.5">Pantau mutasi luar biasa secara real-time dan verifikasi kepatuhan transaksi di sini.</p>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select v-model="selectedAccountId" @change="onAccountChange" class="filter-field w-full sm:!w-auto !pr-8 sm:max-w-[200px] flex-shrink-0">
                        <option value="">Semua Rekening</option>
                        <option value="cash">Transaksi Tunai</option>
                        <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.account_alias || acc.bank_name }}</option>
                    </select>
                    <button @click="runDetection" class="btn-primary text-xs !py-1.5 !px-4 sm:!py-2.5 sm:!px-5 flex-shrink-0">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                        Pindai Mutasi
                    </button>
                </div>
            </div>

            <!-- Timeline / Date Range Picker -->
            <div>
                <DateRangePicker :initial-from="filters?.date_from" :initial-to="filters?.date_to" :initial-preset="filters?.preset" @update="onDateUpdate" />
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Type filter -->
                <div class="flex items-center bg-cream-200/60 p-1 rounded-xl">
                    <button @click="setType('ALL')" :class="['px-2.5 sm:px-4 py-1 sm:py-1.5 text-[10px] sm:text-xs font-semibold rounded-lg transition-all', filters?.type === 'ALL' ? 'bg-white text-plum shadow-soft' : 'text-surface-600 hover:text-plum']">Semua</button>
                    <button @click="setType('INCOME')" :class="['px-2.5 sm:px-4 py-1 sm:py-1.5 text-[10px] sm:text-xs font-semibold rounded-lg transition-all', filters?.type?.startsWith('INCOME') ? 'bg-white text-emerald-600 shadow-soft' : 'text-surface-600 hover:text-plum']">💰 Pemasukan</button>
                    <button @click="setType('EXPENSE')" :class="['px-2.5 sm:px-4 py-1 sm:py-1.5 text-[10px] sm:text-xs font-semibold rounded-lg transition-all', filters?.type?.startsWith('EXPENSE') ? 'bg-white text-red-500 shadow-soft' : 'text-surface-600 hover:text-plum']">💸 Pengeluaran</button>
                </div>

                <!-- Severity filter -->
                <div class="flex items-center bg-cream-200/60 p-1 rounded-xl">
                    <button @click="setSeverity('ALL')" :class="['px-2.5 sm:px-4 py-1 sm:py-1.5 text-[10px] sm:text-xs font-semibold rounded-lg transition-all', filters?.severity === 'ALL' ? 'bg-white text-plum shadow-soft' : 'text-surface-600 hover:text-plum']">Semua</button>
                    <button @click="setSeverity('HIGH')" :class="['px-2.5 sm:px-4 py-1 sm:py-1.5 text-[10px] sm:text-xs font-semibold rounded-lg transition-all', filters?.severity === 'HIGH' ? 'bg-white text-red-500 shadow-soft' : 'text-surface-600 hover:text-plum']">Tinggi</button>
                    <button @click="setSeverity('MEDIUM')" :class="['px-2.5 sm:px-4 py-1 sm:py-1.5 text-[10px] sm:text-xs font-semibold rounded-lg transition-all', filters?.severity === 'MEDIUM' ? 'bg-white text-amber-500 shadow-soft' : 'text-surface-600 hover:text-plum']">Sedang</button>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="glass-card p-4 border-l-4 border-l-emerald-400">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-lg">💰</span>
                        <h3 class="text-sm font-semibold text-plum">Mutasi Masuk Luar Biasa</h3>
                    </div>
                    <p class="text-xs text-surface-600">Transaksi masuk instan atau terakumulasi dalam bulan berjalan yang mencapai <strong>Rp 10 juta</strong> atau lebih.</p>
                </div>
                <div class="glass-card p-4 border-l-4 border-l-red-400">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-lg">💸</span>
                        <h3 class="text-sm font-semibold text-plum">Mutasi Keluar Tidak Seimbang</h3>
                    </div>
                    <p class="text-xs text-surface-600">Transaksi pengeluaran dana ke suatu rekening yang <strong>tidak seimbang</strong> dengan rasio total pemasukan terkait.</p>
                </div>
            </div>

            <!-- Anomaly List -->
            <div class="space-y-3">
                <div v-for="(flag, index) in anomalies.data" :key="flag.id" :id="'flag-card-' + flag.id" class="glass-card p-4 sm:p-5 animate-scale-in" :style="{ 'animation-delay': `${index * 60}ms`, 'animation-fill-mode': 'both' }">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <!-- Type & Severity badges -->
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span :class="[
                                    'badge text-[10px]',
                                    flag.detection_method?.startsWith('INCOME')
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : 'bg-red-50 text-red-700 border border-red-200'
                                ]">
                                    {{ typeIcon(flag.detection_method) }} {{ typeLabel(flag.detection_method) }}
                                </span>
                                <span class="badge text-[10px] bg-surface-100 text-surface-600 border border-surface-200">
                                    {{ subtypeLabel(flag.detection_method) }}
                                </span>
                                <span :class="severityClass(flag.severity)" class="text-[10px]">{{ severityLabel(flag.severity) }}</span>
                            </div>

                            <!-- Transaction info -->
                            <p class="text-sm font-semibold text-plum truncate">{{ flag.transaction?.description }}</p>
                            <p class="text-xs text-surface-500 mt-0.5">
                                {{ formatDate(flag.transaction?.transaction_date) }}
                                · {{ flag.transaction?.bank_account?.account_alias || flag.transaction?.bank_account?.bank_name }}
                            </p>
                            <p :class="['text-base font-bold mt-1', flag.transaction?.type === 'DEBIT' ? 'text-emerald-600' : 'text-red-500']">
                                {{ formatCurrency(flag.transaction?.amount || 0) }}
                            </p>

                            <!-- Reason -->
                            <p class="text-xs text-surface-600 mt-2 bg-cream-200/50 rounded-lg p-2.5 leading-relaxed">{{ flag.reason }}</p>

                            <!-- Review note (if reviewed) -->
                            <div v-if="flag.is_reviewed && flag.review_note" class="mt-2 text-xs bg-blue-50 border border-blue-200 rounded-lg p-2.5 text-blue-700">
                                📝 <strong>Catatan Admin:</strong> {{ flag.review_note }}
                            </div>
                            <!-- Leader review note (if reviewed by Leader) -->
                            <div v-if="flag.leader_reviewed_at" class="mt-2 text-xs rounded-lg p-2.5 border" :class="flag.is_approved_by_leader ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'">
                                👤 <strong>Catatan Pimpinan ({{ flag.is_approved_by_leader ? 'Disetujui' : 'Ditolak' }}):</strong> {{ flag.leader_note || '-' }}
                            </div>
                        </div>

                        <!-- Actions -->
                        <div v-if="!flag.is_reviewed" class="flex gap-2 flex-shrink-0">
                            <button @click="openReview(flag, false)" class="btn-secondary text-xs !py-1.5 !px-3">
                                ✍ Tinjau
                            </button>
                            <button @click="openReview(flag, true)" class="btn-ghost text-xs !py-1.5 !px-3">
                                Abaikan
                            </button>
                        </div>
                        <div v-else class="flex items-center gap-2 flex-shrink-0">
                            <span v-if="flag.is_dismissed" class="badge text-[10px] bg-surface-100 text-surface-500 border border-surface-200">⚠ Diabaikan</span>
                            <template v-else-if="flag.needs_leader_action">
                                <span v-if="!flag.leader_reviewed_at" class="badge text-[10px] bg-amber-500 text-white border border-amber-600 font-bold py-0.5 px-2 rounded-lg">⚠️ Menunggu Pimpinan</span>
                                <span v-else-if="flag.is_approved_by_leader" class="badge text-[10px] bg-emerald-500 text-white border border-emerald-600 font-bold py-0.5 px-2 rounded-lg">✓ Disetujui Pimpinan</span>
                                <span v-else class="badge text-[10px] bg-rose-600 text-white border border-rose-700 font-bold py-0.5 px-2 rounded-lg">❌ Ditolak Pimpinan</span>
                            </template>
                            <span v-else class="badge-green text-[10px]">✓ Ditinjau</span>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!anomalies.data?.length" class="glass-card p-12 text-center">
                    <svg class="w-12 h-12 text-surface-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                    <p class="text-surface-500 font-medium">Belum ada temuan resiko terdeteksi.</p>
                    <p class="text-xs text-surface-400 mt-1">Klik "Pindai Mutasi" untuk memulai analisis kepatuhan internal.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="anomalies.last_page > 1" class="flex justify-center gap-1.5 sm:gap-2 mt-6 flex-wrap">
                <template v-for="(link, idx) in anomalies.links" :key="link.url || 'ellipsis-' + idx">
                    <span v-if="link.url"
                        @click="router.get(link.url)"
                        :class="['px-3 py-1.5 text-sm rounded-lg transition-colors cursor-pointer', link.active ? 'bg-gradient-rose text-white' : 'text-surface-600 hover:bg-rose-50']"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <!-- Review Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showReviewModal" class="fixed inset-0 bg-plum/30 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showReviewModal = false">
                    <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl w-full max-w-lg p-6 animate-scale-in border border-white/60">
                        <div class="flex items-start justify-between pb-4 border-b border-rose-100/50 mb-4">
                            <div class="flex items-center gap-3">
                                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-soft flex-shrink-0', isDismissing ? 'bg-gradient-to-br from-amber-400 to-amber-500' : 'bg-gradient-rose']">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path v-if="isDismissing" stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-plum">
                                        {{ isDismissing ? 'Abaikan Anomali' : 'Tinjau Temuan Anomali' }}
                                    </h3>
                                    <p class="text-xs text-surface-500 mt-0.5">
                                        {{ isDismissing ? 'Berikan catatan mengapa anomali ini diabaikan.' : 'Berikan catatan singkat hasil tinjauan Anda.' }}
                                    </p>
                                </div>
                            </div>
                            <button @click="showReviewModal = false" class="text-surface-400 hover:text-plum p-1.5 rounded-lg hover:bg-rose-50/50 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Transaction Preview -->
                        <div v-if="reviewingFlag" class="bg-cream-100 rounded-xl p-3 mb-4">
                            <p class="text-sm font-semibold text-plum truncate">{{ reviewingFlag.transaction?.description }}</p>
                            <p :class="['text-sm font-bold mt-0.5', reviewingFlag.transaction?.type === 'DEBIT' ? 'text-emerald-600' : 'text-red-500']">
                                {{ formatCurrency(reviewingFlag.transaction?.amount || 0) }}
                            </p>
                        </div>

                        <!-- Template Notes -->
                        <div class="mb-3">
                            <p class="text-xs font-medium text-surface-500 mb-2">Template catatan:</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="(tpl, i) in noteTemplates" :key="i"
                                    @click="selectTemplate(tpl)"
                                    :class="['text-[10px] px-2.5 py-1 rounded-lg border transition-all', reviewNote === tpl ? 'bg-rose-50 border-rose-300 text-plum font-semibold' : 'bg-cream-100 border-surface-200 text-surface-600 hover:border-rose-300']"
                                >{{ tpl }}</button>
                            </div>
                        </div>

                        <!-- Explicit Minta Tindak Lanjut Pimpinan -->
                        <div v-if="!isDismissing" class="mb-4 bg-rose-50/40 border border-rose-200/40 rounded-xl p-3 flex items-start gap-2.5">
                            <input 
                                type="checkbox" 
                                id="needs_leader" 
                                v-model="needsLeaderAction" 
                                class="mt-1 rounded border-rose-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer"
                            />
                            <label for="needs_leader" class="cursor-pointer select-none">
                                <span class="text-xs font-bold text-rose-700 block">Minta Tindak Lanjut Pimpinan</span>
                                <span class="text-[10px] text-rose-600 block mt-0.5 leading-normal">
                                    Centang jika transaksi ini memerlukan peninjauan khusus atau otorisasi langsung oleh Direktur (Pimpinan).
                                </span>
                            </label>
                        </div>

                        <!-- Custom Note Input -->
                        <div class="mb-4">
                            <label class="label-text">Catatan (opsional)</label>
                            <textarea
                                v-model="reviewNote"
                                class="input-field !min-h-[80px] resize-none"
                                placeholder="Tulis catatan tinjauan..."
                            ></textarea>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 justify-end pt-3 mt-1 border-t border-rose-100/30">
                            <button @click="showReviewModal = false" class="btn-ghost text-xs">Batal</button>
                            <button
                                @click="submitReview"
                                :class="isDismissing ? 'btn-secondary' : 'btn-primary'"
                                class="text-xs"
                            >
                                {{ isDismissing ? 'Abaikan' : 'Simpan Tinjauan' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active { transition: opacity 0.2s ease; }
.fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
