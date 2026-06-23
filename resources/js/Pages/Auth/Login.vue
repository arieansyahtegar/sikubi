<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}

/* ── Parallax mouse-tracking for background blobs ── */
const mouseX = ref(0);
const mouseY = ref(0);

function onMouseMove(e) {
    const cx = window.innerWidth / 2;
    const cy = window.innerHeight / 2;
    mouseX.value = ((e.clientX - cx) / cx) * 12;
    mouseY.value = ((e.clientY - cy) / cy) * 12;
}

onMounted(() => window.addEventListener('mousemove', onMouseMove));
onUnmounted(() => window.removeEventListener('mousemove', onMouseMove));
</script>

<template>
    <Head title="Login — SIKUBI" />

    <div class="min-h-screen bg-gradient-cream flex items-center justify-center p-4 relative overflow-hidden">
        <!-- ═══ Animated background blobs with parallax ═══ -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div
                class="login-blob blob-1"
                :style="{ transform: `translate(${mouseX * 1.2}px, ${mouseY * 1.2}px)` }"
            />
            <div
                class="login-blob blob-2"
                :style="{ transform: `translate(${mouseX * -0.8}px, ${mouseY * -0.8}px)` }"
            />
            <div
                class="login-blob blob-3"
                :style="{ transform: `translate(${mouseX * 0.6}px, ${mouseY * 0.6}px)` }"
            />
        </div>

        <!-- ═══ Floating sakura petals ═══ -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <svg class="login-petal petal-a" viewBox="0 0 32 32" fill="none">
                <path d="M16 2C16.5 8 22 13.5 28 14C22 14.5 16.5 20 16 26C15.5 20 10 14.5 4 14C10 13.5 15.5 8 16 2Z" fill="currentColor"/>
            </svg>
            <svg class="login-petal petal-b" viewBox="0 0 24 24" fill="none">
                <path d="M12 1C12.4 6 17 10.6 22 11C17 11.4 12.4 16 12 21C11.6 16 7 11.4 2 11C7 10.6 11.6 6 12 1Z" fill="currentColor"/>
            </svg>
            <svg class="login-petal petal-c" viewBox="0 0 20 20" fill="none">
                <path d="M10 1C10.3 5 14 8.7 18 9C14 9.3 10.3 13 10 17C9.7 13 6 9.3 2 9C6 8.7 9.7 5 10 1Z" fill="currentColor"/>
            </svg>
            <svg class="login-petal petal-d" viewBox="0 0 28 28" fill="none">
                <path d="M14 1C14.4 6 19 10.6 25 11C19 11.4 14.4 16 14 22C13.6 16 9 11.4 3 11C9 10.6 13.6 6 14 1Z" fill="currentColor"/>
            </svg>
            <svg class="login-petal petal-e" viewBox="0 0 16 16" fill="none">
                <path d="M8 1C8.2 4 11 6.8 14 7C11 7.2 8.2 10 8 13C7.8 10 5 7.2 2 7C5 6.8 7.8 4 8 1Z" fill="currentColor"/>
            </svg>
        </div>

        <!-- ═══ Login Card ═══ -->
        <div class="w-full max-w-md relative login-card-entrance">
            <!-- Logo Section -->
            <div class="text-center mb-8 login-stagger-1">
                <div class="login-logo-ring">
                    <img src="/images/bigenmi-logo.png" alt="Bigenmi" class="w-full h-full object-contain" />
                </div>
                <h1 class="login-title font-display">SIKUBI</h1>
                <p class="text-sm text-surface-600 mt-1 font-body">Sistem Keuangan Bigenmi</p>
                <div class="login-divider" />
            </div>

            <!-- Form Card -->
            <div class="glass-card p-6 sm:p-8 login-stagger-2 login-form-card">
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Error Alert -->
                    <Transition name="login-shake">
                        <div v-if="form.errors.email" class="login-error-box">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            {{ form.errors.email }}
                        </div>
                    </Transition>

                    <!-- Email -->
                    <div class="login-field-group">
                        <label class="label-text" for="login-email">Email</label>
                        <div class="relative login-input-wrapper">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-surface-500 login-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </div>
                            <input
                                id="login-email"
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="email"
                                placeholder="nama@bigenmi.co.id"
                                class="input-field !pl-11"
                            />
                            <div class="login-focus-glow" />
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="login-field-group">
                        <label class="label-text" for="login-password">Password</label>
                        <div class="relative login-input-wrapper">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-surface-500 login-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                            <input
                                id="login-password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="input-field !pl-11 !pr-11"
                            />
                            <div class="login-focus-glow" />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-surface-500 hover:text-rose-gold transition-colors duration-300"
                                @click="showPassword = !showPassword"
                            >
                                <svg v-if="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="btn-primary w-full py-3 text-base login-submit-btn"
                    >
                        <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ form.processing ? 'Masuk...' : 'Masuk' }}
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 login-stagger-3">
                <p class="text-xs text-surface-500 font-body">PT Bigenmi Gemilang Indonesia</p>
                <p class="text-[10px] text-surface-500/60 mt-0.5">© 2026 · Internal Financial System</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ═══════════════════════════════════════
   PARALLAX BACKGROUND BLOBS
   Uses SIKUBI palette: rose-500, champagne-500, rose-200
   ═══════════════════════════════════════ */

.login-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: transform;
}

/* rose-500 (#E8637A) glow — top-right */
.blob-1 {
    width: 420px;
    height: 420px;
    top: -80px;
    right: -80px;
    background: radial-gradient(circle, rgba(232, 99, 122, 0.16) 0%, rgba(244, 137, 154, 0.06) 60%, transparent 80%);
    animation: blobMorph1 18s ease-in-out infinite;
}

/* champagne-500 (#C49A4A) glow — bottom-left */
.blob-2 {
    width: 380px;
    height: 380px;
    bottom: -100px;
    left: -60px;
    background: radial-gradient(circle, rgba(196, 154, 74, 0.12) 0%, rgba(242, 201, 122, 0.05) 60%, transparent 80%);
    animation: blobMorph2 22s ease-in-out infinite;
}

/* rose-200 (#FFD0D6) glow — center-right */
.blob-3 {
    width: 300px;
    height: 300px;
    top: 35%;
    right: 20%;
    background: radial-gradient(circle, rgba(255, 208, 214, 0.12) 0%, rgba(255, 232, 235, 0.04) 60%, transparent 80%);
    animation: blobMorph3 15s ease-in-out infinite;
}

@keyframes blobMorph1 {
    0%, 100% { border-radius: 42% 58% 70% 30% / 45% 45% 55% 55%; }
    25% { border-radius: 55% 45% 35% 65% / 58% 32% 68% 42%; }
    50% { border-radius: 38% 62% 55% 45% / 42% 58% 42% 58%; }
    75% { border-radius: 62% 38% 45% 55% / 55% 42% 58% 45%; }
}

@keyframes blobMorph2 {
    0%, 100% { border-radius: 55% 45% 60% 40% / 40% 60% 40% 60%; }
    33% { border-radius: 40% 60% 45% 55% / 55% 40% 60% 45%; }
    66% { border-radius: 60% 40% 50% 50% / 45% 55% 45% 55%; }
}

@keyframes blobMorph3 {
    0%, 100% { border-radius: 50% 50% 50% 50%; transform: scale(1); }
    50% { border-radius: 40% 60% 55% 45% / 55% 45% 50% 50%; transform: scale(1.08); }
}

/* ═══════════════════════════════════════
   FLOATING SAKURA PETALS
   Colors from: rose-gold, champagne-500, rose-300
   ═══════════════════════════════════════ */

.login-petal {
    position: absolute;
    pointer-events: none;
    will-change: transform, opacity;
}

/* rose-gold (#E8637A) */
.petal-a {
    width: 28px;
    top: 14%;
    left: 12%;
    color: rgba(232, 99, 122, 0.10);
    animation: petalDrift1 16s ease-in-out infinite;
}

/* champagne-500 (#C49A4A) */
.petal-b {
    width: 20px;
    top: 68%;
    right: 10%;
    color: rgba(196, 154, 74, 0.08);
    animation: petalDrift2 13s ease-in-out infinite;
    animation-delay: 1.5s;
}

/* rose-300 (#FFB0BA) */
.petal-c {
    width: 16px;
    top: 42%;
    left: 6%;
    color: rgba(255, 176, 186, 0.10);
    animation: petalDrift3 18s ease-in-out infinite;
    animation-delay: 3s;
}

/* rose-200 (#FFD0D6) */
.petal-d {
    width: 22px;
    top: 20%;
    right: 18%;
    color: rgba(255, 208, 214, 0.10);
    animation: petalDrift2 20s ease-in-out infinite;
    animation-delay: 5s;
}

/* champagne-300 (#F2C97A) */
.petal-e {
    width: 14px;
    bottom: 18%;
    left: 22%;
    color: rgba(242, 201, 122, 0.07);
    animation: petalDrift1 14s ease-in-out infinite;
    animation-delay: 7s;
}

@keyframes petalDrift1 {
    0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); opacity: 0.4; }
    20% { transform: translate(15px, -30px) rotate(72deg) scale(1.1); opacity: 0.9; }
    40% { transform: translate(-10px, -55px) rotate(144deg) scale(0.95); opacity: 0.6; }
    60% { transform: translate(20px, -80px) rotate(216deg) scale(1.05); opacity: 0.8; }
    80% { transform: translate(-5px, -40px) rotate(288deg) scale(1); opacity: 0.5; }
}

@keyframes petalDrift2 {
    0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); opacity: 0.3; }
    25% { transform: translate(-20px, -25px) rotate(90deg) scale(1.15); opacity: 0.8; }
    50% { transform: translate(12px, -50px) rotate(180deg) scale(0.9); opacity: 0.5; }
    75% { transform: translate(-8px, -30px) rotate(270deg) scale(1.05); opacity: 0.7; }
}

@keyframes petalDrift3 {
    0%, 100% { transform: translate(0, 0) rotate(0deg); opacity: 0.3; }
    33% { transform: translate(25px, -40px) rotate(120deg); opacity: 0.7; }
    66% { transform: translate(-15px, -20px) rotate(240deg); opacity: 0.5; }
}

/* ═══════════════════════════════════════
   CARD ENTRANCE — Staggered cascade
   Uses SIKUBI cubic-bezier(0.16, 1, 0.3, 1)
   ═══════════════════════════════════════ */

.login-card-entrance {
    animation: cardEntrance 0.9s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes cardEntrance {
    0% { opacity: 0; transform: translateY(30px) scale(0.96); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}

.login-stagger-1 {
    animation: staggerFade 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
}

.login-stagger-2 {
    animation: staggerFade 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.4s both;
}

.login-stagger-3 {
    animation: staggerFade 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.6s both;
}

@keyframes staggerFade {
    0% { opacity: 0; transform: translateY(14px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════════════
   LOGO — Breathing glow ring
   Shadow uses rose-gold rgba from shadow-glow token
   Border uses rose-200 (#FFD0D6)
   ═══════════════════════════════════════ */

.login-logo-ring {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: 24px;
    background: white;
    border: 1px solid rgba(255, 208, 214, 0.5); /* rose-200 */
    box-shadow:
        0 4px 20px rgba(232, 99, 122, 0.1),  /* shadow-glow tuned */
        0 0 0 0 rgba(232, 99, 122, 0);
    padding: 12px;
    margin-bottom: 16px;
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    animation: logoBreath 4s ease-in-out infinite;
}

.login-logo-ring:hover {
    transform: scale(1.08) rotate(-3deg);
    box-shadow:
        0 8px 32px rgba(232, 99, 122, 0.18),  /* shadow-glow */
        0 0 0 4px rgba(232, 99, 122, 0.06);
    border-color: rgba(232, 99, 122, 0.25); /* rose-gold accent */
}

@keyframes logoBreath {
    0%, 100% { box-shadow: 0 4px 20px rgba(232, 99, 122, 0.1), 0 0 0 0 rgba(232, 99, 122, 0); }
    50% { box-shadow: 0 6px 28px rgba(232, 99, 122, 0.15), 0 0 0 6px rgba(232, 99, 122, 0.04); }
}

/* ═══════════════════════════════════════
   TITLE — Shimmer using plum (#2C1929) + rose-gold (#E8637A)
   ═══════════════════════════════════════ */

.login-title {
    font-size: 1.875rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    background: linear-gradient(
        120deg,
        #2C1929 0%,   /* plum */
        #2C1929 40%,
        #E8637A 50%,  /* rose-gold shimmer */
        #2C1929 60%,
        #2C1929 100%
    );
    background-size: 250% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: titleShimmer 5s ease-in-out infinite;
}

@keyframes titleShimmer {
    0%, 100% { background-position: 100% center; }
    50% { background-position: 0% center; }
}

/* ═══════════════════════════════════════
   DIVIDER — gradient-rose to gradient-gold
   ═══════════════════════════════════════ */

.login-divider {
    width: 0;
    height: 2px;
    border-radius: 99px;
    background: linear-gradient(90deg, #E8637A, #C49A4A); /* rose-gold → champagne-500 */
    margin: 12px auto 0;
    animation: dividerGrow 1s cubic-bezier(0.16, 1, 0.3, 1) 0.6s forwards;
}

@keyframes dividerGrow {
    to { width: 48px; }
}

/* ═══════════════════════════════════════
   FORM CARD — Enhanced glass-card hover
   Matches glass-card-hover from app.css
   ═══════════════════════════════════════ */

.login-form-card {
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.login-form-card:hover {
    border-color: rgba(232, 99, 122, 0.2); /* rose-gold border */
    background: rgba(255, 255, 255, 0.92);
    box-shadow:
        0 12px 40px rgba(232, 99, 122, 0.08),  /* shadow-card-hover */
        0 2px 8px rgba(0, 0, 0, 0.02),
        inset 0 1px 0 rgba(255, 255, 255, 1);
}

/* ═══════════════════════════════════════
   INPUT FOCUS GLOW — rose-gold ring
   Matches input-field focus:ring-rose-gold/20
   ═══════════════════════════════════════ */

.login-field-group {
    position: relative;
}

.login-input-wrapper {
    position: relative;
}

.login-focus-glow {
    position: absolute;
    inset: 0;
    border-radius: 0.75rem; /* rounded-xl from input-field */
    pointer-events: none;
    opacity: 0;
    box-shadow: 0 0 0 3px rgba(232, 99, 122, 0.12); /* rose-gold/12 */
    transition: opacity 0.3s ease;
}

.login-input-wrapper:focus-within .login-focus-glow {
    opacity: 1;
}

.login-input-wrapper:focus-within .login-input-icon {
    color: #E8637A; /* rose-gold */
    transition: color 0.3s ease;
}

/* ═══════════════════════════════════════
   SUBMIT BUTTON — Shine sweep + pulse
   Inherits btn-primary from app.css theme
   ═══════════════════════════════════════ */

.login-submit-btn {
    position: relative;
    overflow: hidden;
}

.login-submit-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.15),
        transparent
    );
    transition: left 0.6s ease;
}

.login-submit-btn:hover::before {
    left: 100%;
}

.login-submit-btn:hover:not(:disabled) {
    animation: submitPulse 2s ease-in-out infinite;
}

@keyframes submitPulse {
    0%, 100% {
        box-shadow:
            0 8px 24px rgba(232, 99, 122, 0.4),     /* shadow from btn-primary:hover */
            0 0 0 0 rgba(232, 99, 122, 0.15);
    }
    50% {
        box-shadow:
            0 8px 24px rgba(232, 99, 122, 0.4),
            0 0 0 8px rgba(232, 99, 122, 0);
    }
}

/* ═══════════════════════════════════════
   ERROR BOX — rose-50 bg, rose-800 text
   ═══════════════════════════════════════ */

.login-error-box {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #FFF5F6;         /* rose-50 */
    border: 1px solid #FFD0D6;   /* rose-200 */
    border-radius: 0.75rem;      /* rounded-xl */
    color: #862840;              /* rose-800 */
    font-size: 0.85rem;
    font-weight: 500;
    animation: shakeIn 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}

@keyframes shakeIn {
    0%, 100% { transform: translateX(0); }
    15% { transform: translateX(-8px); }
    30% { transform: translateX(8px); }
    45% { transform: translateX(-5px); }
    60% { transform: translateX(5px); }
    75% { transform: translateX(-2px); }
    90% { transform: translateX(2px); }
}

/* ═══════════════════════════════════════
   TRANSITIONS
   ═══════════════════════════════════════ */

.login-shake-enter-active {
    animation: shakeIn 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}

.login-shake-leave-active {
    transition: all 0.3s ease;
}

.login-shake-enter-from,
.login-shake-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.97);
}
</style>
