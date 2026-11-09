<script setup>
/** Login (mockup-driven): cream backdrop, floating peso/wallet watermarks, white card. */
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, KeyRound, Mail, PhilippinePeso, Wallet } from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="relative flex min-h-screen items-center justify-center overflow-x-clip bg-cream px-4 py-10">
        <!-- floating watermarks (decorative) -->
        <PhilippinePeso class="pointer-events-none absolute left-[8%] top-[30%] h-12 w-12 hidden -rotate-12 text-forest/20 load-in md:block" aria-hidden="true" />
        <Wallet class="pointer-events-none absolute right-[7%] top-[22%] h-12 w-12 hidden rotate-6 text-forest/15 load-in md:block" aria-hidden="true" />
        <PhilippinePeso class="pointer-events-none absolute right-[16%] bottom-[28%] h-10 w-10 hidden rotate-12 text-forest/15 load-in md:block" aria-hidden="true" />
        <Wallet class="pointer-events-none absolute bottom-[24%] left-[18%] h-9 w-9 hidden -rotate-12 text-forest/15 load-in md:block" aria-hidden="true" />

        <main class="w-full max-w-[560px] rounded-3xl bg-white px-7 py-10 shadow-cardlg sm:px-12">
            <!-- logo -->
            <img
                src="/images/cashiko-logo.png"
                alt="CashIko"
                class="mx-auto h-24 w-auto select-none object-contain mix-blend-multiply"
                draggable="false"
            />

            <h1 class="mt-5 text-center text-[22px] font-extrabold tracking-tight text-forest-deep">
                Maligayang pagbabalik sa CashIko!
            </h1>
            <p class="mt-1 text-center text-sm text-stone-500">Log in sa iyong account para magpatuloy.</p>

            <div v-if="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="mt-7">
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-forest-deep">
                        Email Address o Numero ng Telepono
                    </label>

                    <div class="relative">
                        <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-forest/70" aria-hidden="true" />
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full rounded-lg border border-warm/70 px-11 py-3 text-[15px] text-forest-deep shadow-sm transition-colors duration-150 placeholder:text-stone-400 focus:border-forest/60 focus:outline-none focus:ring-2 focus:ring-forest/25"
                            placeholder="iyon@email.ph"
                        />
                    </div>
                    <p v-if="form.errors.email" class="mt-1.5 text-xs font-semibold text-red-600">{{ form.errors.email }}</p>
                </div>

                <div class="mt-4">
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-forest-deep">Password</label>

                    <div class="relative">
                        <KeyRound class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-forest/70" aria-hidden="true" />
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-lg border border-warm/70 py-3 pl-11 pr-11 text-[15px] text-forest-deep shadow-sm transition-colors duration-150 placeholder:text-stone-400 focus:border-forest/60 focus:outline-none focus:ring-2 focus:ring-forest/25"
                            placeholder="••••••••"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            :aria-label="showPassword ? 'Itago ang password' : 'Ipakita ang password'"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-0.5 text-forest/60 transition-colors duration-150 hover:text-forest focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-forest/40"
                        >
                            <EyeOff v-if="showPassword" class="h-5 w-5" aria-hidden="true" />
                            <Eye v-else class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="mt-1.5 text-xs font-semibold text-red-600">{{ form.errors.password }}</p>
                </div>

                <div class="mt-2 text-right">
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm font-bold text-forest transition-colors duration-150 hover:text-forest-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-forest/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                    >
                        Nakalimutan ang password?
                    </Link>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="mt-5 w-full rounded-xl bg-forest py-3.5 text-[17px] font-bold text-white shadow-sm transition-all duration-150 ease-out hover:bg-forest-dark active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-forest/50 focus-visible:ring-offset-2 focus-visible:ring-offset-white disabled:opacity-60"
                >
                    <span v-if="form.processing" class="inline-flex items-center justify-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.3" stroke-width="4" />
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                        </svg>
                        Naglo-log in…
                    </span>
                    <span v-else>Mag-log in</span>
                </button>
            </form>

            <p class="mt-6 text-center text-[15px] text-forest-deep">
                Wala ka pang account?
                <Link
                    :href="route('register')"
                    class="font-bold text-forest/80 transition-colors duration-150 hover:text-forest focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-forest/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                >
                    Mag-sign up dito.
                </Link>
            </p>
        </main>
    </div>
</template>

<style scoped>
.load-in {
    animation: floaty 7s ease-in-out infinite;
}
.load-in:nth-child(2 of .load-in) {
    animation-delay: -2s;
}
.load-in:nth-child(3 of .load-in) {
    animation-delay: -4s;
}
@keyframes floaty {
    0%, 100% { transform: translateY(0); opacity: 0.7; }
    50% { transform: translateY(-8px); opacity: 1; }
}
</style>
