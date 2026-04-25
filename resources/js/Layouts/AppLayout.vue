<script setup>
import { ref, computed, provide } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const permissions = computed(() => page.props.permissions || {});
const sidebarOpen = ref(false);

const userInitial = computed(() => (user.value?.name?.[0] || 'U').toUpperCase());
const roleLabel = computed(() => user.value?.role === 'DIREKTUR' ? 'Pimpinan' : 'Admin Keuangan');

const pageTitle = computed(() => {
    const url = page.url;
    const titles = {
        '/dashboard': 'Dashboard',
        '/import': 'Import Data',
        '/transactions': 'Transaksi',
        '/accounts': 'Rekening Bank',
        '/anomalies': 'Deteksi Anomali',
        '/settings/categories': 'Kategori',
        '/settings/rules': 'Aturan Klasifikasi',
        '/users': 'Kelola Admin',
    };
    return Object.entries(titles).find(([k]) => url.startsWith(k))?.[1] || 'SIKUBI';
});

const currentDate = computed(() =>
    new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
);

// Dynamic navigation based on role
const mainNav = computed(() => {
    const items = [
        { path: '/dashboard', label: 'Dashboard', icon: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z', roles: ['DIREKTUR', 'ADMIN_KEUANGAN'] },
        { path: '/import', label: 'Import Data', icon: 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5', roles: ['ADMIN_KEUANGAN'] },
        { path: '/transactions', label: 'Transaksi', icon: 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5', roles: ['DIREKTUR', 'ADMIN_KEUANGAN'] },
        { path: '/accounts', label: 'Rekening Bank', icon: 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z', roles: ['ADMIN_KEUANGAN'] },
        { path: '/anomalies', label: 'Deteksi Anomali', icon: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z', roles: ['ADMIN_KEUANGAN'] },
    ];
    return items.filter(i => i.roles.includes(user.value?.role));
});

const settingsNav = computed(() => {
    const items = [
        { path: '/settings/categories', label: 'Kategori', icon: 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z', roles: ['DIREKTUR', 'ADMIN_KEUANGAN'] },
        { path: '/settings/rules', label: 'Aturan Klasifikasi', icon: 'M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75', roles: ['DIREKTUR', 'ADMIN_KEUANGAN'] },
        { path: '/users', label: 'Kelola Admin', icon: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', roles: ['DIREKTUR'] },
    ];
    return items.filter(i => i.roles.includes(user.value?.role));
});

function isActive(path) {
    return page.url.startsWith(path);
}

// Toast system
const toasts = ref([]);
let toastId = 0;
function addToast(message, type = 'info') {
    const id = ++toastId;
    toasts.value.push({ id, message, type });
    setTimeout(() => removeToast(id), 4000);
}
function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}
provide('addToast', addToast);
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-cream">
        <!-- Mobile sidebar backdrop -->
        <Transition name="fade">
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 bg-plum/30 backdrop-blur-sm z-40 lg:hidden"
                @click="sidebarOpen = false"
            />
        </Transition>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed lg:static inset-y-0 left-0 z-50 w-[272px] flex flex-col bg-white border-r border-rose-100/60 transition-transform duration-300 lg:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-6 py-5 border-b border-rose-100/60">
                <div class="w-11 h-11 rounded-2xl bg-white border border-rose-200 flex items-center justify-center shadow-sm flex-shrink-0 overflow-hidden">
                    <img src="/images/bigenmi-logo.png" alt="Bigenmi" class="w-8 h-8 object-contain" />
                </div>
                <div>
                    <h1 class="text-lg font-display font-bold text-plum tracking-tight">SIKUBI</h1>
                    <p class="text-[10px] text-surface-500 font-medium tracking-wide uppercase">Sistem Keuangan Bigenmi</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                <p class="px-4 pt-2 pb-2 text-[10px] font-bold text-surface-500 uppercase tracking-widest">Menu Utama</p>
                <Link
                    v-for="item in mainNav" :key="item.path"
                    :href="item.path"
                    :class="[isActive(item.path) ? 'sidebar-link-active' : 'sidebar-link']"
                    @click="sidebarOpen = false"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    <span>{{ item.label }}</span>
                </Link>

                <p class="px-4 pt-5 pb-2 text-[10px] font-bold text-surface-500 uppercase tracking-widest">Pengaturan</p>
                <Link
                    v-for="item in settingsNav" :key="item.path"
                    :href="item.path"
                    :class="[isActive(item.path) ? 'sidebar-link-active' : 'sidebar-link']"
                    @click="sidebarOpen = false"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    <span>{{ item.label }}</span>
                </Link>
            </nav>

            <!-- User Profile -->
            <div class="px-4 py-4 border-t border-rose-100/60">
                <div class="flex items-center gap-3 p-3 rounded-xl bg-cream-200/50 hover:bg-cream-300/50 transition-colors">
                    <div class="w-9 h-9 rounded-xl bg-gradient-rose flex items-center justify-center text-sm font-bold text-white shadow-soft flex-shrink-0">
                        {{ userInitial }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-plum truncate">{{ user?.name || 'User' }}</p>
                        <p class="text-[10px] text-surface-500 font-medium">{{ roleLabel }}</p>
                    </div>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="p-1.5 text-surface-500 hover:text-red-500 rounded-lg transition-colors"
                        title="Keluar"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <header class="h-16 flex items-center justify-between px-4 sm:px-6 border-b border-rose-100/60 bg-white/80 backdrop-blur-xl sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <button
                        class="lg:hidden p-2.5 text-plum hover:text-rose-gold rounded-xl hover:bg-rose-50 active:bg-rose-100 transition-colors"
                        @click.stop="sidebarOpen = !sidebarOpen"
                        aria-label="Toggle menu"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-sm font-semibold text-plum">{{ pageTitle }}</h2>
                        <p class="text-xs text-surface-500 hidden sm:block">{{ currentDate }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="hidden sm:inline-block text-xs text-surface-500 bg-cream-200/60 px-3 py-1 rounded-lg">{{ roleLabel }}</span>
                    <div class="lg:hidden w-8 h-8 rounded-xl bg-gradient-rose flex items-center justify-center text-xs font-bold text-white">
                        {{ userInitial }}
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <slot />
            </main>
        </div>

        <!-- Toast container -->
        <div class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 max-w-sm w-full sm:w-auto">
            <TransitionGroup name="slide-up">
                <div
                    v-for="toast in toasts" :key="toast.id"
                    :class="[
                        'glass-card px-5 py-3 flex items-center gap-3 animate-slide-up',
                        toast.type === 'success' ? 'border-emerald-300' : '',
                        toast.type === 'error' ? 'border-red-300' : '',
                        toast.type === 'info' ? 'border-blue-300' : '',
                    ]"
                >
                    <span :class="[
                        'w-2 h-2 rounded-full flex-shrink-0',
                        toast.type === 'success' ? 'bg-emerald-500' : '',
                        toast.type === 'error' ? 'bg-red-500' : '',
                        toast.type === 'info' ? 'bg-blue-500' : '',
                    ]" />
                    <p class="text-sm text-plum flex-1">{{ toast.message }}</p>
                    <button @click="removeToast(toast.id)" class="text-surface-500 hover:text-plum transition-colors flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </div>
</template>

<style>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active { transition: all 0.3s ease-out; }
.slide-up-leave-active { transition: all 0.2s ease-in; }
.slide-up-enter-from { opacity: 0; transform: translateY(16px); }
.slide-up-leave-to { opacity: 0; transform: translateX(16px); }
</style>
