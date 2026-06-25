<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    accounts: Array,
    transactions: { type: Array, default: null },
    income_breakdown: { type: Array, default: () => [] },
    expense_breakdown: { type: Array, default: () => [] },
    anomalies: { type: Array, default: () => [] },
    summary: { type: Object, default: null },
    filters: Object,
});

const page = usePage();

// Form state
const selectedMonth = ref(props.filters?.month || '');
const selectedYear = ref(props.filters?.year || new Date().getFullYear().toString());
const selectedAccountId = ref(props.filters?.account_id || '');

const months = [
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: currentYear - 2019 }, (_, i) => (currentYear - i).toString());

const hasReport = computed(() => props.transactions !== null && props.summary !== null);

const selectedAccount = computed(() => {
    if (!props.accounts || !selectedAccountId.value) return null;
    return props.accounts.find(acc => String(acc.id) === String(selectedAccountId.value));
});

function showReport() {
    if (!selectedMonth.value || !selectedYear.value) return;
    router.get('/reports/print', {
        month: selectedMonth.value,
        year: selectedYear.value,
        account_id: selectedAccountId.value || undefined,
    }, { preserveState: false });
}

function goBack() {
    selectedMonth.value = '';
    selectedYear.value = new Date().getFullYear().toString();
    selectedAccountId.value = '';
    router.get('/reports/print', {}, { preserveState: false });
}

function printPage() {
    window.print();
}

const isExportingExcel = ref(false);

function downloadExcel() {
    if (!hasReport.value) return;
    isExportingExcel.value = true;
    const params = new URLSearchParams({
        month: selectedMonth.value,
        year: selectedYear.value,
    });
    if (selectedAccountId.value) params.set('account_id', selectedAccountId.value);
    const link = document.createElement('a');
    link.href = '/reports/recap/excel?' + params.toString();
    link.setAttribute('download', '');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => { isExportingExcel.value = false; }, 2000);
}

function getLastDay() {
    const d = new Date(parseInt(selectedYear.value), parseInt(selectedMonth.value), 0);
    return `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function formatCurrency(v) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v);
}

function applyPreset(type) {
    const today = new Date();
    if (type === 'last_month') {
        const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        selectedMonth.value = String(lastMonth.getMonth() + 1);
        selectedYear.value = String(lastMonth.getFullYear());
    } else if (type === 'this_month') {
        selectedMonth.value = String(today.getMonth() + 1);
        selectedYear.value = String(today.getFullYear());
    } else if (type === 'this_year') {
        selectedMonth.value = String(today.getMonth() + 1);
        selectedYear.value = String(today.getFullYear());
    }
}

const reportReadyMessage = computed(() => {
    if (!selectedMonth.value || !selectedYear.value) return '';
    const monthObj = months.find(m => m.value === selectedMonth.value);
    const monthLabel = monthObj ? monthObj.label : '';
    const accLabel = selectedAccountId.value === 'cash' ? 'Transaksi Tunai' 
                   : selectedAccount.value ? (selectedAccount.value.account_alias || selectedAccount.value.bank_name)
                   : 'Semua Rekening';
    return `Laporan siap disusun untuk periode ${monthLabel} ${selectedYear.value} (${accLabel})`;
});
</script>

<template>
    <Head title="Cetak Laporan — SIKUBI" />
    <AppLayout>
        <div class="space-y-6 animate-fade-in">
            <!-- Mode: Selector -->
            <div v-if="!hasReport" class="max-w-3xl mx-auto w-full py-4 sm:py-8 no-print">
                <div class="glass-card overflow-hidden relative">
                    <!-- Decorative Sakura Background Header -->
                    <div class="report-hero-header">
                        <img src="/images/bigenmi_sakura_decoration.png" alt="" class="report-hero-bg" aria-hidden="true" />
                        <div class="report-hero-overlay"></div>
                        <div class="relative z-10 text-center px-6 py-10 sm:py-12">
                            <h1 class="text-xl sm:text-2xl font-display font-bold text-plum mb-2">Cetak & Unduh Laporan Keuangan</h1>
                            <p class="text-xs sm:text-sm text-surface-700 max-w-lg mx-auto leading-relaxed font-medium">
                                Susun rekapitulasi, pantau neraca kas, dan ekspor laporan keuangan PT Bigenmi siap audit.
                            </p>
                            <div class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-rose-50/95 text-[10px] font-bold uppercase tracking-wider text-rose-600 border border-rose-100 shadow-sm mt-4 backdrop-blur-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                Standar Laporan Kepatuhan Internal & Audit
                            </div>
                        </div>
                    </div>

                    <!-- Form Content -->
                    <div class="p-6 sm:p-8 space-y-6">
                        <div class="sr-only">
                            <h2>Konfigurasikan jangka waktu pencetakan dan rekening tujuan</h2>
                        </div>

                        <!-- Quick Presets -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-surface-600">Pilihan Pintas</label>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    @click="applyPreset('last_month')" 
                                    type="button" 
                                    class="btn-secondary text-[11px] !py-1.5 !px-3.5 !rounded-xl transition-all hover:border-rose-300"
                                >
                                    🌙 Bulan Lalu
                                </button>
                                <button 
                                    @click="applyPreset('this_month')" 
                                    type="button" 
                                    class="btn-secondary text-[11px] !py-1.5 !px-3.5 !rounded-xl transition-all hover:border-rose-300"
                                >
                                    📅 Bulan Ini
                                </button>
                                <button 
                                    @click="applyPreset('this_year')" 
                                    type="button" 
                                    class="btn-secondary text-[11px] !py-1.5 !px-3.5 !rounded-xl transition-all hover:border-rose-300"
                                >
                                    📆 Tahun Ini
                                </button>
                            </div>
                        </div>

                        <!-- Year Selector -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-surface-600">Tahun</label>
                            <select v-model="selectedYear" class="input-field !rounded-xl shadow-sm border-surface-200" id="year-selector">
                                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>

                        <!-- Month Grid Pills Selection -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-surface-600">Bulan</label>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2" id="month-selector">
                                <button
                                    v-for="m in months"
                                    :key="m.value"
                                    @click="selectedMonth = m.value"
                                    type="button"
                                    :class="[
                                        'py-2.5 px-3 rounded-xl border text-xs font-semibold text-center transition-all duration-200',
                                        selectedMonth === m.value 
                                            ? 'bg-gradient-to-r from-rose-500 to-rose-600 text-white border-rose-600 shadow-md shadow-rose-500/20 scale-[1.02]' 
                                            : 'bg-white hover:bg-rose-50/30 text-surface-700 border-surface-200 hover:border-rose-200'
                                    ]"
                                >
                                    {{ m.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Account Selector -->
                        <div v-if="accounts?.length" class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-surface-600">Rekening</label>
                            <select v-model="selectedAccountId" class="input-field !rounded-xl shadow-sm border-surface-200" id="account-selector">
                                <option value="">Semua Rekening</option>
                                <option value="cash">Transaksi Tunai</option>
                                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.account_alias || acc.bank_name }}</option>
                            </select>
                        </div>

                        <!-- Live Feedback Badge -->
                        <Transition name="fade">
                            <div v-if="reportReadyMessage" class="rounded-xl bg-emerald-50/40 p-3 border border-emerald-100/50 flex items-center gap-2.5 text-xs text-emerald-950 font-semibold animate-scale-in">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0 animate-pulse"></span>
                                {{ reportReadyMessage }}
                            </div>
                        </Transition>

                        <!-- Submit button -->
                        <button
                            @click="showReport"
                            :disabled="!selectedMonth || !selectedYear"
                            class="btn-primary w-full !py-3 text-sm !rounded-xl gap-2 justify-center flex items-center shadow-soft"
                            id="btn-show-report"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            Susun Laporan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mode: Report Preview -->
            <div v-else>
                <!-- Action buttons (hidden on print) -->
                <div class="flex flex-wrap items-center justify-between gap-3 no-print mb-4">
                    <div class="flex items-center gap-2">
                        <button @click="goBack" class="btn-secondary text-xs gap-1.5" id="btn-back">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                            Kembali
                        </button>

                        <button @click="downloadExcel" :disabled="isExportingExcel" class="btn-secondary text-xs gap-1.5 !text-emerald-700 hover:!text-emerald-800" id="btn-excel">
                            <svg v-if="isExportingExcel" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 !text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            {{ isExportingExcel ? 'Excel...' : 'Excel' }}
                        </button>
                    </div>
                    <button @click="printPage" class="btn-primary text-xs gap-1.5" id="btn-print">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m0 0a48.03 48.03 0 0110.5 0m-10.5 0V5.625c0-.621.504-1.125 1.125-1.125h8.25c.621 0 1.125.504 1.125 1.125v3.026" />
                        </svg>
                        Cetak Halaman Ini
                    </button>
                </div>

                <!-- Printable Content -->
                <div class="print-content" id="report-content">
                    <!-- Report Header -->
                    <div class="report-header">
                        <div class="header-top-row">
                            <img src="/images/bigenmi-logo.png" alt="Bigenmi" class="report-logo" />
                            <div class="report-company">
                                <h2>SIKUBI</h2>
                                <p>Sistem Keuangan PT Bigenmi Gemilang Indonesia</p>
                            </div>
                        </div>
                        <h1 class="report-title">
                            Laporan Pendapatan &amp; Pengeluaran<br />
                            <span class="report-subtitle">Periode Bulan {{ summary.month_label }}</span>
                        </h1>
                        <div class="report-bank-meta">
                            <span v-if="selectedAccountId === 'cash'" class="bank-info-badge">
                                <svg class="w-3.5 h-3.5 inline-block align-text-top mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.33l-7.5-5-7.5 5V21" />
                                </svg>
                                Rekening: Transaksi Tunai
                            </span>
                            <span v-else-if="selectedAccount" class="bank-info-badge">
                                <svg class="w-3.5 h-3.5 inline-block align-text-top mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.33l-7.5-5-7.5 5V21" />
                                </svg>
                                Rekening: {{ selectedAccount.bank_name }} — {{ selectedAccount.account_number }} <span v-if="selectedAccount.account_alias">({{ selectedAccount.account_alias }})</span>
                            </span>
                            <span v-else class="bank-info-badge">
                                <svg class="w-3.5 h-3.5 inline-block align-text-top mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.33l-7.5-5-7.5 5V21" />
                                </svg>
                                Rekening: Semua Rekening
                            </span>
                        </div>
                    </div>

                    <!-- Dashboard Metrics (Page 1) -->
                    <div class="recap-cards-grid">
                        <div class="recap-card income-card">
                            <div class="card-title">TOTAL PENDAPATAN</div>
                            <div class="card-value">{{ formatCurrency(summary.total_debit) }}</div>
                            <div class="card-subtitle">{{ summary.total_debit_count }} Transaksi Masuk</div>
                        </div>
                        <div class="recap-card expense-card">
                            <div class="card-title">TOTAL PENGELUARAN</div>
                            <div class="card-value">{{ formatCurrency(summary.total_credit) }}</div>
                            <div class="card-subtitle">{{ summary.total_credit_count }} Transaksi Keluar</div>
                        </div>
                        <div class="recap-card balance-card" :class="summary.balance >= 0 ? 'balance-positive' : 'balance-negative'">
                            <div class="card-title">SISA SALDO BERSIH (NET)</div>
                            <div class="card-value">{{ formatCurrency(summary.balance) }}</div>
                            <div class="card-subtitle">Arus Kas Bersih Bulan Ini</div>
                        </div>
                    </div>

                    <!-- Category Share Progress Columns (The Visual Graphics Equivalent) -->
                    <div class="breakdown-section">
                        <h3 class="section-title">Visualisasi Kontribusi Kategori &amp; Kegiatan</h3>
                        <div class="breakdown-columns">
                            <!-- Income breakdown -->
                            <div class="breakdown-col">
                                <h4 class="col-title title-income">Pendapatan Berdasarkan Kategori</h4>
                                <div v-if="!income_breakdown.length" class="empty-breakdown">Tidak ada data pendapatan.</div>
                                <div v-else class="breakdown-list">
                                    <div v-for="item in income_breakdown" :key="item.name" class="breakdown-item">
                                        <div class="item-header">
                                            <span class="item-name">{{ item.name }}</span>
                                            <span class="item-values">
                                                <span class="item-percent">{{ Math.round((item.amount / (summary.total_debit || 1)) * 100) }}%</span>
                                                <span class="item-amount">({{ formatCurrency(item.amount) }})</span>
                                            </span>
                                        </div>
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill fill-income" :style="{ width: Math.round((item.amount / (summary.total_debit || 1)) * 100) + '%', backgroundColor: item.color }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Expense breakdown -->
                            <div class="breakdown-col">
                                <h4 class="col-title title-expense">Pengeluaran Berdasarkan Kategori</h4>
                                <div v-if="!expense_breakdown.length" class="empty-breakdown">Tidak ada data pengeluaran.</div>
                                <div v-else class="breakdown-list">
                                    <div v-for="item in expense_breakdown" :key="item.name" class="breakdown-item">
                                        <div class="item-header">
                                            <span class="item-name">{{ item.name }}</span>
                                            <span class="item-values">
                                                <span class="item-percent">{{ Math.round((item.amount / (summary.total_credit || 1)) * 100) }}%</span>
                                                <span class="item-amount">({{ formatCurrency(item.amount) }})</span>
                                            </span>
                                        </div>
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill fill-expense" :style="{ width: Math.round((item.amount / (summary.total_credit || 1)) * 100) + '%', backgroundColor: item.color }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Anomaly Detection Report -->
                    <div class="anomaly-report-section">
                        <h3 class="section-title">Hasil Deteksi &amp; Analisis Anomali Sistem</h3>
                        
                        <div v-if="!anomalies.length" class="anomaly-safe-card">
                            <span class="safe-icon">✓</span>
                            <div class="safe-text-wrapper">
                                <strong>Sistem Keuangan Aman (100% Bersih)</strong>
                                <p>Tidak ditemukan adanya transaksi anomali, transfer mencurigakan, atau lonjakan nominal yang tidak wajar pada periode bulan ini.</p>
                            </div>
                        </div>
                        
                        <div v-else class="anomaly-danger-section">
                            <div class="anomaly-warning-card">
                                <span class="warning-icon">⚠</span>
                                <div class="warning-text-wrapper">
                                    <strong>Ditemukan {{ anomalies.length }} Bendera Anomali Keuangan (Telah Ditinjau &amp; Kroscek oleh Admin)</strong>
                                    <p>Terdapat beberapa mutasi yang memerlukan peninjauan khusus dan telah ditinjau serta diverifikasi langsung oleh Admin Keuangan.</p>
                                </div>
                            </div>
                            
                            <div class="report-table-wrapper shadow-sm">
                                <table class="anomaly-print-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">Tanggal</th>
                                            <th style="width: 28%;">Transaksi &amp; Rekening</th>
                                            <th style="width: 30%;">Analisis Deteksi Sistem</th>
                                            <th style="width: 30%;">Status &amp; Keterangan Admin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="flag in anomalies" :key="flag.id">
                                            <td class="td-date">{{ flag.date }}</td>
                                            <td>
                                                <div class="font-bold text-plum">{{ flag.description }}</div>
                                                <div class="text-xs text-surface-500 mt-0.5">Rekening: {{ flag.account }}</div>
                                                <div class="text-xs font-extrabold text-rose-gold mt-0.5">Nominal: {{ formatCurrency(flag.amount) }}</div>
                                            </td>
                                            <td>
                                                <span class="anomaly-badge badge-high mb-1 inline-block">HIGH THREAT</span>
                                                <div class="text-xs text-rose-950 font-medium leading-relaxed">{{ flag.reason }}</div>
                                            </td>
                                            <td>
                                                <div v-if="flag.is_reviewed" class="flex flex-col gap-1">
                                                    <span class="status-badge badge-verified inline-block w-fit">✓ TERVERIFIKASI AMAN</span>
                                                    <div class="text-xs font-semibold text-emerald-900 leading-normal" v-if="flag.review_note">
                                                        Alasan: <span class="italic font-normal">"{{ flag.review_note }}"</span>
                                                    </div>
                                                    <div class="text-[10px] text-surface-500 italic" v-else>Disetujui tanpa catatan khusus.</div>
                                                </div>
                                                <div v-else class="flex flex-col gap-1">
                                                    <span class="status-badge badge-pending inline-block w-fit">🚨 BELUM DITINJAU</span>
                                                    <div class="text-[10px] text-rose-800 italic">Memerlukan verifikasi nota oleh Admin Keuangan.</div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                    <!-- Footer -->
                    <div class="report-footer">
                        <p>&copy; {{ new Date().getFullYear() }} SIKUBI — Sistem Keuangan PT Bigenmi Gemilang Indonesia</p>
                    </div>
                </div>

                <!-- Branded Footer Logo (hidden on print) -->
                <div class="flex flex-col items-center justify-center mt-8 mb-4 no-print opacity-60">
                    <img src="/images/bigenmi-logo.png" alt="Bigenmi" class="w-10 h-10 object-contain mb-1.5 filter grayscale hover:grayscale-0 transition-all duration-300" />
                    <p class="text-[10px] text-surface-400 font-sans tracking-wide uppercase">SIKUBI Financial Intelligence</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ═══════════════════════════════════════
   SELECTOR HERO HEADER (Integrated Illustration)
   ═══════════════════════════════════════ */

.report-hero-header {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #FFF5F7 0%, #FEF0F2 30%, #FDF2EC 70%, #FFFAF5 100%);
    border-bottom: 1px solid rgba(183, 110, 121, 0.1);
}

.report-hero-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    opacity: 0.10;
    pointer-events: none;
    filter: saturate(0.85);
    mix-blend-mode: multiply;
}

.report-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        180deg,
        rgba(255, 245, 247, 0.3) 0%,
        rgba(255, 240, 242, 0.5) 50%,
        rgba(255, 245, 247, 0.85) 100%
    );
    pointer-events: none;
}

/* ═══════════════════════════════════════
   REPORT STYLES (screen + print)
   ═══════════════════════════════════════ */

.print-content {
    background: white;
    border: 1px solid rgba(183, 110, 121, 0.15);
    border-radius: 16px;
    padding: 2rem 2.5rem;
    box-shadow: 0 2px 8px rgba(183, 110, 121, 0.05);
}

/* Report Header */
.report-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #7A2D58;
}

.header-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.report-logo {
    height: 38px;
    width: auto;
    object-fit: contain;
}

.report-company {
    text-align: right;
}

.report-company h2 {
    font-size: 1.5rem;
    font-weight: 800;
    color: #7A2D58;
    letter-spacing: 1px;
    margin: 0;
    line-height: 1.2;
}

.report-company p {
    font-size: 0.75rem;
    color: #8B5E6B;
    margin: 2px 0 0 0;
    font-weight: 500;
}

.report-title {
    text-align: center;
    font-size: 1.25rem;
    font-weight: 700;
    color: #7A2D58;
    line-height: 1.4;
    margin-top: 1rem;
}

.report-subtitle {
    font-size: 0.95rem;
    font-weight: 500;
    color: #8B5E6B;
    display: inline-block;
    margin-top: 4px;
}

.report-bank-meta {
    text-align: center;
    margin-top: 0.75rem;
}

.bank-info-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.85rem;
    background-color: rgba(122, 45, 88, 0.05);
    border: 1px solid rgba(122, 45, 88, 0.12);
    border-radius: 9999px;
    font-size: 0.78rem;
    font-weight: 600;
    color: #7A2D58;
}

/* Transaction Table */
.report-table-wrapper {
    overflow-x: auto;
    margin-bottom: 1.5rem;
    border-radius: 8px;
    border: 1px solid #E8C4D1;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
    border: 1px solid #E8C4D1;
}

.report-table thead {
    background: #7A2D58;
    color: white;
}

.report-table th {
    padding: 10px 14px;
    font-weight: 600;
    text-align: left;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    border: 1px solid #E8C4D1;
}

.th-no { width: 50px; text-align: center; }
.th-date { width: 110px; }
.th-type { width: 110px; text-align: center; }
.th-amount { width: 150px; text-align: right; }

.report-table td {
    padding: 9px 14px;
    border: 1px solid #E8C4D1;
    color: #4A2035;
}

.report-table tbody tr:hover {
    background: rgba(183, 110, 121, 0.03);
}

.td-no { text-align: center; color: #8B5E6B; }
.td-date { white-space: nowrap; }
.td-desc { max-width: 300px; }
.td-type { text-align: center; font-weight: 600; font-size: 0.78rem; }
.td-amount { text-align: right; font-weight: 700; white-space: nowrap; }

.type-income { color: #059669; }
.type-expense { color: #DC2626; }
.amount-income { color: #059669; }
.amount-expense { color: #DC2626; }
.amount-balance { font-weight: 800; color: #7A2D58; }

.empty-row {
    text-align: center;
    padding: 2rem !important;
    color: #8B5E6B;
    font-style: italic;
}

/* Recap Cards Grid */
.recap-cards-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

@media (min-width: 640px) {
    .recap-cards-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

.recap-card {
    border-radius: 12px;
    padding: 1.2rem;
    border: 1px solid #E8C4D1;
    background: linear-gradient(135deg, #ffffff, rgba(183, 110, 121, 0.02));
    box-shadow: 0 1px 3px rgba(183, 110, 121, 0.05);
}

.income-card {
    border-left: 4px solid #059669;
}

.expense-card {
    border-left: 4px solid #DC2626;
}

.balance-card {
    border-left: 4px solid #7A2D58;
}

.balance-positive {
    background: linear-gradient(135deg, #ffffff, rgba(5, 150, 105, 0.03));
    border-left-color: #059669;
}

.balance-negative {
    background: linear-gradient(135deg, #ffffff, rgba(220, 38, 38, 0.03));
    border-left-color: #DC2626;
}

.card-title {
    font-size: 0.7rem;
    font-weight: 700;
    color: #8B5E6B;
    letter-spacing: 0.8px;
    margin-bottom: 0.4rem;
}

.card-value {
    font-size: 1.35rem;
    font-weight: 800;
    color: #4A2035;
    line-height: 1.2;
    white-space: nowrap;
}

.card-subtitle {
    font-size: 0.72rem;
    color: #8B5E6B;
    margin-top: 0.3rem;
    font-weight: 500;
}

/* Category Breakdown Progress Columns */
.breakdown-section {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #7A2D58;
    margin-bottom: 1rem;
    border-left: 3px solid #7A2D58;
    padding-left: 8px;
    line-height: 1.2;
}

.breakdown-columns {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 640px) {
    .breakdown-columns {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.breakdown-col {
    background: rgba(183, 110, 121, 0.02);
    border: 1px solid rgba(183, 110, 121, 0.15);
    border-radius: 12px;
    padding: 1.2rem;
}

.col-title {
    font-size: 0.85rem;
    font-weight: 700;
    margin-bottom: 1rem;
    padding-bottom: 0.4rem;
    border-bottom: 1px dashed rgba(183, 110, 121, 0.15);
}

.title-income { color: #059669; }
.title-expense { color: #DC2626; }

.empty-breakdown {
    text-align: center;
    padding: 1.5rem 0;
    font-size: 0.8rem;
    color: #8B5E6B;
    font-style: italic;
}

.breakdown-list {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.breakdown-item {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.item-header {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    font-weight: 600;
}

.item-name {
    color: #4A2035;
}

.item-values {
    color: #8B5E6B;
}

.item-percent {
    font-weight: 700;
    color: #4A2035;
    margin-right: 4px;
}

.progress-bar-bg {
    height: 8px;
    background-color: rgba(183, 110, 121, 0.08);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

/* Anomaly Section */
.anomaly-report-section {
    margin-bottom: 2rem;
}

.anomaly-safe-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(5, 150, 105, 0.03);
    border: 1px solid rgba(5, 150, 105, 0.15);
    border-radius: 12px;
    padding: 1rem 1.2rem;
}

.safe-icon {
    font-size: 1.35rem;
    color: #059669;
    font-weight: 800;
}

.safe-text-wrapper strong {
    font-size: 0.85rem;
    color: #059669;
    display: block;
}

.safe-text-wrapper p {
    font-size: 0.75rem;
    color: #047857;
    margin-top: 2px;
}

.anomaly-warning-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(220, 38, 38, 0.03);
    border: 1px solid rgba(220, 38, 38, 0.15);
    border-radius: 12px;
    padding: 1rem 1.2rem;
    margin-bottom: 1rem;
}

.warning-icon {
    font-size: 1.35rem;
    color: #DC2626;
    font-weight: 800;
}

.warning-text-wrapper strong {
    font-size: 0.85rem;
    color: #DC2626;
    display: block;
}

.warning-text-wrapper p {
    font-size: 0.75rem;
    color: #B91C1C;
    margin-top: 2px;
}

.anomaly-print-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
    border: 1px solid #E8C4D1;
    border-radius: 8px;
    overflow: hidden;
}

.anomaly-print-table th {
    padding: 8px 12px;
    background: rgba(220, 38, 38, 0.05);
    color: #B91C1C;
    font-weight: 700;
    text-align: left;
    border: 1px solid #E8C4D1;
}

.anomaly-print-table td {
    padding: 8px 12px;
    border: 1px solid #E8C4D1;
    color: #4A2035;
}

.anomaly-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 800;
    text-align: center;
}

.badge-high {
    background-color: rgba(220, 38, 38, 0.1);
    color: #DC2626;
}

.badge-medium {
    background-color: rgba(217, 119, 6, 0.1);
    color: #D97706;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-verified {
    background-color: rgba(5, 150, 105, 0.1);
    color: #059669;
}

.badge-pending {
    background-color: rgba(220, 38, 38, 0.1);
    color: #DC2626;
}

.page-break-title {
    margin-top: 2.5rem;
}

/* Footer */
.report-footer {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid #E8C4D1;
    color: #8B5E6B;
    font-size: 0.75rem;
    margin-top: 2rem;
}

/* ═══════════════════════════════════════
   PRINT STYLES
   ═══════════════════════════════════════ */

@media print {
    /* Hide layout chrome elements */
    aside,
    header,
    .no-print {
        display: none !important;
    }

    /* Reset layout parent heights and overflows to print the entire page */
    html,
    body,
    #app,
    #app > div,
    .flex-1,
    main {
        overflow: visible !important;
        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #ffffff !important;
    }

    .print-content {
        display: block !important;
        border: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        margin: 0 !important;
        background: transparent !important;
        width: 100% !important;
    }

    .report-table-wrapper {
        border: 1px solid #333;
        overflow: visible !important;
    }

    .report-table {
        page-break-inside: auto;
        border: 1px solid #333333 !important;
        border-collapse: collapse !important;
    }

    .report-table thead {
        display: table-header-group !important;
        background: #333333 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .report-table th {
        color: white !important;
        border: 1px solid #333333 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .report-table td {
        border: 1px solid #333333 !important;
    }

    .report-table tr {
        page-break-inside: avoid !important;
        page-break-after: auto !important;
    }

    .detailed-ledger-section {
        page-break-before: always !important;
    }

    .progress-bar-bg {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .progress-bar-fill {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .bank-info-badge {
        background-color: rgba(122, 45, 88, 0.05) !important;
        border: 1px solid rgba(122, 45, 88, 0.15) !important;
        color: #7A2D58 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .anomaly-safe-card {
        background: #f0fdf4 !important;
        border: 1px solid #bbf7d0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .anomaly-warning-card {
        background: #fef2f2 !important;
        border: 1px solid #fecaca !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .anomaly-badge {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .badge-high {
        background-color: #fee2e2 !important;
        color: #dc2626 !important;
    }

    .badge-medium {
        background-color: #fef3c7 !important;
        color: #d97706 !important;
    }

    .status-badge {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .badge-verified {
        background-color: #d1fae5 !important;
        color: #059669 !important;
    }

    .badge-pending {
        background-color: #fee2e2 !important;
        color: #dc2626 !important;
    }

    .type-income, .amount-income {
        color: #059669 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .type-expense, .amount-expense {
        color: #DC2626 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .report-footer {
        page-break-inside: avoid;
    }
}
</style>

<style>
@media print {
    /* Eliminate browser default headers and footers (date, title, URL) */
    @page {
        size: A4 portrait;
        margin: 0 !important;
    }

    /* Globally hide layout chrome, sidebars, headers, and navigation bars */
    aside,
    header,
    nav,
    footer,
    .no-print {
        display: none !important;
    }

    /* Reset overflow and heights on root wrappers to avoid print clipping */
    html,
    body,
    #app,
    #app > div,
    .flex-1,
    main {
        overflow: visible !important;
        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #ffffff !important;
    }

    /* Re-inject custom page margins on the print container */
    .print-content {
        margin: 0 !important;
        padding: 12mm 15mm 12mm 15mm !important;
        box-sizing: border-box !important;
        width: 100% !important;
    }
}

/* Fade Transition */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1), transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: scale(0.98) translateY(-4px);
}
</style>
