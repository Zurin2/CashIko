<script setup>
/** Public marketing page (§8.1): spec copy, Taglish. */
import { Head, Link } from '@inertiajs/vue3'
import { Boxes, TrendingUp, HandCoins, ArrowRight } from 'lucide-vue-next'

defineProps({ canRegister: Boolean, laravelVersion: String })

const pillars = [
  { icon: Boxes, title: 'Stock', copy: 'Huwalag mag-lista sa papel. Nakikita mo agad ang kulang, ang ubos, at ang patay na stock.' },
  { icon: TrendingUp, title: 'Kita', copy: 'Hindi kaha ang tutuboin. Kita = Benta − Kapalit − Gastos. Totoong kita, bawat araw.' },
  { icon: HandCoins, title: 'Utang', copy: 'Mula libreta pahawakan na. May balanseng naglalakbay, due date, at matamlay na alarm.' },
]

const beforeAfter = [
  { label: 'Libreta today', items: ['1 sinturon ng palengke listahan', 'Gastos naka-utaas sa utak', 'Utang na nakagalas sa kap sobra', 'Kita: hindi alam'] },
  { label: 'CashIko', items: ['Benta at stock sa isang tap', 'Gastos na naka-log bawat salapi', 'Utang na may due date at reminder', 'Kita: nakita agad sa Home'] },
]
</script>

<template>
  <Head title="Alamin ang tunay mong kita" />
  <div class="min-h-screen bg-surface">
    <nav class="sticky top-0 z-40 border-b border-warm bg-white/90 backdrop-blur">
      <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
        <div class="flex items-center gap-2">
          <div class="grid h-9 w-9 place-items-center rounded-xl bg-forest text-lg font-extrabold text-white">C</div>
          <span class="text-lg font-extrabold text-forest-dark">CashIko</span>
        </div>
        <Link :href="route('login')" class="tap-target flex items-center rounded-xl px-4 font-semibold text-forest hover:bg-cream">Log in</Link>
      </div>
    </nav>

    <header class="mx-auto max-w-5xl px-4 pb-14 pt-12">
      <p class="mb-2 text-xs font-bold uppercase tracking-widest text-mango">Para sa negosyanteng hindi magpapa-ligaya sa kutob</p>
      <h1 class="max-w-[18rem] text-4xl font-extrabold leading-[1.08] text-forest-dark sm:max-w-md sm:text-5xl">Alamin ang tunay mong kita.</h1>
      <p class="mt-4 max-w-md text-base text-forest-muted">
        Stock, benta, gastos, at utang — sa isang app na sari-sari friendly. 3 tap para mag-record, walang kalabong formulas.
      </p>
      <div class="mt-6 flex flex-wrap items-center gap-3">
        <Link :href="route('register')" class="flex h-12 items-center rounded-xl bg-forest px-6 font-semibold text-white transition active:scale-[0.98]">
          Simulan na <ArrowRight class="ml-2 h-4 w-4" />
        </Link>
        <span class="text-sm text-forest-muted">Libreng trial: 14 araw · Walang credit card</span>
      </div>
    </header>

    <section class="mx-auto max-w-5xl px-4">
      <div class="grid gap-4 sm:grid-cols-3">
        <article v-for="p in pillars" :key="p.title" class="rounded-2xl border border-warm bg-white p-5 shadow-card">
          <div class="mb-3 grid h-10 w-10 place-items-center rounded-xl bg-forest/10 text-forest"><component :is="p.icon" class="h-5 w-5" /></div>
          <h2 class="text-lg font-bold text-forest-dark">{{ p.title }}</h2>
          <p class="mt-1 text-sm leading-relaxed text-forest-muted">{{ p.copy }}</p>
        </article>
      </div>
      <div class="mt-4 rounded-2xl border border-forest/15 bg-forest/5 px-5 py-4 text-sm font-semibold text-forest-muted">
        99% ng mga negosyo sa Pilipinas ay MSME — CashIko ay para sa kanila.
      </div>
    </section>

    <section class="mx-auto max-w-5xl px-4 py-12">
      <h2 class="text-2xl font-extrabold text-forest-dark">Mula libreta sa CashIko</h2>
      <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-warm bg-white p-5">
          <div class="text-xs font-bold uppercase tracking-widest text-stone-500">Noon</div>
          <ul class="mt-3 space-y-2 text-sm text-forest-muted">
            <li v-for="i in beforeAfter[0].items" :key="i" class="flex gap-2"><span class="text-red-500">✕</span>{{ i }}</li>
          </ul>
        </div>
        <div class="rounded-2xl border border-forest/20 bg-forest/5 p-5">
          <div class="text-xs font-bold uppercase tracking-widest text-forest">Kasama ang CashIko</div>
          <ul class="mt-3 space-y-2 text-sm font-medium text-forest-dark">
            <li v-for="i in beforeAfter[1].items" :key="i" class="flex gap-2"><span class="text-emerald-600">✓</span>{{ i }}</li>
          </ul>
        </div>
      </div>
      <div class="my-10 flex flex-col items-center gap-3 rounded-2xl bg-forest px-6 py-10 text-center text-white">
        <h3 class="text-2xl font-extrabold">Handa na ba? Imulat ang negosyo mo ngayon.</h3>
        <Link :href="route('register')" class="mt-2 flex h-12 items-center rounded-xl bg-mango px-6 font-semibold transition active:scale-[0.98]">Gumawa ng account</Link>
      </div>
    </section>

    <footer class="border-t border-warm bg-white py-8 text-center text-xs text-forest-muted">
      CashIko — ang business companion ng Filipino microbusiness. Mock prototype, v1 build.
    </footer>
  </div>
</template>
