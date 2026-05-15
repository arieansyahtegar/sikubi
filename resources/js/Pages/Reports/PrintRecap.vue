<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import RoverAnimation from '@/Components/RoverAnimation.vue';

const props = defineProps({
    accounts: Array,
    transactions: { type: Array, default: null },
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

const isExporting = ref(false);

function downloadCsv() {
    if (!hasReport.value) return;
    isExporting.value = true;
    const params = new URLSearchParams({
        date_from: `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-01`,
        date_to: getLastDay(),
    });
    if (selectedAccountId.value) params.set('account_id', selectedAccountId.value);
    const link = document.createElement('a');
    link.href = '/reports/recap?' + params.toString();
    link.setAttribute('download', '');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => { isExporting.value = false; }, 2000);
}

function getLastDay() {
    const d = new Date(parseInt(selectedYear.value), parseInt(selectedMonth.value), 0);
    return `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function formatCurrency(v) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v);
}
</script>

<template>
    <Head title="Cetak Laporan — SIKUBI" />
    <AppLayout>
        <div class="space-y-6 animate-fade-in">
            <!-- Mode: Selector -->
            <div v-if="!hasReport" class="flex flex-col items-center justify-center min-h-[65vh]">
                <!-- Rover -->
                <div class="mb-6 no-print">
                    <RoverAnimation :size="220" />
                </div>

                <div class="glass-card p-6 sm:p-8 max-w-lg w-full no-print">
                    <div class="text-center mb-6">
                        <h1 class="page-title text-xl sm:text-2xl">Cetak Laporan Keuangan</h1>
                        <p class="text-xs sm:text-sm text-surface-600 mt-1">Pilih bulan dan tahun untuk melihat rekap transaksi</p>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label-text">Bulan</label>
                                <select v-model="selectedMonth" class="input-field" id="month-selector">
                                    <option value="" disabled>Pilih Bulan</option>
                                    <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="label-text">Tahun</label>
                                <select v-model="selectedYear" class="input-field" id="year-selector">
                                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="accounts?.length">
                            <label class="label-text">Rekening (Opsional)</label>
                            <select v-model="selectedAccountId" class="input-field" id="account-selector">
                                <option value="">Semua Rekening</option>
                                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.account_alias || acc.bank_name }}</option>
                            </select>
                        </div>

                        <button
                            @click="showReport"
                            :disabled="!selectedMonth || !selectedYear"
                            class="btn-primary w-full !py-3 text-sm"
                            id="btn-show-report"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            Tampilkan Laporan
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
                        <button @click="downloadCsv" :disabled="isExporting" class="btn-secondary text-xs gap-1.5" id="btn-csv">
                            <svg v-if="isExporting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12M12 16.5V3" />
                            </svg>
                            {{ isExporting ? 'Memproses...' : 'Download CSV' }}
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
                        <div class="report-logo-section">
                            <img src="/images/bigenmi-logo.png" alt="Bigenmi" class="report-logo" />
                            <div class="report-company">
                                <h2>SIKUBI</h2>
                                <p>Sistem Keuangan PT Bigenmi Gemilang Indonesia</p>
                            </div>
                        </div>
                        <h1 class="report-title">
                            Laporan Pendapatan &amp; Pengeluaran<br />
                            Pada Bulan {{ summary.month_label }}
                        </h1>
                    </div>

                    <!-- Transaction Table -->
                    <div class="report-table-wrapper">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th class="th-no">No</th>
                                    <th class="th-date">Tanggal</th>
                                    <th class="th-desc">Deskripsi</th>
                                    <th class="th-type">Jenis</th>
                                    <th class="th-amount">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!transactions.length">
                                    <td colspan="5" class="empty-row">Tidak ada transaksi untuk bulan ini.</td>
                                </tr>
                                <tr v-for="tx in transactions" :key="tx.no">
                                    <td class="td-no">{{ tx.no }}</td>
                                    <td class="td-date">{{ tx.date }}</td>
                                    <td class="td-desc">{{ tx.description }}</td>
                                    <td :class="['td-type', tx.type_raw === 'DEBIT' ? 'type-income' : 'type-expense']">
                                        {{ tx.type }}
                                    </td>
                                    <td :class="['td-amount', tx.type_raw === 'DEBIT' ? 'amount-income' : 'amount-expense']">
                                        {{ formatCurrency(tx.amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary -->
                    <div class="report-summary">
                        <h3 class="summary-title">
                            Total Pendapatan &amp; Pengeluaran Pada Bulan {{ summary.month_label }}
                        </h3>
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th>Pendapatan</th>
                                    <th>Pengeluaran</th>
                                    <th>Total Jumlah Sisa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ summary.month_label }}</td>
                                    <td class="amount-income">{{ formatCurrency(summary.total_debit) }}</td>
                                    <td class="amount-expense">{{ formatCurrency(summary.total_credit) }}</td>
                                    <td class="amount-balance">{{ formatCurrency(summary.balance) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer -->
                    <div class="report-footer">
                        <p>&copy; {{ new Date().getFullYear() }} SIKUBI — Sistem Keuangan PT Bigenmi Gemilang Indonesia</p>
                    </div>
                </div>

                <!-- Rover at bottom (hidden on print) -->
                <div class="flex justify-center mt-6 no-print">
                    <RoverAnimation :size="140" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
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
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #7A2D58;
}

.report-logo-section {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 1.2rem;
}

.report-logo {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.report-company h2 {
    font-size: 1.25rem;
    font-weight: 800;
    color: #7A2D58;
    letter-spacing: 0.5px;
    line-height: 1.2;
}

.report-company p {
    font-size: 0.7rem;
    color: #8B5E6B;
    margin-top: 1px;
}

.report-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #7A2D58;
    line-height: 1.5;
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
}

.th-no { width: 50px; text-align: center; }
.th-date { width: 110px; }
.th-type { width: 110px; text-align: center; }
.th-amount { width: 150px; text-align: right; }

.report-table td {
    padding: 9px 14px;
    border-bottom: 1px solid #F3E8EE;
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

/* Summary Section */
.report-summary {
    background: linear-gradient(135deg, rgba(183, 110, 121, 0.04), rgba(201, 169, 110, 0.03));
    border: 1px solid #E8C4D1;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.summary-title {
    text-align: center;
    font-size: 0.95rem;
    font-weight: 700;
    color: #7A2D58;
    margin-bottom: 1rem;
}

.summary-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.summary-table th {
    padding: 10px 14px;
    background: rgba(183, 110, 121, 0.08);
    color: #7A2D58;
    font-weight: 600;
    text-align: center;
    border: 1px solid #E8C4D1;
    font-size: 0.78rem;
}

.summary-table td {
    padding: 10px 14px;
    text-align: center;
    border: 1px solid #E8C4D1;
    font-weight: 600;
}

/* Footer */
.report-footer {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid #E8C4D1;
    color: #8B5E6B;
    font-size: 0.75rem;
}

/* ═══════════════════════════════════════
   PRINT STYLES
   ═══════════════════════════════════════ */

@media print {
    .no-print {
        display: none !important;
    }

    .print-content {
        border: none;
        border-radius: 0;
        padding: 0;
        box-shadow: none;
        margin: 0;
    }

    .report-table-wrapper {
        border: 1px solid #333;
    }

    .report-table thead {
        background: #333 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .report-table th {
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .report-summary {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .summary-table th {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
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
}
</style>
