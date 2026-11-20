<script setup>
/**
 * Public marketing page (§8.1): premium animated landing, English copy.
 * Motion: @vueuse/motion (scroll reveals via :visibleOnce, staggered hero
 * springs) + zero-dep CountUp + pure-CSS ambient keyframes (marquee, growbar,
 * pulse, float) — all disabled under prefers-reduced-motion.
 */
import { Head, Link } from '@inertiajs/vue3'
import { Boxes, TrendingUp, HandCoins, ArrowRight, ShoppingBag, ReceiptText } from 'lucide-vue-next'
import CountUp from '@/Components/CountUp.vue'

defineProps({ canRegister: Boolean, laravelVersion: String })

/* Hero headline: word-by-word spring reveal. */
const heroWords = 'Know what you really earn.'.split(' ')
const spring = (delay = 0) => ({ type: 'spring', stiffness: 160, damping: 20, delay })

const pillars = [
  {
    key: 'stock', icon: Boxes, title: 'Stock',
    copy: 'Stop listing on paper. See at once what is low, what is out, and what is not selling.',
  },
  {
    key: 'kita', icon: TrendingUp, title: 'Profit',
    copy: 'No guessing your margin. Profit = Sales − Cost − Expenses. Real profit, every day.',
  },
  {
    key: 'utang', icon: HandCoins, title: 'Debts',
    copy: 'No more lending from memory. Balances that carry over, due dates, and a quiet reminder.',
  },
]

const beforeAfter = [
  { label: 'Before', good: false, items: ['Market list scribbled on paper', 'Expenses kept up in your head', 'Debts that slip through the cracks', 'Profit: no idea'] },
  { label: 'With CashIko', good: true, items: ['Sales and stock in one tap', 'Every peso of expense logged', 'Debts with due dates and reminders', 'Profit: right there on Home'] },
]

/* Ticker items (illustrative product claims, same copy family as pillars). */
const ticker = [
  'Sales in one tap', 'Debts with due dates', 'Stock that never runs short',
  'Profit — real, every day', 'Every peso of expense logged', '3-tap recording',
]

/* 7-day sales bars for the product preview (illustrative demo values). */
const bars = [
  { h: 42, label: 'M' }, { h: 58, label: 'T' }, { h: 35, label: 'W' },
  { h: 70, label: 'T' }, { h: 52, label: 'F' }, { h: 88, label: 'S' },
  { h: 64, label: 'S' },
]
</script>

<template>
  <Head title="Know what you really earn" />
  <div class="min-h-screen overflow-x-hidden bg-surface">
    <!-- Nav -->
    <nav
      v-motion
      :initial="{ opacity: 0, y: -12 }"
      :enter="{ opacity: 1, y: 0, transition: spring() }"
      class="sticky top-0 z-40 border-b border-warm bg-gradient-to-b from-white/95 to-white/80 backdrop-blur"
    >
      <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
        <div class="flex items-center gap-2">
          <img src="/images/cashiko-icon.svg" alt="CashIko" class="h-9 w-auto" draggable="false" />
          <span class="text-lg font-extrabold text-forest-dark">CashIko</span>
        </div>
        <Link :href="route('login')" class="tap-target flex items-center rounded-xl px-4 font-semibold text-forest hover:bg-cream">Log in</Link>
      </div>
    </nav>

    <!-- Hero -->
    <header
      class="relative mx-auto max-w-5xl px-4 pb-16 pt-14"
      style="background-image: radial-gradient(58% 52% at 80% 8%, hsl(145 48% 26% / 0.10), transparent 72%), radial-gradient(46% 44% at 2% 96%, hsl(26 88% 56% / 0.07), transparent 72%);"
    >
      <p
        v-motion
        :initial="{ opacity: 0, y: 10 }"
        :enter="{ opacity: 1, y: 0, transition: spring(60) }"
        class="mb-3 text-xs font-bold uppercase tracking-widest text-forest"
      >
        For owners who refuse to run on guesswork
      </p>
      <h1 class="flex max-w-lg flex-wrap gap-x-[0.26em] text-4xl font-extrabold leading-[1.1] text-forest-dark sm:text-5xl">
        <span
          v-for="(w, i) in heroWords"
          :key="i"
          v-motion
          :initial="{ opacity: 0, y: 16 }"
          :enter="{ opacity: 1, y: 0, transition: spring(120 + i * 70) }"
          class="inline-block"
          :class="i === heroWords.length - 1
            ? 'bg-gradient-to-br from-mango via-[#e5731f] to-[#b45309] bg-clip-text text-transparent'
            : ''"
        >{{ w }}</span>
      </h1>
      <p
        v-motion
        :initial="{ opacity: 0, y: 14 }"
        :enter="{ opacity: 1, y: 0, transition: spring(560) }"
        class="mt-4 max-w-md text-base text-forest-muted"
      >
        Stock, sales, expenses, and debts — in one app built for sari-sari stores. 3 taps to record, no messy formulas.
      </p>
      <div
        v-motion
        :initial="{ opacity: 0, y: 14 }"
        :enter="{ opacity: 1, y: 0, transition: spring(680) }"
        class="mt-6 flex flex-wrap items-center gap-3"
      >
        <Link
          :href="route('register')"
          class="flex h-12 items-center rounded-xl bg-gradient-to-b from-forest to-forest-deep px-6 font-semibold text-white shadow-[0_10px_30px_-10px_hsl(145_48%_26%/0.65)] ring-1 ring-inset ring-white/20 transition hover:from-forest-dark hover:to-forest-deep active:scale-[0.98]"
        >
          Get started <ArrowRight class="ml-2 h-4 w-4" />
        </Link>
        <span class="text-sm text-forest-muted">Free trial: 14 days · No credit card</span>
      </div>

      <!-- Live product preview -->
      <div class="relative mt-12">
        <div class="pointer-events-none absolute inset-x-10 top-20 h-56 rounded-full bg-gradient-to-r from-forest/20 via-cream/0 to-mango/20 blur-3xl" aria-hidden="true" />
        <div
          class="anim-float pointer-events-none absolute -top-6 right-2 hidden h-11 w-11 place-items-center rounded-2xl border border-warm bg-gradient-to-b from-white to-cream/60 shadow-card md:grid" style="animation-delay: 0.8s"
        >
          <ShoppingBag class="h-5 w-5 text-forest" />
        </div>
        <div
          class="anim-float pointer-events-none absolute -bottom-4 left-2 hidden h-11 w-11 place-items-center rounded-2xl border border-warm bg-gradient-to-b from-white to-cream/60 shadow-card md:grid" style="animation-delay: 1.6s"
        >
          <ReceiptText class="h-5 w-5 text-mango" />
        </div>

        <div
          v-motion
          :initial="{ opacity: 0, y: 32 }"
          :enter="{ opacity: 1, y: 0, transition: { type: 'spring', stiffness: 90, damping: 18, delay: 720 } }"
          class="relative z-10 mx-auto max-w-md overflow-hidden rounded-3xl border border-warm bg-gradient-to-b from-white via-white to-cream/70 shadow-[0_24px_60px_-20px_hsl(145_48%_26%/0.28)]"
        >
          <div class="flex items-center justify-between border-b border-warm px-5 py-3">
            <span class="text-sm font-bold text-forest-dark">Home</span>
            <span class="text-xs font-semibold text-forest-muted">Tuesday · 6:12 PM</span>
          </div>
          <div class="px-5 py-4">
            <div class="text-xs font-semibold text-forest-muted">Sales today</div>
            <div class="mt-0.5 text-3xl font-extrabold text-forest-dark">
              <CountUp :to="2415.5" :decimals="2" prefix="₱" />
            </div>
            <!-- 7-day bars -->
            <div class="mt-4 flex h-24 items-end gap-2">
              <div v-for="(b, i) in bars" :key="i" class="flex flex-1 flex-col items-center gap-1">
                <div
                  class="anim-growbar w-full origin-bottom rounded-t-md"
                  :class="i === 5 ? 'bg-mango' : 'bg-forest/80'"
                  :style="{ height: b.h + 'px', animationDelay: 0.9 + i * 0.09 + 's' }"
                />
                <span class="text-[10px] font-semibold text-forest-muted">{{ b.label }}</span>
              </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-2">
              <span class="chip border-forest/20 bg-forest/5 text-forest">
                <span class="anim-pulse inline-block h-1.5 w-1.5 rounded-full bg-mango" />
                Aling Nena's debt · ₱350 · due tomorrow
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
    </header>

    <!-- Ticker -->
    <section class="border-y border-warm bg-gradient-to-r from-cream/40 via-white to-cream/40 py-3.5" aria-hidden="true">
      <div
        class="marquee-mask overflow-hidden"
        style="mask-image: linear-gradient(to right, transparent, black 12%, black 88%, transparent); -webkit-mask-image: linear-gradient(to right, transparent, black 12%, black 88%, transparent)"
      >
        <div class="anim-marquee flex w-max hover:[animation-play-state:paused]">
          <div v-for="n in 2" :key="n" class="flex items-center gap-8 pr-8">
            <span v-for="t in ticker" :key="t" class="whitespace-nowrap text-sm font-semibold text-forest-muted">
              {{ t }} <span class="ml-8 text-forest/60">·</span>
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- MSME stat -->
    <section class="mx-auto max-w-5xl px-4 pt-14">
      <div
        v-motion
        :initial="{ opacity: 0, y: 24 }"
        :visibleOnce="{ opacity: 1, y: 0, transition: spring(0) }"
        class="mx-auto max-w-xl text-center"
      >
        <div class="bg-gradient-to-br from-forest-dark via-forest to-[#397f5c] bg-clip-text text-6xl font-extrabold tracking-tight text-transparent sm:text-7xl">
          <CountUp :to="99" suffix="%" :duration="1600" />
        </div>
        <p class="mt-3 text-base font-semibold text-forest-muted">
          of businesses in the Philippines are MSMEs — CashIko is built for them.
        </p>
      </div>
    </section>

    <!-- Pillars: asymmetric alternating rows with mini-scenes -->
    <section class="mx-auto max-w-5xl px-4 py-14">
      <div class="space-y-10">
        <article
          v-for="(p, i) in pillars"
          :key="p.key"
          v-motion
          :initial="{ opacity: 0, y: 28 }"
          :visibleOnce="{ opacity: 1, y: 0, transition: spring(0) }"
          class="grid items-center gap-6 md:grid-cols-2"
          :class="i % 2 ? 'md:[&>*:first-child]:order-2' : ''"
        >
          <div class="md:px-6">
            <div class="mb-3 grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-forest/20 to-mango/10 text-forest">
              <component :is="p.icon" class="h-5 w-5" />
            </div>
            <h2 class="text-2xl font-extrabold text-forest-dark">{{ p.title }}</h2>
            <p class="mt-2 max-w-sm text-base leading-relaxed text-forest-muted">{{ p.copy }}</p>
          </div>

          <!-- Mini-scene -->
          <div class="rounded-2xl border border-warm bg-gradient-to-b from-white to-cream/50 p-4 shadow-card">
            <template v-if="p.key === 'stock'">
              <div class="flex flex-wrap gap-2">
                <span class="chip border-forest/20 bg-forest/5 text-forest">Instant noodles · 24 ✓</span>
                <span class="chip border-mango/40 bg-mango/10 text-forest-dark">Softdrinks · 3 ⚠</span>
                <span class="chip border-red-200 bg-red-50 text-red-700">Coffee · 0 ✕</span>
                <span class="chip border-forest/20 bg-forest/5 text-forest">Rice · 12 ✓</span>
              </div>
              <p class="mt-3 text-xs font-semibold text-forest-muted">Low or dead stock — you see it right away.</p>
            </template>
            <template v-else-if="p.key === 'kita'">
              <div class="space-y-1.5 font-semibold tabular-nums">
                <div class="flex justify-between text-sm text-forest-muted"><span>Sales</span><span>₱2,415.00</span></div>
                <div class="flex justify-between text-sm text-forest-muted"><span>− Cost</span><span>₱1,180.00</span></div>
                <div class="flex justify-between text-sm text-forest-muted"><span>− Expenses</span><span>₱340.00</span></div>
                <div class="flex justify-between border-t border-warm pt-1.5 text-base text-forest-dark">
                  <span class="font-extrabold">Profit</span>
                  <span class="text-peso font-extrabold"><CountUp :to="895" prefix="₱" :duration="1200" /></span>
                </div>
              </div>
            </template>
            <template v-else>
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
          </div>
        </article>
      </div>
    </section>

    <!-- Before / After -->
    <section class="mx-auto max-w-5xl px-4 pb-14">
      <h2
        v-motion
        :initial="{ opacity: 0, y: 20 }"
        :visibleOnce="{ opacity: 1, y: 0, transition: spring(0) }"
        class="text-2xl font-extrabold text-forest-dark"
      >
        From notebook to CashIko
      </h2>
      <div class="mt-5 grid gap-4 md:grid-cols-2">
        <div
          v-for="(col, c) in beforeAfter"
          :key="col.label"
          v-motion
          :initial="{ opacity: 0, y: 26 }"
          :visibleOnce="{ opacity: 1, y: 0, transition: spring(c * 120) }"
          class="rounded-2xl border p-5"
          :class="col.good
            ? 'border-forest/25 bg-gradient-to-br from-forest/10 via-forest/5 to-mango/10 shadow-[0_10px_30px_-16px_hsl(145_48%_26%/0.35)]'
            : 'border-warm bg-gradient-to-br from-white to-cream/40'"
        >
          <div class="text-xs font-bold uppercase tracking-widest" :class="col.good ? 'text-forest' : 'text-stone-500'">
            {{ col.label }}
          </div>
          <ul class="mt-3 space-y-2 text-sm">
            <li
              v-for="(item, i) in col.items"
              :key="item"
              v-motion
              :initial="{ opacity: 0, x: col.good ? 18 : -18 }"
              :visibleOnce="{ opacity: 1, x: 0, transition: spring(c * 120 + 150 + i * 60) }"
              class="flex gap-2"
              :class="col.good ? 'font-medium text-forest-dark' : 'text-forest-muted'"
            >
              <span :class="col.good ? 'text-emerald-600' : 'text-red-500'">{{ col.good ? '✓' : '✕' }}</span>{{ item }}
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="mx-auto max-w-5xl px-4 pb-16">
      <div
        v-motion
        :initial="{ opacity: 0, y: 30 }"
        :visibleOnce="{ opacity: 1, y: 0, transition: spring(0) }"
        class="relative flex flex-col items-center gap-3 overflow-hidden rounded-3xl px-6 py-12 text-center text-white ring-1 ring-inset ring-white/10"
        style="background-image: radial-gradient(70% 60% at 18% 0%, rgba(255,255,255,0.12), transparent 65%), radial-gradient(rgba(255,255,255,0.07) 1px, transparent 1px), linear-gradient(135deg, hsl(145 48% 24%), hsl(150 30% 13%)); background-size: auto, 18px 18px, auto;"
      >
        <h3 class="max-w-sm text-2xl font-extrabold sm:text-3xl">Ready? See your business clearly today.</h3>
        <Link :href="route('register')" class="mt-2 flex h-12 items-center rounded-xl bg-gradient-to-b from-mango to-[#d97706] px-6 font-semibold text-white shadow-[0_12px_30px_-10px_hsl(26_88%_46%/0.7)] ring-1 ring-inset ring-white/25 transition hover:brightness-105 active:scale-[0.98]">
          Create an account
        </Link>
        <span class="text-xs font-medium text-white/80">Free trial: 14 days · No credit card</span>
      </div>
    </section>

    <footer class="border-t border-warm bg-gradient-to-b from-white to-cream/40 py-8">
      <div class="mx-auto flex max-w-5xl flex-col items-center gap-2 px-4 text-center">
        <div class="flex items-center gap-2">
          <img src="/images/cashiko-icon.svg" alt="CashIko" class="h-7 w-auto" draggable="false" />
          <span class="font-extrabold text-forest-dark">CashIko</span>
        </div>
        <p class="text-xs text-forest-muted">The business companion for Filipino micro-businesses.</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}
@keyframes marquee {
  from { transform: translateX(0); }
  to { transform: translateX(-50%); }
}
@keyframes growbar {
  from { transform: scaleY(0); }
  to { transform: scaleY(1); }
}
@keyframes pulse-dot {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.6); opacity: 0.4; }
}

.anim-float { animation: float 5s ease-in-out infinite; }
.anim-marquee { animation: marquee 28s linear infinite; }
.anim-growbar {
  animation: growbar 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
  transform-origin: bottom;
}
.anim-pulse { animation: pulse-dot 1.8s ease-in-out infinite; }

@media (prefers-reduced-motion: reduce) {
  .anim-float,
  .anim-marquee,
  .anim-growbar,
  .anim-pulse {
    animation: none !important;
  }
}
</style>
