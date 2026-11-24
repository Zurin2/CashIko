<script setup>
/**
 * Public marketing page (§8.1) — guest-only; logged-in users are redirected
 * by the route closure in routes/web.php.
 *
 * Content ported from the Astro marketing site (hero → facts → problem →
 * how-it-works → features → bilingual → pricing → founders → FAQ → CTA) so
 * both surfaces tell the same story. Copy stays honest: no testimonials, no
 * invented proof, no BIR claim, pricing matches the Pricing screen.
 *
 * Typography stays inside the app's sans stack — no display serif.
 * Motion: @vueuse/motion (spring reveals) + CountUp + pure-CSS keyframes, all
 * disabled under prefers-reduced-motion.
 */
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  ArrowRight, Boxes, Check, ChevronDown, HandCoins, Menu, ReceiptText,
  ShoppingBag, TrendingUp, X,
} from 'lucide-vue-next'
import CountUp from '@/Components/CountUp.vue'
import { isFilipino, setLocale, t } from '@/lib/i18n'

defineProps({ canRegister: Boolean, laravelVersion: String })

const spring = (delay = 0) => ({ type: 'spring', stiffness: 160, damping: 20, delay })
const reveal = { opacity: 1, y: 0, transition: spring(0) }
const hidden = { opacity: 0, y: 26 }

/* ---------------------------------------------------------------- language */
const lang = computed(() => (isFilipino() ? 'fil' : 'en'))
const setLang = (code) => setLocale(code === 'fil' ? 'filipino' : 'english')

/* ------------------------------------------------------------------- copy */
const heroWords = computed(() => t('Know what you really earn.').split(' '))
const problemTitle = "The notebook isn't wrong. It's just unfinished."
const ledgerTitle = "A ledger that remembers so you don't have to."

const facts = [
  { value: '3 taps', label: 'to record a sale' },
  { value: '₱99', label: 'per month, after the trial' },
  { value: '14 days', label: 'free, no credit card' },
  { value: 'EN / FIL', label: 'switch language anytime' },
]

const problems = [
  { n: '01', text: 'Sales get written down. Costs get remembered.' },
  { n: '02', text: 'Utang lives in three places at once.' },
  { n: '03', text: 'You only find out at the end of the month.' },
]

const steps = [
  {
    n: '01',
    title: 'Add what you sell',
    body: 'Name, price, cost, and how many you have. One product at a time — or your whole list.',
  },
  {
    n: '02',
    title: 'Record each sale in three taps',
    body: 'Pick the product, the quantity, the payment — cash, GCash, Maya. Done.',
  },
  {
    n: '03',
    title: 'Read the truth',
    body: 'Today, this week, this month: sales, expenses, utang, and the profit left over.',
  },
]

const features = [
  { key: 'stock', icon: Boxes, title: 'A warning before you run out, not after.' },
  { key: 'utang', icon: HandCoins, title: ledgerTitle },
  { key: 'profit', icon: TrendingUp, title: 'Gross sales are not profit. This is.' },
]

const plans = [
  {
    id: 'basic',
    name: 'Basic',
    tag: 'One store',
    price: 99,
    featured: false,
    bullets: [
      'One branch',
      'Stock tracking and low-stock alerts',
      'Three-tap sales recording — cash, GCash, Maya, e-wallets',
      'Utang ledger with payment reminders',
      'Expense logging',
      'Real profit: sales − cost − expenses',
      'CSV export and printable reports',
      'English and Filipino',
    ],
  },
  {
    id: 'premium',
    name: 'Premium',
    tag: 'Multi-branch',
    price: 199,
    featured: true,
    bullets: [
      'Everything in Basic',
      'Multiple branches, tracked in real time',
      'AI assistant — Tanong kay CashIko',
      'Priority support',
    ],
  },
]

const faqs = [
  {
    q: 'What exactly is CashIko?',
    a: 'A web app that keeps the books of a small store: stock, sales, utang, expenses and the profit left over. It is built for one phone behind the counter.',
  },
  {
    q: 'Do I need to install an app?',
    a: 'No. It opens in your browser, and you can add it to your home screen so it behaves like an app.',
  },
  {
    q: 'Does it work without internet?',
    a: 'No. CashIko needs a connection to save your records safely. If the signal drops, record later — nothing is saved until it goes through.',
  },
  {
    q: 'How long does setup take?',
    a: 'Under a minute: business name, type, language. You can start recording a sale before you finish adding every product.',
  },
  {
    q: 'How does the free trial work?',
    a: 'Fourteen days, no credit card. After that, Basic is ₱99 a month and Premium is ₱199 a month. You can switch or cancel in Settings.',
  },
  {
    q: 'Is utang a payment method?',
    a: 'No. A sale is recorded when money comes in. Utang is a separate ledger: who owes, how much, and when it is due — with payments that bring the balance down.',
  },
  {
    q: 'Can I correct a sale I recorded by mistake?',
    a: 'Yes. Open the sale and void it. Totals, stock and profit recalculate.',
  },
  {
    q: 'Can I use Filipino instead of English?',
    a: 'Yes. Settings → Language switches every screen. This page has the same switch at the top.',
  },
  {
    q: 'Is CashIko a BIR-accredited POS?',
    a: 'No. CashIko is not a BIR-accredited POS and does not issue official receipts. It is a record-keeping tool for your own numbers.',
  },
  {
    q: 'Do you sell my data?',
    a: 'No. Your records stay with your business.',
  },
  {
    q: 'What happens to my records if I stop using CashIko?',
    a: 'You can export your sales and reports to CSV before you go.',
  },
]

/* Illustrative 7-day bars for the hero preview (values sum to the ₱2,415 shown). */
const bars = [
  { h: 42, label: 'M', v: 240 }, { h: 58, label: 'T', v: 310 }, { h: 35, label: 'W', v: 190 },
  { h: 70, label: 'T', v: 420 }, { h: 52, label: 'F', v: 280 }, { h: 88, label: 'S', v: 560 },
  { h: 64, label: 'S', v: 415 },
]

const navLinks = [
  { href: '#how', label: 'How it works' },
  { href: '#features', label: 'Features' },
  { href: '#pricing', label: 'Pricing' },
  { href: '#faq', label: 'FAQ' },
]

/* Rotating words carousel — the claims that scroll past under the hero. */
const ticker = [
  'Sales in one tap',
  'Debts with due dates',
  'Stock that never runs short',
  'Profit — real, every day',
  'Every peso of expense logged',
  '3-tap recording',
]

/* ------------------------------------------------------------ interaction */
const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

const menuOpen = ref(false)
const closeMenu = () => { menuOpen.value = false }

const progress = ref(0)
const onScroll = () => {
  const doc = document.documentElement
  const max = doc.scrollHeight - doc.clientHeight
  progress.value = max > 0 ? Math.min(100, Math.max(0, (doc.scrollTop / max) * 100)) : 0
}

/* Pointer-driven tilt on the hero preview (fine pointers only). */
const tilt = ref({ x: 0, y: 0 })
const onTilt = (event) => {
  if (prefersReducedMotion() || window.matchMedia('(pointer: coarse)').matches) return
  const rect = event.currentTarget.getBoundingClientRect()
  const px = (event.clientX - rect.left) / rect.width - 0.5
  const py = (event.clientY - rect.top) / rect.height - 0.5
  tilt.value = { x: px * 7, y: py * -7 }
}
const resetTilt = () => { tilt.value = { x: 0, y: 0 } }

const onKeydown = (event) => {
  if (event.key === 'Escape') closeMenu()
}

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('keydown', onKeydown)
})
onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Head title="Know what you really earn" />

  <div class="min-h-screen overflow-x-hidden bg-surface">
    <!-- ------------------------------------------------------ scroll progress -->
    <div class="fixed inset-x-0 top-0 z-50 h-0.5 bg-transparent" aria-hidden="true">
      <div
        class="h-full origin-left bg-gradient-to-r from-forest via-mango to-forest transition-[width] duration-150 ease-out"
        :style="{ width: progress + '%' }"
      />
    </div>

    <!-- ------------------------------------------------------------- nav -->
    <nav class="sticky top-0 z-40 border-b border-warm bg-white/90 backdrop-blur">
      <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3">
        <a href="#top" class="flex items-center gap-2" @click="closeMenu">
          <img src="/images/cashiko-icon.svg" alt="" class="h-9 w-auto" draggable="false" />
          <span class="text-lg font-extrabold tracking-tight text-forest-dark">CashIko</span>
        </a>

        <!-- desktop nav -->
        <div class="hidden items-center gap-1 md:flex">
          <a
            v-for="l in navLinks"
            :key="l.href"
            :href="l.href"
            class="rounded-lg px-3 py-2 text-sm font-semibold text-forest-muted transition-colors duration-150 hover:text-forest"
          >{{ t(l.label) }}</a>
        </div>

        <!-- desktop language + login -->
        <div class="hidden items-center gap-2 md:flex">
          <div class="flex items-center rounded-full border border-warm bg-white p-0.5" role="group" :aria-label="t('Language')">
            <button
              v-for="code in ['en', 'fil']"
              :key="code"
              type="button"
              :aria-pressed="lang === code"
              class="tap-target rounded-full px-2.5 text-[11px] font-bold uppercase tracking-wide transition-colors duration-150"
              :class="lang === code ? 'bg-forest text-white' : 'text-forest-muted hover:text-forest'"
              @click="setLang(code)"
            >{{ code === 'en' ? 'EN' : 'FIL' }}</button>
          </div>
          <Link
            :href="route('login')"
            class="tap-target flex items-center rounded-xl px-3 text-sm font-semibold text-forest transition-colors duration-150 hover:bg-cream"
          >{{ t('Log in') }}</Link>
        </div>

        <!-- mobile burger -->
        <button
          type="button"
          class="tap-target grid place-items-center rounded-xl border border-warm bg-white text-forest-dark transition-colors duration-150 hover:border-forest/40 hover:bg-cream/60 md:hidden"
          :aria-expanded="menuOpen"
          aria-controls="mobile-menu"
          :aria-label="menuOpen ? t('Close menu') : t('Open menu')"
          @click="menuOpen = !menuOpen"
        >
          <X v-if="menuOpen" class="h-5 w-5" />
          <Menu v-else class="h-5 w-5" />
        </button>
      </div>

      <!-- mobile panel -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="-translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-2 opacity-0"
      >
        <div
          v-if="menuOpen"
          id="mobile-menu"
          class="border-t border-warm bg-white px-4 pb-5 pt-3 md:hidden"
        >
          <a
            v-for="l in navLinks"
            :key="l.href"
            :href="l.href"
            class="tap-target flex items-center rounded-xl px-3 text-base font-semibold text-forest-dark transition-colors duration-150 hover:bg-cream/70"
            @click="closeMenu"
          >{{ t(l.label) }}</a>

          <div class="mt-3 flex items-center justify-between gap-3 border-t border-warm pt-3">
            <span class="text-xs font-bold uppercase tracking-widest text-forest-muted">{{ t('Language') }}</span>
            <div class="flex items-center rounded-full border border-warm bg-white p-0.5" role="group" :aria-label="t('Language')">
              <button
                v-for="code in ['en', 'fil']"
                :key="code"
                type="button"
                :aria-pressed="lang === code"
                class="tap-target rounded-full px-3 text-[11px] font-bold uppercase tracking-wide transition-colors duration-150"
                :class="lang === code ? 'bg-forest text-white' : 'text-forest-muted hover:text-forest'"
                @click="setLang(code)"
              >{{ code === 'en' ? 'EN' : 'FIL' }}</button>
            </div>
          </div>

          <div class="mt-3 flex flex-col gap-2">
            <Link
              :href="route('login')"
              class="tap-target flex items-center justify-center rounded-xl border border-warm bg-white px-4 text-sm font-semibold text-forest-dark transition-colors duration-150 hover:border-forest/40 hover:bg-cream/60"
              @click="closeMenu"
            >{{ t('Log in') }}</Link>
            <Link
              v-if="canRegister"
              :href="route('register')"
              class="tap-target flex items-center justify-center rounded-xl bg-gradient-to-b from-mango to-[#d97706] px-4 text-sm font-semibold text-white ring-1 ring-inset ring-white/25 transition-colors duration-150 hover:brightness-105 active:scale-[0.99]"
              @click="closeMenu"
            >{{ t('Start free trial') }}</Link>
          </div>
        </div>
      </transition>
    </nav>

    <!-- ------------------------------------------------------------ hero -->
    <header id="top" class="relative mx-auto max-w-5xl px-4 pb-14 pt-12">
      <div class="grid items-center gap-10 md:grid-cols-[1.05fr_0.95fr]">
        <div>
          <p class="text-xs font-bold uppercase tracking-widest text-forest">
            {{ t('For sari-sari stores, karinderyas and small shops') }}
          </p>

          <h1 class="mt-3 flex flex-wrap gap-x-[0.24em] text-4xl font-extrabold leading-[1.08] tracking-tight text-forest-dark sm:text-5xl">
            <span
              v-for="(w, i) in heroWords"
              :key="i"
              v-motion
              :initial="{ opacity: 0, y: 16 }"
              :enter="{ opacity: 1, y: 0, transition: spring(120 + i * 70) }"
              class="inline-block"
              :class="i === heroWords.length - 1 ? 'text-mango' : ''"
            >{{ w }}</span>
          </h1>

          <p class="mt-4 max-w-md text-base leading-relaxed text-forest-muted">
            {{ t('CashIko turns the notebook under the counter into a clear daily picture: stock, sales, utang, expenses — and the profit that is actually left.') }}
          </p>

          <div class="mt-6 flex flex-wrap items-center gap-3">
            <Link
              v-if="canRegister"
              :href="route('register')"
              class="flex h-12 items-center rounded-xl bg-gradient-to-b from-mango to-[#d97706] px-6 font-semibold text-white shadow-[0_12px_30px_-12px_hsl(26_88%_46%/0.75)] ring-1 ring-inset ring-white/25 transition duration-200 ease-out hover:-translate-y-0.5 hover:brightness-105 active:translate-y-0 active:scale-[0.98]"
            >
              {{ t('Start 14-day free trial') }} <ArrowRight class="ml-2 h-4 w-4" />
            </Link>
            <a
              href="#how"
              class="flex h-12 items-center rounded-xl border border-warm bg-white px-5 font-semibold text-forest-dark transition duration-200 ease-out hover:-translate-y-0.5 hover:border-forest/40 hover:bg-cream/60 active:translate-y-0 active:scale-[0.98]"
            >{{ t('See how it works') }}</a>
          </div>

          <p class="mt-3 text-sm text-forest-muted">{{ t('No credit card. Cancel or switch plans in Settings.') }}</p>
        </div>

        <!-- illustrative product preview -->
        <div class="relative [perspective:1200px]">
          <div
            class="pointer-events-none absolute -inset-6 rounded-[2rem] bg-gradient-to-br from-forest/10 via-transparent to-mango/15 blur-2xl"
            aria-hidden="true"
          />
          <div
            class="transition-transform duration-200 ease-out will-change-transform"
            :style="{ transform: `rotateY(${tilt.x}deg) rotateX(${tilt.y}deg)` }"
            @pointermove="onTilt"
            @pointerleave="resetTilt"
          >
            <div
              v-motion
              :initial="{ opacity: 0, y: 28 }"
              :enter="{ opacity: 1, y: 0, transition: { type: 'spring', stiffness: 90, damping: 18, delay: 420 } }"
              class="relative overflow-hidden rounded-3xl border border-warm bg-white shadow-cardlg"
            >
            <div class="flex items-center justify-between border-b border-warm px-5 py-3">
              <span class="text-sm font-bold text-forest-dark">Home</span>
              <span class="text-xs font-semibold text-forest-muted">Tuesday · 6:12 PM</span>
            </div>
            <div class="px-5 py-4">
              <div class="text-xs font-semibold text-forest-muted">Sales today</div>
              <div class="mt-0.5 text-3xl font-extrabold tracking-tight text-forest-dark">
                <CountUp :to="2415" :decimals="2" prefix="₱" />
              </div>

              <div class="mt-4 flex h-24 items-end gap-2">
                <div
                  v-for="(b, i) in bars"
                  :key="i"
                  class="group relative flex flex-1 flex-col items-center gap-1"
                >
                  <span
                    class="pointer-events-none absolute -top-7 whitespace-nowrap rounded-md bg-forest-dark px-1.5 py-0.5 text-[10px] font-bold text-white opacity-0 shadow-card transition-opacity duration-150 group-hover:opacity-100"
                  >₱{{ b.v }}</span>
                  <div
                    class="anim-growbar w-full origin-bottom rounded-t-md transition-[filter] duration-150 group-hover:brightness-110"
                    :class="i === 5 ? 'bg-mango' : 'bg-forest/80'"
                    :style="{ height: b.h + 'px', animationDelay: 0.5 + i * 0.09 + 's' }"
                  />
                  <span class="text-[10px] font-semibold text-forest-muted">{{ b.label }}</span>
                </div>
              </div>

              <div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="chip border-forest/20 bg-forest/5 text-forest">
                  <span class="anim-pulse inline-block h-1.5 w-1.5 rounded-full bg-mango" />
                  Aling Nena · ₱350 · due tomorrow
                </span>
              </div>
              <div class="mt-3 flex gap-2 pb-1">
                <span class="chip border-warm bg-cream text-forest-dark">Cash</span>
                <span class="chip border-warm bg-cream text-forest-dark">GCash</span>
                <span class="chip border-warm bg-cream text-forest-dark">Maya</span>
              </div>
            </div>
            </div>
          </div>

          <div class="anim-float pointer-events-none absolute -right-3 -top-5 hidden h-11 w-11 place-items-center rounded-2xl border border-warm bg-white shadow-card md:grid" style="animation-delay: 0.8s">
            <ShoppingBag class="h-5 w-5 text-forest" />
          </div>
          <div class="anim-float pointer-events-none absolute -bottom-5 -left-3 hidden h-11 w-11 place-items-center rounded-2xl border border-warm bg-white shadow-card md:grid" style="animation-delay: 1.6s">
            <ReceiptText class="h-5 w-5 text-mango" />
          </div>
        </div>
      </div>
    </header>

    <!-- ------------------------------------------------- rotating claims -->
    <section
      class="overflow-hidden border-y border-warm bg-forest-dark py-3"
      aria-label="What CashIko records"
    >
      <div class="anim-marquee flex w-max items-center hover:[animation-play-state:paused]">
        <template v-for="pass in 2" :key="pass">
          <span
            v-for="(word, i) in ticker"
            :key="`${pass}-${i}`"
            class="mr-8 flex items-center gap-8 whitespace-nowrap text-sm font-bold uppercase tracking-widest text-white/85"
            :aria-hidden="pass === 2 ? 'true' : undefined"
          >
            {{ t(word) }}
            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-mango" aria-hidden="true" />
          </span>
        </template>
      </div>
    </section>

    <!-- --------------------------------------------------- facts strip -->
    <section class="border-b border-warm bg-gradient-to-r from-cream/50 via-white to-cream/50" aria-label="CashIko at a glance">
      <dl class="mx-auto grid max-w-5xl grid-cols-2 divide-warm px-4 sm:grid-cols-4 sm:divide-x">
        <div v-for="(f, i) in facts" :key="f.value" class="px-2 py-5 text-center sm:px-4" :class="i < 2 ? 'border-b border-warm sm:border-b-0' : ''">
          <dt class="text-xl font-extrabold tracking-tight text-forest-dark">{{ t(f.value) }}</dt>
          <dd class="mt-0.5 text-xs font-medium text-forest-muted">{{ t(f.label) }}</dd>
        </div>
      </dl>
    </section>

    <!-- -------------------------------------------------------- problem -->
    <section class="mx-auto max-w-5xl px-4 py-16">
      <div class="grid gap-8 md:grid-cols-12">
        <div class="md:col-span-5">
          <p class="text-xs font-bold uppercase tracking-widest text-forest">{{ t('The problem') }}</p>
          <h2 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-forest-dark">{{ t(problemTitle) }}</h2>
        </div>
        <ol class="divide-y divide-warm md:col-span-7">
          <li v-for="p in problems" :key="p.n" class="flex gap-4 py-4 first:pt-0 last:pb-0">
            <span class="mt-0.5 text-sm font-bold tabular-nums text-mango">{{ p.n }}</span>
            <span class="text-base font-medium leading-relaxed text-forest-muted">{{ t(p.text) }}</span>
          </li>
        </ol>
      </div>
    </section>

    <!-- --------------------------------------------------- how it works -->
    <section id="how" class="scroll-mt-20 border-y border-warm bg-cream/30 py-16">
      <div class="mx-auto max-w-5xl px-4">
        <h2 class="text-3xl font-extrabold tracking-tight text-forest-dark">{{ t('Three taps, then the truth.') }}</h2>
        <p class="mt-2 max-w-lg text-base text-forest-muted">{{ t('From opening the app to a recorded sale: under ten seconds.') }}</p>

        <ol class="mt-8 grid gap-6 md:grid-cols-3 md:divide-x md:divide-warm">
          <li v-for="(s, i) in steps" :key="s.n" class="md:px-6 md:first:pl-0 md:last:pr-0">
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-extrabold tabular-nums text-mango">{{ s.n }}</span>
              <h3 class="text-base font-extrabold text-forest-dark">{{ t(s.title) }}</h3>
            </div>
            <p class="mt-2 text-sm leading-relaxed text-forest-muted">{{ t(s.body) }}</p>
          </li>
        </ol>
      </div>
    </section>

    <!-- ------------------------------------------------------- features -->
    <section id="features" class="mx-auto max-w-5xl scroll-mt-20 px-4 py-16">
      <p class="text-xs font-bold uppercase tracking-widest text-forest">{{ t('Features') }}</p>

      <div class="mt-6 space-y-14">
        <article
          v-for="(f, i) in features"
          :key="f.key"
          v-motion
          :initial="hidden"
          :visibleOnce="reveal"
          class="grid items-center gap-6 md:grid-cols-2"
          :class="i % 2 ? 'md:[&>*:first-child]:order-2' : ''"
        >
          <div class="md:px-4">
            <div class="mb-3 grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-forest/20 to-mango/10 text-forest">
              <component :is="f.icon" class="h-5 w-5" />
            </div>
            <h2 class="text-2xl font-extrabold leading-snug tracking-tight text-forest-dark">{{ t(f.title) }}</h2>
            <p v-if="f.key === 'stock'" class="mt-2 max-w-sm text-base leading-relaxed text-forest-muted">
              {{ t('Every sale counts down your stock. Low or out shows up on Home before a customer asks for it.') }}
            </p>
            <p v-else-if="f.key === 'utang'" class="mt-2 max-w-sm text-base leading-relaxed text-forest-muted">
              {{ t('Who owes what, since when, and when it is due. Payments knock the balance down.') }}
            </p>
            <p v-else class="mt-2 max-w-sm text-base leading-relaxed text-forest-muted">
              {{ t('Sales − cost − expenses. The number that tells you whether the day was worth opening.') }}
            </p>
          </div>

          <div class="rounded-2xl border border-warm bg-gradient-to-b from-white to-cream/50 p-4 shadow-card">
            <template v-if="f.key === 'stock'">
              <div class="flex flex-wrap gap-2">
                <span class="chip border-forest/20 bg-forest/5 text-forest">Instant noodles · 24 ✓</span>
                <span class="chip border-mango/40 bg-mango/10 text-forest-dark">Softdrinks · 3 ⚠</span>
                <span class="chip border-red-200 bg-red-50 text-red-700">Coffee · 0 ✕</span>
                <span class="chip border-forest/20 bg-forest/5 text-forest">Rice · 12 ✓</span>
              </div>
              <p class="mt-3 text-xs font-semibold text-forest-muted">Illustrative stock levels.</p>
            </template>

            <template v-else-if="f.key === 'utang'">
              <ul class="space-y-2 text-sm font-semibold">
                <li class="flex items-center justify-between rounded-xl bg-cream px-3 py-2">
                  <span class="text-forest-dark">Aling Nena</span>
                  <span class="flex items-center gap-2 text-forest-muted">
                    <span class="anim-pulse inline-block h-1.5 w-1.5 rounded-full bg-mango" />₱350 · due tomorrow
                  </span>
                </li>
                <li class="flex items-center justify-between rounded-xl bg-cream px-3 py-2">
                  <span class="text-forest-dark">Kuya Ben</span>
                  <span class="flex items-center gap-2 text-red-700">
                    <span class="anim-pulse inline-block h-1.5 w-1.5 rounded-full bg-red-500" style="animation-delay: 0.6s" />₱120 · 2 days late
                  </span>
                </li>
              </ul>
            </template>

            <template v-else>
              <div class="space-y-1.5 font-semibold tabular-nums">
                <div class="flex justify-between text-sm text-forest-muted"><span>Sales</span><span>₱2,415.00</span></div>
                <div class="flex justify-between text-sm text-forest-muted"><span>− Cost</span><span>₱1,180.00</span></div>
                <div class="flex justify-between text-sm text-forest-muted"><span>− Expenses</span><span>₱340.00</span></div>
                <div class="flex justify-between border-t border-warm pt-1.5 text-base text-forest-dark">
                  <span class="font-extrabold">Profit</span>
                  <span class="text-peso font-extrabold"><CountUp :to="895" prefix="₱" :duration="1200" /></span>
                </div>
              </div>
              <p class="mt-3 text-xs font-semibold text-forest-muted">Illustrative figures.</p>
            </template>
          </div>
        </article>
      </div>
    </section>

    <!-- ------------------------------------------------------ bilingual -->
    <section class="border-y border-warm bg-cream/30 py-14">
      <div class="mx-auto grid max-w-5xl items-center gap-8 px-4 md:grid-cols-2">
        <div>
          <h2 class="text-2xl font-extrabold leading-snug tracking-tight text-forest-dark">{{ t('English or Filipino. Your call, any time.') }}</h2>
          <p class="mt-2 max-w-md text-base leading-relaxed text-forest-muted">
            {{ t('Every screen has a Filipino version, and the switch is in Settings. Try it here first.') }}
          </p>
        </div>

        <div class="rounded-2xl border border-warm bg-white p-5 shadow-card">
          <div class="flex items-center gap-2">
            <button
              v-for="code in ['en', 'fil']"
              :key="code"
              type="button"
              :aria-pressed="lang === code"
              class="tap-target rounded-xl px-4 text-sm font-bold transition-colors duration-150"
              :class="lang === code ? 'bg-forest text-white' : 'border border-warm bg-white text-forest-dark hover:border-forest/40'"
              @click="setLang(code)"
            >{{ code === 'en' ? 'English' : 'Filipino' }}</button>
          </div>
          <p class="mt-4 border-t border-warm pt-4 text-lg font-extrabold leading-snug text-forest-dark">
            {{ t('Three taps, then the truth.') }}
          </p>
        </div>
      </div>
    </section>

    <!-- -------------------------------------------------------- pricing -->
    <section id="pricing" class="mx-auto max-w-5xl scroll-mt-20 px-4 py-16">
      <div class="max-w-xl">
        <p class="text-xs font-bold uppercase tracking-widest text-forest">{{ t('Pricing') }}</p>
        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-forest-dark">{{ t('Less than a kilo of rice a day.') }}</h2>
        <p class="mt-2 text-base leading-relaxed text-forest-muted">
          {{ t('Every plan includes the complete books: stock, sales, utang, expenses and real profit. Premium adds branches and the assistant.') }}
        </p>
      </div>

      <div class="mt-8 grid gap-5 md:grid-cols-2">
        <div
          v-for="p in plans"
          :key="p.id"
          class="flex flex-col rounded-2xl border p-6 transition duration-200 ease-out hover:-translate-y-1"
          :class="p.featured
            ? 'border-forest/30 bg-gradient-to-b from-forest-deep to-forest-dark text-white shadow-cardlg hover:shadow-[0_28px_60px_-24px_hsl(150_18%_10%/0.65)]'
            : 'border-warm bg-white shadow-card hover:border-forest/30 hover:shadow-cardlg'"
        >
          <div class="flex items-baseline justify-between">
            <h3 class="text-lg font-extrabold tracking-tight" :class="p.featured ? 'text-white' : 'text-forest-dark'">{{ t(p.name) }}</h3>
            <span
              class="text-[11px] font-bold uppercase tracking-widest"
              :class="p.featured ? 'text-white/60' : 'text-forest-muted'"
            >{{ t(p.tag) }}</span>
          </div>

          <div class="mt-3 flex items-baseline gap-1">
            <span class="text-3xl font-extrabold tracking-tight" :class="p.featured ? 'text-white' : 'text-forest-dark'">₱{{ p.price }}</span>
            <span class="text-sm font-semibold" :class="p.featured ? 'text-white/70' : 'text-forest-muted'">{{ t('/ month') }}</span>
          </div>

          <ul class="mt-5 flex-1 space-y-2.5 text-sm" :class="p.featured ? 'text-white/85' : 'text-forest-muted'">
            <li v-for="b in p.bullets" :key="b" class="flex gap-2">
              <Check class="mt-0.5 h-4 w-4 shrink-0" :class="p.featured ? 'text-mango' : 'text-forest'" />
              <span>{{ t(b) }}</span>
            </li>
          </ul>

          <Link
            v-if="canRegister"
            :href="route('register')"
            class="mt-6 flex h-12 items-center justify-center rounded-xl font-semibold transition duration-200 ease-out hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98]"
            :class="p.featured
              ? 'bg-gradient-to-b from-mango to-[#d97706] text-white ring-1 ring-inset ring-white/25 hover:brightness-105'
              : 'border border-forest/25 bg-forest/5 text-forest hover:bg-forest/10'"
          >{{ t('Start 14-day trial') }}</Link>
        </div>
      </div>

      <p class="mt-4 text-sm text-forest-muted">{{ t('14 days free · no credit card · switch or cancel from Settings') }}</p>
    </section>

    <!-- ------------------------------------------------------- founders -->
    <section class="border-y border-warm bg-cream/30 py-14">
      <div class="mx-auto max-w-3xl px-4">
        <blockquote class="border-l-2 border-mango pl-5">
          <p class="text-lg font-semibold leading-relaxed text-forest-dark sm:text-xl">
            {{ t('“Every store already knows how to keep records. They just deserve software that does the maths without asking them to become accountants.”') }}
          </p>
          <footer class="mt-3 text-sm font-bold uppercase tracking-widest text-forest-muted">{{ t('The CashIko team') }}</footer>
        </blockquote>
      </div>
    </section>

    <!-- ------------------------------------------------------------ FAQ -->
    <section id="faq" class="mx-auto max-w-5xl scroll-mt-20 px-4 py-16">
      <div class="grid gap-8 md:grid-cols-12">
        <div class="md:col-span-4">
          <p class="text-xs font-bold uppercase tracking-widest text-forest">{{ t('Frequently asked') }}</p>
          <h2 class="mt-2 text-2xl font-extrabold leading-snug tracking-tight text-forest-dark">{{ t('The things store owners ask first.') }}</h2>
        </div>

        <div class="divide-y divide-warm border-y border-warm md:col-span-8">
          <details v-for="(f, i) in faqs" :key="i" class="group">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-base font-semibold text-forest-dark">
              <span>{{ t(f.q) }}</span>
              <ChevronDown class="h-4 w-4 shrink-0 text-forest-muted transition-transform duration-150 group-open:rotate-180" />
            </summary>
            <p class="pb-4 pr-8 text-sm leading-relaxed text-forest-muted">{{ t(f.a) }}</p>
          </details>
        </div>
      </div>
    </section>

    <!-- ------------------------------------------------------- final CTA -->
    <section class="mx-auto max-w-5xl px-4 pb-16">
      <div
        v-motion
        :initial="hidden"
        :visibleOnce="reveal"
        class="relative flex flex-col items-center gap-3 overflow-hidden rounded-3xl px-6 py-12 text-center text-white ring-1 ring-inset ring-white/10"
        style="background-image: radial-gradient(70% 60% at 18% 0%, rgba(255,255,255,0.12), transparent 65%), radial-gradient(rgba(255,255,255,0.07) 1px, transparent 1px), linear-gradient(135deg, hsl(145 48% 24%), hsl(150 30% 13%)); background-size: auto, 18px 18px, auto;"
      >
        <h2 class="max-w-md text-2xl font-extrabold tracking-tight sm:text-3xl">{{ t('Know what you really earn — starting today.') }}</h2>
        <p class="max-w-sm text-sm text-white/80">{{ t('Fourteen days free. No credit card. Record your first sale in under a minute.') }}</p>
        <div class="mt-2 flex flex-wrap items-center justify-center gap-3">
          <Link
            v-if="canRegister"
            :href="route('register')"
            class="flex h-12 items-center rounded-xl bg-gradient-to-b from-mango to-[#d97706] px-6 font-semibold text-white shadow-[0_12px_30px_-10px_hsl(26_88%_46%/0.7)] ring-1 ring-inset ring-white/25 transition duration-200 ease-out hover:-translate-y-0.5 hover:brightness-105 active:translate-y-0 active:scale-[0.98]"
          >{{ t('Start free trial') }}</Link>
          <a
            href="#pricing"
            class="flex h-12 items-center rounded-xl border border-white/25 px-5 font-semibold text-white transition duration-200 ease-out hover:-translate-y-0.5 hover:bg-white/10 active:translate-y-0 active:scale-[0.98]"
          >{{ t('See pricing') }}</a>
        </div>
      </div>
    </section>

    <!-- --------------------------------------------------------- footer -->
    <footer class="border-t border-warm bg-gradient-to-b from-white to-cream/40 py-8">
      <div class="mx-auto flex max-w-5xl flex-col items-center gap-3 px-4 text-center">
        <div class="flex items-center gap-2">
          <img src="/images/cashiko-icon.svg" alt="" class="h-7 w-auto" draggable="false" />
          <span class="font-extrabold tracking-tight text-forest-dark">CashIko</span>
        </div>
        <p class="text-xs text-forest-muted">{{ t('The business companion for Filipino micro-businesses.') }}</p>
        <nav class="flex flex-wrap items-center justify-center gap-4 text-xs font-semibold text-forest-muted">
          <a href="#pricing" class="transition-colors duration-150 hover:text-forest">{{ t('Pricing') }}</a>
          <a href="#faq" class="transition-colors duration-150 hover:text-forest">{{ t('FAQ') }}</a>
          <Link :href="route('login')" class="transition-colors duration-150 hover:text-forest">{{ t('Log in') }}</Link>
        </nav>
        <p class="text-[11px] text-forest-muted">© {{ new Date().getFullYear() }} CashIko</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}
@keyframes growbar {
  from { transform: scaleY(0); }
  to { transform: scaleY(1); }
}
@keyframes pulse-dot {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.6); opacity: 0.4; }
}
@keyframes marquee {
  from { transform: translateX(0); }
  to { transform: translateX(-50%); }
}

.anim-float { animation: float 5s ease-in-out infinite; }
.anim-growbar {
  animation: growbar 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
  transform-origin: bottom;
}
.anim-pulse { animation: pulse-dot 1.8s ease-in-out infinite; }
.anim-marquee { animation: marquee 32s linear infinite; }

summary::-webkit-details-marker { display: none; }

@media (prefers-reduced-motion: reduce) {
  .anim-float,
  .anim-growbar,
  .anim-pulse,
  .anim-marquee {
    animation: none !important;
  }
  .anim-marquee { transform: none !important; }
}
</style>
