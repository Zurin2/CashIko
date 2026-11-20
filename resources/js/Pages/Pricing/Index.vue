<script setup>
/** Pricing (§8.13): plan toggle. */
import { Head, router } from '@inertiajs/vue3'
import { Check, Crown } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import Toaster from '@/Components/Toaster.vue'
import { usePage } from '@inertiajs/vue3'
import { t } from '@/lib/i18n'

const props = defineProps({ trialDays: Number })

const isPremium = usePage().props.business?.plan === 'premium'

const FEATURES = {
  basic: [
    'Stock tracking + low stock alerts',
    'Sales logging (POS)',
    'Profit monitoring',
    'Utang ledger + reminders',
    'Reports & CSV/Print export',
  ],
  premium: [
    'Everything in Basic',
    'AI assistant — ask CashIko',
    'Multi-branch real-time tracking',
    'Priority support',
  ],
}

const choose = (plan) =>
  router.post(route('pricing.plan'), { plan }, {})
</script>

<template>
  <Head :title="t('Pricing')" />
  <AppLayout>
    <Toaster />
    <div class="md:max-w-2xl mx-auto pb-8">
      <div class="mb-4 rounded-2xl border border-forest/15 bg-forest/5 px-4 py-3 text-sm font-semibold text-forest-muted">
        {{ t('Free trial:') }} <b class="text-forest-dark">{{ t('{count} days left', { count: props.trialDays }) }}</b>
        <span class="block text-[11px] font-normal">{{ t('GCash / Maya payments coming soon.') }}</span>
      </div>

      <div class="space-y-4">
        <article v-for="plan in ['basic', 'premium']" :key="plan" class="rounded-2xl border bg-white p-5 shadow-card" :class="plan === 'premium' ? 'border-forest/30' : 'border-warm'">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="flex items-center gap-1.5 text-lg font-extrabold capitalize text-forest-dark">
                {{ plan }} <Crown v-if="plan === 'premium'" class="h-4 w-4 text-mango" />
              </h2>
              <div class="text-sm text-forest-muted">
                <span class="font-bold text-forest-dark">₱{{ plan === 'basic' ? 99 : 199 }}</span> {{ t('/ month') }}
              </div>
            </div>
            <span v-if="(isPremium ? 'premium' : 'basic') === plan" class="rounded-full bg-forest px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white">{{ t('CURRENT') }}</span>
          </div>
          <ul class="mt-3 space-y-1.5 text-sm">
            <li v-for="f in FEATURES[plan]" :key="f" class="flex items-start gap-2 text-forest-dark">
              <Check class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" /> {{ t(f) }}
            </li>
          </ul>
          <button
            v-if="(isPremium ? 'premium' : 'basic') !== plan"
            class="mt-4 h-12 w-full rounded-xl font-semibold transition active:scale-[0.98]"
            :class="plan === 'premium' ? 'bg-mango text-white' : 'border border-forest text-forest'"
            @click="choose(plan)"
          >
            {{ plan === 'premium' ? t('Upgrade to Premium') : t('Downgrade to Basic') }}
          </button>
        </article>
      </div>
    </div>
  </AppLayout>
</template>
