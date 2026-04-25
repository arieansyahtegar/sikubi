<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, inject } from 'vue';

const props = defineProps({
    anomalies: Object,
    filters: Object,
});

const addToast = inject('addToast');
const page = usePage();

const momMonth = ref(new Date().toISOString().slice(0, 7));
const momResult = ref(page.props.flash?.momResult || null);
const momLoading = ref(false);

function setSeverity(severity) {
    router.get('/anomalies', { severity }, { preserveState: true, preserveScroll: true });
}

function runDetection() {
    router.post('/anomalies/detect', {}, {
        preserveScroll: true,
        onSuccess: () => addToast?.('Deteksi anomali selesai', 'success'),
    });
}

function reviewFlag(id, dismiss = false) {
    router.patch(`/anomalies/${id}`, { dismiss }, { preserveScroll: true });
}

function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v); }
function formatDate(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }); }

function severityLabel(s) {
    return { HIGH: 'Tinggi', MEDIUM: 'Sedang', LOW: 'Rendah' }[s] || s;
}
function severityClass(s) {
    return { HIGH: 'badge-red', MEDIUM: 'badge-yellow', LOW: 'badge-blue' }[s] || 'badge-rose';
}

function runMoM() {
    momLoading.value = true;
    router.post('/anomalies/detect-mom', { month: momMonth.value }, {
        preserveScroll: true,
        onSuccess: (p) => {
            momResult.value = p.props.flash?.momResult || null;
            addToast?.(momResult.value?.message || 'Analisis MoM selesai', 'success');
        },
        onFinish: () => { momLoading.value = false; },
    });
}
</script>

<template>
    <Head title="Deteksi Anomali — SIKUBI" />
    <AppLayout>
        <div class="space-y-6 animate-fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="page-title">Deteksi Anomali</h1>
                    <p class="text-sm text-surface-600 mt-1">Identifikasi transaksi dengan pola tidak biasa</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center bg-cream-200/60 p-1 rounded-xl">
                        <button @click="setSeverity('HIGH')" :class="['px-4 py-1.5 text-xs font-semibold rounded-lg transition-all', filters?.severity === 'HIGH' ? 'bg-white text-red-500 shadow-soft' : 'text-surface-600 hover:text-plum']">Tinggi</button>
                        <button @click="setSeverity('MEDIUM')" :class="['px-4 py-1.5 text-xs font-semibold rounded-lg transition-all', filters?.severity === 'MEDIUM' ? 'bg-white text-amber-500 shadow-soft' : 'text-surface-600 hover:text-plum']">Sedang</button>
                        <button @click="setSeverity('ALL')" :class="['px-4 py-1.5 text-xs font-semibold rounded-lg transition-all', filters?.severity === 'ALL' ? 'bg-white text-plum shadow-soft' : 'text-surface-600 hover:text-plum']">Semua</button>
                    </div>
                    <button @click="runDetection" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                        Jalankan Deteksi
                    </button>
                </div>
            </div>

            <div class="space-y-3">
                <div v-for="flag in anomalies.data" :key="flag.id" class="glass-card p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span :class="severityClass(flag.severity)">{{ severityLabel(flag.severity) }}</span>
                                <span class="text-xs text-surface-500">Skor: {{ (flag.score * 100).toFixed(0) }}%</span>
                            </div>
                            <p class="text-sm font-semibold text-plum truncate">{{ flag.transaction?.description }}</p>
                            <p class="text-xs text-surface-500 mt-0.5">
                                {{ formatDate(flag.transaction?.transaction_date) }}
                                · {{ flag.transaction?.bank_account?.account_alias || flag.transaction?.bank_account?.bank_name }}
                            </p>
                            <p :class="['text-base font-bold mt-1', flag.transaction?.type === 'DEBIT' ? 'text-emerald-600' : 'text-red-500']">
                                {{ formatCurrency(flag.transaction?.amount || 0) }}
                            </p>
                            <p class="text-xs text-surface-600 mt-2 bg-cream-200/50 rounded-lg p-2">{{ flag.reason }}</p>
                        </div>
                        <div v-if="!flag.is_reviewed" class="flex gap-2 flex-shrink-0">
                            <button @click="reviewFlag(flag.id, false)" class="btn-secondary text-xs !py-1.5 !px-3">Sudah Ditinjau</button>
                            <button @click="reviewFlag(flag.id, true)" class="btn-ghost text-xs !py-1.5 !px-3">Abaikan</button>
                        </div>
                        <span v-else class="badge-green">✓ Ditinjau</span>
                    </div>
                </div>
                <div v-if="!anomalies.data?.length" class="glass-card p-12 text-center">
                    <svg class="w-12 h-12 text-surface-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                    <p class="text-surface-500">Belum ada anomali terdeteksi. Klik "Jalankan Deteksi" untuk memulai analisis.</p>
                </div>
            </div>

            <!-- MoM Analysis -->
            <div class="glass-card overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between px-4 sm:px-6 py-4 border-b border-rose-100/40 gap-3">
                    <div>
                        <h3 class="section-title text-sm">📊 Analisis Bulan-ke-Bulan (MoM)</h3>
                        <p class="text-xs text-surface-500 mt-0.5">Bandingkan pengeluaran per kategori antar bulan</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="momMonth" type="month" class="input-field !w-auto text-sm !py-1.5" />
                        <button @click="runMoM" :disabled="momLoading" class="btn-primary text-xs !py-2">
                            {{ momLoading ? 'Menganalisis...' : 'Jalankan Analisis' }}
                        </button>
                    </div>
                </div>
                <div v-if="momResult?.variances?.length" class="p-4 sm:p-6">
                    <p class="text-xs text-surface-500 mb-3">Periode: {{ momResult.month }} vs bulan sebelumnya · {{ momResult.flags_created }} anomali ditemukan</p>
                    <div class="table-container">
                        <table class="data-table">
                            <thead><tr><th>Kategori</th><th class="text-right">Bulan Lalu</th><th class="text-right">Bulan Ini</th><th class="text-right">Perubahan</th><th>Tingkat</th></tr></thead>
                            <tbody>
                                <tr v-for="v in momResult.variances" :key="v.category_id">
                                    <td class="font-medium">{{ v.category_name }}</td>
                                    <td class="text-right text-sm">{{ formatCurrency(v.prev_amount) }}</td>
                                    <td class="text-right text-sm font-semibold text-red-500">{{ formatCurrency(v.curr_amount) }}</td>
                                    <td class="text-right"><span class="text-red-600 font-bold">+{{ v.variance_pct }}%</span></td>
                                    <td><span :class="severityClass(v.severity)">{{ severityLabel(v.severity) }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else-if="momResult" class="p-8 text-center text-surface-500 text-sm">Tidak ada lonjakan pengeluaran signifikan terdeteksi di periode ini.</div>
            </div>
        </div>
    </AppLayout>
</template>
