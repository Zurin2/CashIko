<script setup>
/** Assistant (§8.12): premium chat persisted via ChatMessage. */
import { computed, nextTick, ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Sparkles, Send, Mic, Trash2 } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Toaster from '@/Components/Toaster.vue'
import { peso } from '@/lib/format'

const props = defineProps({ messages: Array })

const SUGGESTED = [
  'Kumita ba ako this week?',
  'Sino ang may pinakamalaking utang?',
  'Anong mga item ang malapit nang maubos?',
  'Ano ang best seller ko ngayong buwan?',
  'Magkano ang benta ko kahapon?',
  'Aling branch ang pinakamataas ang benta?',
  'Anong mga item ang hindi nabebenta?',
]

const draft = ref('')
const list = ref([...props.messages])
const sending = ref(false)
const body = ref(null)

router.on('success', () => {
  /* Keep the page's server-sent message list in sync with fresh history. */
  const fresh = router.page?.props?.messages
  if (fresh) list.value = [...fresh]
})

const scrollDown = () => nextTick(() => body.value?.scrollTo({ top: body.value.scrollHeight }))

watch(list, () => scrollDown(), { deep: true })

const ask = (question) => {
  const q = (question ?? draft.value).trim()
  if (!q || sending.value) return
  draft.value = ''
  list.value.push({ role: 'user', content: q })
  list.value.push({ role: 'assistant', content: '__loading__', id: 'loading' })
  sending.value = true
  scrollDown()

  router.post(route('assistant.ask'), { question: q }, {
    preserveState: true,
    replace: true,
    only: ['messages'],
    onSuccess: (page) => {
      list.value = [...(page.props.messages || props.messages)]
      sending.value = false
      scrollDown()
    },
    onError: () => {
      list.value = list.value.filter((m) => m.id !== 'loading')
      sending.value = false
    },
  })
}

const clear = () => {
  if (!confirm('Clear chat history?')) return
  router.post(route('assistant.clear'), {}, { onSuccess: () => (list.value = []), only: ['messages'] })
}

const visible = computed(() => list.value.filter((m) => m.id !== 'loading' || m.content === '__loading__'))
</script>

<template>
  <Head title="Tanong kay CashIko" />
  <AppLayout>
    <Toaster />
    <div class="mx-auto flex h-[calc(100vh-11rem)] flex-col md:max-w-2xl">
      <header class="mb-3 flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-mango to-forest text-white"><Sparkles class="h-5 w-5" /></span>
        <span class="flex-1">
          <span class="block font-bold text-forest-dark">Tanong kay CashIko</span>
          <span class="block text-[11px] text-forest-muted">AI business assistant</span>
        </span>
        <button class="tap-target grid place-items-center text-stone-400 hover:text-red-600" @click="clear" aria-label="Clear chat"><Trash2 class="h-4.5 w-4.5" /></button>
      </header>

      <div ref="body" class="flex-1 space-y-2.5 overflow-y-auto pr-1">
        <div v-if="!visible.length" class="space-y-4">
          <div class="space-y-2">
            <button v-for="s in SUGGESTED" :key="s" class="tap-target w-full rounded-2xl border border-warm bg-white px-4 py-3 text-left text-sm font-semibold text-forest-dark shadow-card transition hover:border-forest/40 active:scale-[0.99]" @click="ask(s)">
              {{ s }}
            </button>
          </div>
        </div>

        <template v-for="(m, i) in visible" :key="i">
          <div v-if="m.content === '__loading__'" class="max-w-[70%] rounded-2xl border border-warm bg-white px-4 py-2.5 text-sm text-forest-muted">Nag-iisip…</div>
          <div
            v-else
            class="max-w-[80%] whitespace-pre-wrap rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
            :class="m.role === 'user' ? 'ml-auto bg-forest text-white' : 'mr-auto border border-warm bg-white text-forest-dark shadow-card'"
          >{{ m.content }}</div>
        </template>
      </div>

      <form class="mb-2 mt-3 flex items-end gap-2 rounded-2xl border border-warm bg-white p-2 shadow-card" @submit.prevent="ask()">
        <input
          class="input !h-11 flex-1 border-0 ring-0 focus:ring-0"
          v-model="draft"
          placeholder="Magtanong tungkol sa negosyo mo…"
          @keydown.enter.exact.prevent="ask()"
        />
        <button type="button" class="tap-target grid place-items-center text-stone-300" disabled :title="'Voice — coming soon'"><Mic class="h-5 w-5" /></button>
        <button type="submit" class="tap-target grid h-11 w-11 place-items-center rounded-xl bg-forest text-white active:scale-95 disabled:opacity-40" :disabled="sending"><Send class="h-4.5 w-4.5" /></button>
      </form>
    </div>
  </AppLayout>
</template>
