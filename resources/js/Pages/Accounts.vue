<script setup>
import { ref, inject, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    accounts: Array,
    filters: Object
});

const addToast = inject('addToast');
const showForm = ref(false);
const form = useForm({ bank_name: '', account_number: '', account_alias: '', currency: 'IDR' });

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

// Generate timeline months (last 12 months from current date)
const timelineMonths = computed(() => {
    const list = [];
    const monthsName = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const now = new Date();
    for (let i = 0; i < 12; i++) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        list.push({
            label: `${monthsName[d.getMonth()]} ${d.getFullYear()}`,
            shortLabel: `${monthsName[d.getMonth()].substring(0, 3)} ${d.getFullYear()}`,
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

// Total transaction count for the currently active filter
const totalTransactions = computed(() => {
    if (!props.accounts) return 0;
    return props.accounts.reduce((sum, acc) => sum + (acc.transactions_count || 0), 0);
});

function applyFilters() {
    router.get('/accounts', {
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

watch(() => props.filters, (f) => {
    dateFrom.value = f?.date_from || '';
    dateTo.value = f?.date_to || '';
}, { deep: true });

function viewTransactions(acc) {
    router.visit('/transactions', {
        data: {
            account_id: acc.id,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        preserveState: false
    });
}

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

            <!-- Add Account Form -->
            <Transition name="slide-up">
                <div v-if="showForm" class="glass-card p-6">
                    <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-rose-100/40">
                        <div class="w-8 h-8 rounded-lg bg-gradient-rose flex items-center justify-center text-white">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-plum">Tambah Rekening Bank</h3>
                            <p class="text-xs text-surface-500">Hubungkan rekening koran bank baru ke sistem</p>
                        </div>
                    </div>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <div><label class="label-text">Nama Bank</label><input v-model="form.bank_name" class="input-field" placeholder="BCA" required /></div>
                        <div><label class="label-text">No. Rekening</label><input v-model="form.account_number" class="input-field" placeholder="1234567890" required /></div>
                        <div><label class="label-text">Alias Rekening</label><input v-model="form.account_alias" class="input-field" placeholder="BCA Utama" /></div>
                        <div class="sm:col-span-2 md:col-span-3 flex justify-end gap-2 pt-3 mt-1 border-t border-rose-100/30">
                            <button type="button" @click="showForm = false" class="btn-ghost text-xs">Batal</button>
                            <button type="submit" :disabled="form.processing" class="btn-primary text-xs">Simpan Rekening</button>
                        </div>
                    </form>
                </div>
            </Transition>

            <!-- Timeline Month Filter -->
            <div class="glass-card p-4 sm:p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-gradient-rose flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                        </div>
                        <p class="text-xs font-bold text-plum uppercase tracking-wider">Filter Periode</p>
                    </div>
                    <span v-if="activeTimelineKey" class="text-[11px] text-surface-500 font-medium">
                        Total: <strong class="text-plum">{{ totalTransactions }}</strong> transaksi
                    </span>
                </div>
                <div class="flex items-center gap-1.5 overflow-x-auto pb-2 scrollbar-thin">
                    <button
                        @click="selectTimeline('')"
                        :class="[
                            'timeline-btn',
                            !activeTimelineKey
                                ? 'timeline-btn-active'
                                : 'timeline-btn-inactive'
                        ]"
                    >
                        <span>Semua</span>
                        <span v-if="!activeTimelineKey" class="timeline-badge">{{ totalTransactions }}</span>
                    </button>
                    <button
                        v-for="month in timelineMonths" :key="month.key"
                        @click="selectTimeline(month)"
                        :class="[
                            'timeline-btn',
                            activeTimelineKey === month.key
                                ? 'timeline-btn-active'
                                : 'timeline-btn-inactive'
                        ]"
                    >
                        <span class="hidden sm:inline">{{ month.label }}</span>
                        <span class="sm:hidden">{{ month.shortLabel }}</span>
                        <span v-if="activeTimelineKey === month.key" class="timeline-badge">{{ totalTransactions }}</span>
                    </button>
                </div>
            </div>

            <!-- Bank Account Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="(acc, index) in accounts" :key="acc.id"
                    @click="viewTransactions(acc)"
                    class="glass-card-hover p-5 border-2 border-transparent hover:border-rose-gold/40 animate-scale-in cursor-pointer hover:shadow-card-hover transition-all duration-300"
                    :style="{ 'animation-delay': `${index * 80}ms`, 'animation-fill-mode': 'both' }"
                >
                    <div class="flex items-start justify-between">
                        <div class="w-10 h-10 rounded-xl bg-gradient-rose flex items-center justify-center text-white font-bold text-xs tracking-wider flex-shrink-0">
                            {{ acc.bank_name.substring(0, 3).toUpperCase() }}
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
                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-rose-100/40">
                        <div class="w-6 h-6 rounded-md bg-rose-50 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-plum leading-tight">{{ acc.transactions_count || 0 }}</p>
                            <p class="text-[10px] text-surface-500 leading-tight">
                                {{ activeTimelineKey ? 'transaksi bulan ini' : 'total transaksi' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!accounts?.length" class="glass-card p-12 text-center">
                <svg class="w-16 h-16 text-surface-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                </svg>
                <p class="font-semibold text-surface-600 text-lg">Belum ada rekening bank</p>
                <p class="text-sm text-surface-500 mt-1">Klik tombol "+ Tambah" untuk menambahkan rekening bank pertama.</p>
            </div>
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
            
            <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white/95 backdrop-blur-md p-6 text-left shadow-2xl transition-all border border-white/60 animate-scale-in">
                <!-- Modal Header -->
                <div class="flex items-start justify-between pb-4 border-b border-rose-100/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-rose flex items-center justify-center text-white shadow-soft flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-plum">Ubah Rekening Bank</h3>
                            <p class="text-xs text-surface-500 mt-0.5">Ubah nama, nomor, dan alias rekening bank</p>
                        </div>
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
                        <label class="label-text">Nama Bank</label>
                        <input v-model="editForm.bank_name" class="input-field" placeholder="BCA" required />
                    </div>
                    <div>
                        <label class="label-text">No. Rekening</label>
                        <input v-model="editForm.account_number" class="input-field" placeholder="1234567890" required />
                    </div>
                    <div>
                        <label class="label-text">Alias Rekening</label>
                        <input v-model="editForm.account_alias" class="input-field" placeholder="BCA Utama" />
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

.timeline-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.875rem;
    font-size: 0.6875rem;
    font-weight: 600;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    white-space: nowrap;
    border: 1px solid transparent;
    cursor: pointer;
}

.timeline-btn-active {
    background: linear-gradient(135deg, #6d2b4f 0%, #c2185b 100%);
    color: #fff;
    border-color: #c2185b;
    box-shadow: 0 2px 8px rgba(194, 24, 91, 0.25);
}

.timeline-btn-inactive {
    background: #fff;
    color: #64748b;
    border-color: #e2e8f0;
}

.timeline-btn-inactive:hover {
    border-color: #c2185b40;
    color: #c2185b;
    background: #fdf2f8;
}

.timeline-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    padding: 0 0.375rem;
    height: 1.125rem;
    font-size: 0.625rem;
    font-weight: 700;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(4px);
}

.scrollbar-thin {
    scrollbar-width: thin;
    scrollbar-color: #c2185b40 transparent;
}
.scrollbar-thin::-webkit-scrollbar { height: 4px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: #c2185b40; border-radius: 9999px; }
</style>
