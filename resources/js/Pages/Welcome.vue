<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <Head title="Selamat Datang di SIKUBI — Sistem Informasi Keuangan Bigenmi" />
    
    <div class="min-h-screen bg-cream flex flex-col justify-between overflow-hidden relative selection:bg-rose-gold selection:text-white">
        <!-- Decorative Premium Floating Accents -->
        <div class="absolute top-0 right-0 w-[40vw] h-[40vw] rounded-full bg-gradient-to-bl from-rose-100/30 to-champagne-100/10 blur-[80px] pointer-events-none" />
        <div class="absolute -bottom-20 -left-20 w-[50vw] h-[50vw] rounded-full bg-gradient-to-tr from-rose-100/20 to-champagne-100/20 blur-[100px] pointer-events-none" />

        <!-- Floating Abstract Sakura SVG Petals -->
        <div class="absolute top-[15%] left-[8%] animate-pulse pointer-events-none opacity-25">
            <svg class="w-8 h-8 text-rose-gold/60" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2.25c.34 3.03 2.68 5.37 5.71 5.71.34-3.03-2.3-5.37-5.71-5.71zm0 19.5c-.34-3.03-2.68-5.37-5.71-5.71-.34 3.03 2.3 5.37 5.71 5.71zM2.25 12c3.03.34 5.37 2.68 5.71 5.71 3.03-.34 5.37-2.68 5.71-5.71-3.03-.34-5.37-2.68-5.71-5.71-3.03.34-5.37 2.68-5.71 5.71zm19.5 0c-3.03-.34-5.37-2.68-5.71-5.71-3.03.34-5.37 2.68-5.71 5.71 3.03.34 5.37 2.68 5.71-5.71-3.03.34-5.37 2.68-5.71 5.71z" />
            </svg>
        </div>
        <div class="absolute bottom-[20%] right-[10%] animate-bounce pointer-events-none opacity-20" style="animation-duration: 6s;">
            <svg class="w-10 h-10 text-rose-gold/40" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2.25c.34 3.03 2.68 5.37 5.71 5.71.34-3.03-2.3-5.37-5.71-5.71zm0 19.5c-.34-3.03-2.68-5.37-5.71-5.71-.34 3.03 2.3 5.37 5.71 5.71z" />
            </svg>
        </div>

        <!-- Top Navigation Header -->
        <header class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white border border-rose-200 flex items-center justify-center shadow-sm overflow-hidden">
                    <img src="/images/bigenmi-logo.png" alt="PT Bigenmi" class="w-7 h-7 object-contain" />
                </div>
                <div>
                    <h1 class="text-base font-sans font-bold text-plum tracking-tight leading-none">SIKUBI</h1>
                    <p class="text-[9px] text-surface-500 font-semibold tracking-wider uppercase mt-0.5">PT Bigenmi Gemilang Indonesia</p>
                </div>
            </div>
            
            <nav class="flex items-center gap-3">
                <template v-if="user">
                    <Link :href="route('dashboard')" class="btn-primary text-xs !py-2 !px-4">
                        Ke Dashboard →
                    </Link>
                </template>
                <template v-else>
                    <Link :href="route('login')" class="btn-secondary text-xs !py-2 !px-4">
                        Masuk Sistem
                    </Link>
                    <Link v-if="canRegister" :href="route('register')" class="btn-primary text-xs !py-2 !px-4">
                        Registrasi Admin
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Main Hero & Feature Sections -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col justify-center py-8 sm:py-12 relative z-10 animate-fade-in">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                
                <!-- Left: Branding & Core CTA -->
                <div class="lg:col-span-6 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200/50 text-xs font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse" />
                        Platform Pengawasan & Mutasi Kepatuhan
                    </div>
                    <div class="space-y-3">
                        <h2 class="text-3xl sm:text-5xl font-display font-bold text-plum leading-[1.1] tracking-tight">
                            Sistem Tata Kelola <br class="hidden sm:block" />
                            Keuangan <span class="text-rose-gold bg-gradient-to-r from-rose-gold to-rose-600 bg-clip-text text-transparent">Bigenmi</span>
                        </h2>
                        <p class="text-sm sm:text-base text-surface-600 leading-relaxed max-w-xl mx-auto lg:mx-0">
                            SIKUBI menyediakan otorisasi mutasi luar biasa, pencocokan aturan jurnal kategori, dan deteksi anomali kepatuhan internal untuk menjaga kesehatan keuangan korporat.
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3.5 pt-2">
                        <template v-if="user">
                            <Link :href="route('dashboard')" class="btn-primary text-sm !py-3 !px-7">
                                Selamat Datang Kembali, {{ user.name.split(' ')[0] }}
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="btn-primary text-sm !py-3 !px-7 font-bold">
                                Mulai Tinjau Keuangan
                            </Link>
                            <a href="#features" class="btn-secondary text-sm !py-3 !px-6">
                                Pelajari Fitur
                            </a>
                        </template>
                    </div>
                </div>

                <!-- Right: High-End Live Dashboard Preview Container -->
                <div class="lg:col-span-6 relative mt-4 lg:mt-0">
                    <div class="glass-card overflow-hidden shadow-2xl border-rose-200/40 transform rotate-1 hover:rotate-0 transition-transform duration-500 bg-white/80 backdrop-blur-md">
                        <!-- Window header bar -->
                        <div class="px-4 py-3 bg-cream-200/40 border-b border-rose-100/50 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-red-400 inline-block" />
                                <span class="w-3 h-3 rounded-full bg-amber-400 inline-block" />
                                <span class="w-3 h-3 rounded-full bg-emerald-400 inline-block" />
                            </div>
                            <span class="text-[10px] font-mono text-surface-500 uppercase tracking-widest">sikubi.bigenmi.co.id</span>
                            <div class="w-6" />
                        </div>
                        <!-- Mini Preview Mockup Content -->
                        <div class="p-5 space-y-4">
                            <!-- Visual KPI Rows -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-emerald-50/50 rounded-xl p-3 border border-emerald-100/40">
                                    <p class="text-[9px] font-bold text-emerald-700 uppercase tracking-wider">Mutasi Pemasukan</p>
                                    <p class="text-sm font-bold text-emerald-600 mt-0.5">Rp 120.450.000</p>
                                </div>
                                <div class="bg-rose-50/50 rounded-xl p-3 border border-rose-100/40">
                                    <p class="text-[9px] font-bold text-rose-700 uppercase tracking-wider">Mutasi Pengeluaran</p>
                                    <p class="text-sm font-bold text-red-500 mt-0.5">Rp 84.120.000</p>
                                </div>
                            </div>
                            <!-- Mini Anomaly Record Card -->
                            <div class="bg-white border border-rose-100/60 rounded-xl p-3 shadow-sm space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="badge-red text-[9px] font-bold">🚨 Risiko Tinggi</span>
                                    <span class="text-[9px] font-semibold text-surface-500">Skor: 94%</span>
                                </div>
                                <p class="text-xs font-bold text-plum truncate">Transfer Nominal Luar Biasa ≥ 50jt</p>
                                <div class="p-2 rounded bg-cream-100/60 text-[9px] text-surface-600 leading-normal border-l-2 border-rose-gold">
                                    "Memerlukan verifikasi kelayakan transaksi oleh Direktur Pelaksana."
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Features Grid Section -->
            <div id="features" class="mt-20 space-y-8 pt-8 border-t border-rose-100/30">
                <div class="text-center max-w-xl mx-auto space-y-2">
                    <h3 class="text-xl sm:text-2xl font-display font-bold text-plum">Keunggulan & Fitur Utama</h3>
                    <p class="text-xs sm:text-sm text-surface-500 leading-relaxed">
                        Arsitektur sistem kepatuhan berlapis untuk menjamin integritas transaksi internal.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <!-- Card 1: Integrated Mutations -->
                    <div class="glass-card p-5 space-y-4 hover:-translate-y-1 hover:shadow-card-hover duration-300 transition-all border-b-2 hover:border-b-rose-gold">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-gold flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                        </div>
                        <div class="space-y-1.5">
                            <h4 class="text-sm font-bold text-plum">Mutasi & Kas Terpadu</h4>
                            <p class="text-xs text-surface-600 leading-relaxed">
                                Import data rekening bank (Excel/CSV) serta transaksi kas manual terintegrasi dengan validasi duplikasi mutasi.
                            </p>
                        </div>
                    </div>

                    <!-- Card 2: Automatic Classification -->
                    <div class="glass-card p-5 space-y-4 hover:-translate-y-1 hover:shadow-card-hover duration-300 transition-all border-b-2 hover:border-b-emerald-400">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z" />
                            </svg>
                        </div>
                        <div class="space-y-1.5">
                            <h4 class="text-sm font-bold text-plum">Klasifikasi Cerdas</h4>
                            <p class="text-xs text-surface-600 leading-relaxed">
                                Klasifikasi otomatis deskripsi mutasi berdasarkan prioritas pencocokan kata kunci aturan (Exact, Contains, Regex).
                            </p>
                        </div>
                    </div>

                    <!-- Card 3: Anomaly & Auditing -->
                    <div class="glass-card p-5 space-y-4 hover:-translate-y-1 hover:shadow-card-hover duration-300 transition-all border-b-2 hover:border-b-red-400">
                        <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-500 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="space-y-1.5">
                            <h4 class="text-sm font-bold text-plum">Otorisasi & Pengawasan</h4>
                            <p class="text-xs text-surface-600 leading-relaxed">
                                Deteksi anomali mutasi luar biasa yang dipetakan langsung dari staf verifikator menuju otorisasi persetujuan Direktur.
                            </p>
                        </div>
                    </div>

                    <!-- Card 4: Reports -->
                    <div class="glass-card p-5 space-y-4 hover:-translate-y-1 hover:shadow-card-hover duration-300 transition-all border-b-2 hover:border-b-amber-400">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H3a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="space-y-1.5">
                            <h4 class="text-sm font-bold text-plum">Laporan & Ekspor</h4>
                            <p class="text-xs text-surface-600 leading-relaxed">
                                Cetak rekap mutasi atau unduh laporan CSV/Excel secara real-time yang dioptimalkan untuk kompatibilitas spreadsheet.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full border-t border-rose-100/50 py-6 text-center text-[10px] sm:text-xs text-surface-500 relative z-10 bg-white/40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <p>© {{ new Date().getFullYear() }} PT Bigenmi Gemilang Indonesia. Hak Cipta Dilindungi.</p>
                <div class="flex items-center justify-center gap-4 font-semibold text-rose-gold">
                    <span>Keamanan Terenkripsi</span>
                    <span>·</span>
                    <span>Audit Internal Aktif</span>
                </div>
            </div>
        </footer>
    </div>
</template>
