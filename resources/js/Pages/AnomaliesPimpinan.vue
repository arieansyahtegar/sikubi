<script setup>
import { ref, computed, inject } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DateRangePicker from '@/Components/DateRangePicker.vue';

const props = defineProps({
    anomalies: Object,
    accounts: Array,
    filters: Object,
    stats: Object,
});

const page = usePage();
const addToast = inject('addToast');

const selectedAccountId = ref(props.filters?.account_id || '');
const dateFilters = ref({
    preset: props.filters?.preset || null,
    date_from: props.filters?.date_from || null,
    date_to: props.filters?.date_to || null,
});

const leaderNote = ref('');
const isApproved = ref(true);
const isSubmitting = ref(false);

function reloadData() {
    router.get('/anomalies/check', {
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

function formatCurrency(v) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v);
}
function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatDateTime(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }) + ' WIB';
}

function severityLabel(s) {
    return { HIGH: 'Tinggi', MEDIUM: 'Sedang', LOW: 'Rendah' }[s] || s;
}

function subtypeLabel(method) {
    if (method === 'INCOME_INSTANT') return 'Instan ≥ 10jt';
    if (method === 'INCOME_ACCUMULATED') return 'Akumulasi ≥ 10jt';
    if (method === 'EXPENSE_MISMATCH') return 'Tidak Seimbang';
    return method;
}

function needsLeaderAction(flag) {
    if (!flag.is_reviewed || flag.is_dismissed) return false;
    if (flag.leader_reviewed_at) return false;
    if (flag.needs_leader_action) return true;
    const note = (flag.review_note || '').toLowerCase();
    return note.includes('pimpinan') || note.includes('tindak');
}

function hasLeaderAction(flag) {
    return !!flag.leader_reviewed_at;
}

// Expandable anomaly cards
const expandedId = ref(null);
function toggleExpand(id) {
    if (expandedId.value !== id) {
        leaderNote.value = '';
        isApproved.value = true;
    }
    expandedId.value = expandedId.value === id ? null : id;
}

function submitLeaderAction(flagId) {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    
    router.patch(`/anomalies/${flagId}/leader-action`, {
        approve: isApproved.value,
        leader_note: leaderNote.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            leaderNote.value = '';
            addToast?.('Tindak lanjut keputusan berhasil disimpan', 'success');
        },
        onError: () => {
            isSubmitting.value = false;
            addToast?.('Gagal menyimpan tindak lanjut', 'error');
        }
    });
}
</script>

<template>
    <Head title="Otorisasi Mutasi — SIKUBI" />
    <AppLayout>
        <div class="space-y-6 animate-fade-in">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="page-title text-lg sm:text-2xl">Otorisasi & Persetujuan Mutasi</h1>
                    <p class="text-xs sm:text-sm text-surface-600 mt-0.5">Tinjau temuan transaksi luar biasa dan berikan keputusan otorisasi secara cepat.</p>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select v-model="selectedAccountId" @change="onAccountChange" class="filter-field w-full sm:!w-auto !pr-8 sm:max-w-[200px] flex-shrink-0">
                        <option value="">Semua Rekening</option>
                        <option value="cash">Transaksi Tunai</option>
                        <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.account_alias || acc.bank_name }}</option>
                    </select>
                </div>
            </div>
            <div>
                <DateRangePicker :initial-from="filters?.date_from" :initial-to="filters?.date_to" :initial-preset="filters?.preset" @update="onDateUpdate" />
            </div>

            <!-- KPI Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="glass-card p-4 sm:p-5 border-l-4 border-l-amber-400">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold text-surface-500 uppercase tracking-wider">Total Temuan</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                    </div>
                    <p class="stat-value text-amber-600 text-xl sm:text-2xl">{{ stats?.totalCount || 0 }}</p>
                    <p class="text-[10px] sm:text-xs text-surface-500 mt-1">Transaksi terindikasi luar biasa</p>
                </div>
                <div class="glass-card p-4 sm:p-5 border-l-4 border-l-rose-400">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold text-surface-500 uppercase tracking-wider">Belum Ditinjau</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="stat-value text-rose-500 text-xl sm:text-2xl">{{ stats?.unreviewedCount || 0 }}</p>
                    <p class="text-[10px] sm:text-xs text-surface-500 mt-1">Sedang diverifikasi staf admin</p>
                </div>
                <div class="glass-card p-4 sm:p-5 border-l-4 border-l-red-500 bg-red-50/5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold text-surface-500 uppercase tracking-wider font-bold text-red-600">Butuh Otorisasi</span>
                        <div class="w-8 h-8 rounded-xl bg-red-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                        </div>
                    </div>
                    <p class="stat-value text-red-500 text-xl sm:text-2xl">{{ stats?.needsLeaderActionCount || 0 }}</p>
                    <p class="text-[10px] sm:text-xs text-surface-500 mt-1">Memerlukan otorisasi Direktur</p>
                </div>
                <div class="glass-card p-4 sm:p-5 border-l-4 border-l-emerald-400">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold text-surface-500 uppercase tracking-wider font-bold text-emerald-600">Otorisasi Selesai</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="stat-value text-emerald-600 text-xl sm:text-2xl">{{ stats?.reviewedCount || 0 }}</p>
                    <p class="text-[10px] sm:text-xs text-surface-500 mt-1">Telah disetujui / diarsipkan</p>
                </div>
            </div>

            <!-- Summary count -->
            <div v-if="anomalies.total" class="text-xs text-surface-500">
                Menampilkan {{ anomalies.from }}–{{ anomalies.to }} dari {{ anomalies.total }} anomali
            </div>

            <!-- Anomaly List -->
            <div class="space-y-3">
                <div
                    v-for="(flag, index) in anomalies.data"
                    :key="flag.id"
                    class="glass-card overflow-hidden group transition-all duration-300 hover:shadow-card-hover animate-scale-in"
                    :class="expandedId === flag.id ? 'ring-1 ring-rose-200' : ''"
                    :style="{ 'animation-delay': `${index * 75}ms`, 'animation-fill-mode': 'both' }"
                >
                    <!-- Clickable header row -->
                    <div
                        class="p-4 sm:p-5 cursor-pointer"
                        @click="toggleExpand(flag.id)"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <!-- Left: badges + title -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5 mb-2">
                                    <span :class="[
                                        'badge text-[10px] py-0.5 px-2.5 rounded-lg font-semibold',
                                        flag.detection_method?.startsWith('INCOME')
                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                            : 'bg-red-50 text-red-700 border border-red-200'
                                    ]">
                                        {{ flag.detection_method?.startsWith('INCOME') ? '💰 Pemasukan' : '💸 Pengeluaran' }}
                                    </span>
                                    <span class="badge text-[10px] py-0.5 px-2.5 rounded-lg bg-surface-100 text-surface-600 border border-surface-200 font-semibold">
                                        {{ subtypeLabel(flag.detection_method) }}
                                    </span>
                                    <span :class="[
                                        'badge text-[10px] py-0.5 px-2.5 rounded-lg font-semibold',
                                        flag.severity === 'HIGH' ? 'bg-red-100 text-red-600 border border-red-200' :
                                        flag.severity === 'MEDIUM' ? 'bg-amber-100 text-amber-600 border border-amber-200' :
                                        'bg-blue-100 text-blue-600 border border-blue-200'
                                    ]">
                                        {{ severityLabel(flag.severity) }}
                                    </span>
                                    <span class="badge text-[10px] py-0.5 px-2.5 rounded-lg bg-surface-50 text-surface-600 border border-surface-200 font-semibold">
                                        Skor Resiko: {{ (flag.score * 100).toFixed(0) }}%
                                    </span>
                                </div>
                                <p class="text-sm font-semibold text-plum truncate">{{ flag.transaction?.description }}</p>
                                <p class="text-xs text-surface-500 mt-0.5">
                                    {{ formatDate(flag.transaction?.transaction_date) }}
                                    · {{ flag.transaction?.bank_account?.account_alias || flag.transaction?.bank_account?.bank_name || '-' }}
                                </p>
                            </div>

                            <!-- Right: amount + status + expand icon -->
                            <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0 min-w-0">
                                <p :class="['text-sm sm:text-lg font-bold whitespace-nowrap', flag.transaction?.type === 'DEBIT' ? 'text-emerald-600' : 'text-red-500']">
                                    {{ flag.transaction?.type === 'DEBIT' ? '+' : '-' }}{{ formatCurrency(flag.transaction?.amount || 0) }}
                                </p>
                                <!-- Status badge -->
                                <div v-if="flag.is_reviewed" class="flex-shrink-0">
                                    <span v-if="flag.is_dismissed" class="badge text-[9px] sm:text-[10px] bg-surface-100 text-surface-500 border border-surface-200 inline-flex items-center gap-1 py-0.5 px-1.5 sm:px-2 rounded-lg font-semibold whitespace-nowrap">
                                        ⚠ Diabaikan
                                    </span>
                                    <span v-else-if="needsLeaderAction(flag)" class="badge text-[9px] sm:text-[10px] bg-red-500 text-white border border-red-600 inline-flex items-center gap-1 py-0.5 px-1.5 sm:px-2 rounded-lg font-bold whitespace-nowrap">
                                        ⚠️ Butuh Tindak Lanjut
                                    </span>
                                    <span v-else-if="hasLeaderAction(flag)" :class="[
                                        'badge text-[9px] sm:text-[10px] inline-flex items-center gap-1 py-0.5 px-1.5 sm:px-2 rounded-lg font-bold text-white whitespace-nowrap',
                                        flag.is_approved_by_leader ? 'bg-emerald-500 border border-emerald-600' : 'bg-rose-600 border border-rose-700'
                                    ]">
                                        {{ flag.is_approved_by_leader ? '✓ Disetujui Pimpinan' : '❌ Ditolak Pimpinan' }}
                                    </span>
                                    <span v-else class="badge text-[9px] sm:text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1 py-0.5 px-1.5 sm:px-2 rounded-lg font-semibold whitespace-nowrap">
                                        ✓ Terverifikasi
                                    </span>
                                </div>
                                <span v-else class="badge text-[9px] sm:text-[10px] bg-amber-50 text-amber-600 border border-amber-200/60 inline-flex items-center gap-1 font-bold py-0.5 px-1.5 sm:px-2 rounded-lg whitespace-nowrap flex-shrink-0">
                                    🚨 Pending
                                </span>

                                <!-- Chevron -->
                                <svg :class="['w-4 h-4 text-surface-400 transition-transform duration-300 flex-shrink-0', expandedId === flag.id ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Expanded detail -->
                    <Transition name="expand">
                        <div v-if="expandedId === flag.id" class="border-t border-rose-100/40 bg-cream-100/20 p-4 sm:p-5 space-y-4">
                            <!-- Analysis -->
                            <div class="rounded-xl bg-white border border-rose-100/30 p-4 shadow-sm">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-6 h-6 rounded-lg bg-slate-100 border border-slate-300 flex items-center justify-center text-slate-600">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <h4 class="text-xs font-bold text-plum uppercase tracking-wider">Laporan Analisis Mutasi</h4>
                                </div>
                                <p class="text-xs text-surface-700 leading-relaxed">{{ flag.reason }}</p>
                            </div>

                            <!-- Admin Verification Status -->
                            <div class="rounded-xl border p-4 shadow-sm" :class="flag.is_reviewed ? (flag.is_dismissed ? 'bg-surface-50 border-surface-200' : (needsLeaderAction(flag) || hasLeaderAction(flag) ? 'bg-red-50/40 border-red-200/50' : 'bg-emerald-50/30 border-emerald-200/40')) : 'bg-amber-50/30 border-amber-200/40'">
                                <div class="flex items-center gap-2 mb-2">
                                    <div :class="['w-6 h-6 rounded-lg flex items-center justify-center', flag.is_reviewed ? (flag.is_dismissed ? 'bg-surface-200' : (needsLeaderAction(flag) || hasLeaderAction(flag) ? 'bg-red-500 animate-pulse' : 'bg-emerald-500')) : 'bg-amber-500']">
                                        <svg v-if="flag.is_reviewed && !flag.is_dismissed && !(needsLeaderAction(flag) || hasLeaderAction(flag))" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        <svg v-else-if="needsLeaderAction(flag) || hasLeaderAction(flag)" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <svg v-else-if="flag.is_dismissed" class="w-3.5 h-3.5 text-surface-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        <svg v-else class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider" :class="flag.is_reviewed ? (flag.is_dismissed ? 'text-surface-600' : (needsLeaderAction(flag) || hasLeaderAction(flag) ? 'text-red-700' : 'text-emerald-700')) : 'text-amber-700'">
                                        Status Verifikasi Admin
                                    </h4>
                                </div>
                                <div v-if="flag.is_reviewed">
                                    <p class="text-xs font-semibold" :class="flag.is_dismissed ? 'text-surface-600' : (needsLeaderAction(flag) || hasLeaderAction(flag) ? 'text-red-600' : 'text-emerald-700')">
                                        {{ flag.is_dismissed ? '⚠ Anomali ini telah diabaikan oleh Admin Keuangan.' : (needsLeaderAction(flag) || hasLeaderAction(flag) ? '⚠️ Admin Keuangan menandai bahwa anomali ini perlu ditindaklanjuti oleh Pimpinan.' : '✓ Anomali ini telah ditinjau dan dinyatakan aman oleh Admin Keuangan.') }}
                                    </p>
                                    <div v-if="flag.review_note" class="mt-2 p-2.5 rounded-lg bg-white/80 border border-surface-200/60">
                                        <p class="text-[10px] text-surface-500 font-semibold uppercase tracking-wider mb-1">Catatan Admin:</p>
                                        <p class="text-xs text-surface-700 italic leading-relaxed">"{{ flag.review_note }}"</p>
                                    </div>
                                </div>
                                <div v-else>
                                    <p class="text-xs text-amber-700 font-semibold">🚨 Anomali ini belum diverifikasi oleh Admin Keuangan.</p>
                                    <p class="text-[10px] text-amber-600/80 mt-1">Menunggu proses tinjauan atau konfirmasi dari pihak admin.</p>
                                </div>
                            </div>

                            <!-- Leadership Decision / Follow Up Form -->
                            <div v-if="needsLeaderAction(flag)" class="glass-card !bg-white/90 p-5 border border-rose-100 shadow-soft space-y-4 animate-scale-in">
                                <div class="flex items-center gap-3 mb-1">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-amber-50 flex items-center justify-center text-white shadow-soft flex-shrink-0 animate-pulse-soft">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-plum uppercase tracking-wider">Tindak Lanjut Keputusan Pimpinan</h4>
                                        <p class="text-[10px] text-surface-500 mt-0.5">Tentukan persetujuan atas keabsahan transaksi anomali ini</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <button 
                                        type="button" 
                                        @click="isApproved = true"
                                        :class="[
                                            'flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border font-bold text-xs transition-all shadow-sm',
                                            isApproved 
                                                ? 'bg-emerald-500 border-emerald-600 text-white scale-[1.02] shadow-md shadow-emerald-500/20' 
                                                : 'bg-white border-surface-200 text-surface-600 hover:bg-emerald-50 hover:border-emerald-200'
                                        ]"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Setujui Transaksi
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="isApproved = false"
                                        :class="[
                                            'flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border font-bold text-xs transition-all shadow-sm',
                                            !isApproved 
                                                ? 'bg-rose-600 border-rose-700 text-white scale-[1.02] shadow-md shadow-rose-500/20' 
                                                : 'bg-white border-surface-200 text-surface-600 hover:bg-rose-50 hover:border-rose-200'
                                        ]"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Tolak / Investigasi
                                    </button>
                                </div>

                                <!-- Note Textarea -->
                                <div class="space-y-1.5">
                                    <label class="label-text !text-[10px] font-bold uppercase tracking-wider">Catatan Keputusan Pimpinan:</label>
                                    <textarea 
                                        v-model="leaderNote"
                                        rows="2"
                                        class="input-field !text-xs !py-2 !px-3"
                                        placeholder="Masukkan alasan persetujuan atau penolakan transaksi di sini..."
                                    ></textarea>
                                </div>

                                <div class="flex justify-end pt-1">
                                    <button 
                                        @click="submitLeaderAction(flag.id)"
                                        :disabled="isSubmitting"
                                        :class="[
                                            'btn-primary text-xs !py-2 !px-5 font-bold',
                                            isApproved ? '!bg-emerald-600 hover:!bg-emerald-700 !shadow-emerald-500/20' : '!bg-rose-600 hover:!bg-rose-700 !shadow-rose-500/20'
                                        ]"
                                    >
                                        {{ isSubmitting ? 'Memproses...' : 'Kirim Keputusan' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Leadership Action History Display -->
                            <div v-else-if="hasLeaderAction(flag)" class="rounded-xl border p-4 shadow-sm" :class="flag.is_approved_by_leader ? 'bg-emerald-500/10 border-emerald-200/50' : 'bg-rose-500/10 border-rose-200/50'">
                                <div class="flex items-center gap-2 mb-2">
                                    <div :class="['w-6 h-6 rounded-lg flex items-center justify-center', flag.is_approved_by_leader ? 'bg-emerald-500' : 'bg-rose-600']">
                                        <svg v-if="flag.is_approved_by_leader" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <svg v-else class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider" :class="flag.is_approved_by_leader ? 'text-emerald-800' : 'text-rose-800'">
                                        Otorisasi & Keputusan Pimpinan
                                    </h4>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold" :class="flag.is_approved_by_leader ? 'text-emerald-700' : 'text-rose-700'">
                                        {{ flag.is_approved_by_leader 
                                            ? '✓ Transaksi ini telah disetujui & diselesaikan oleh Pimpinan.' 
                                            : '❌ Transaksi ini telah ditolak & ditangguhkan oleh Pimpinan.' }}
                                    </p>
                                    <p class="text-[10px] text-surface-500 mt-0.5">
                                        Ditindaklanjuti pada: {{ formatDateTime(flag.leader_reviewed_at) }}
                                    </p>
                                    <div v-if="flag.leader_note" class="mt-2.5 p-2.5 rounded-lg bg-white/80 border border-surface-200/60">
                                        <p class="text-[10px] text-surface-500 font-semibold uppercase tracking-wider mb-1">Catatan Pimpinan:</p>
                                        <p class="text-xs text-surface-700 italic leading-relaxed">"{{ flag.leader_note }}"</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <div v-if="!anomalies.data?.length" class="glass-card p-12 text-center">
                <svg class="w-14 h-14 text-surface-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                <p class="text-surface-500 font-semibold text-sm">Tidak ada anomali yang ditemukan.</p>
                <p class="text-xs text-surface-400 mt-1">Coba sesuaikan filter tanggal atau rekening untuk memperluas pencarian.</p>
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
    </AppLayout>
</template>

