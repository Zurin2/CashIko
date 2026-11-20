/**
 * Bilingual copy layer (English ⇄ Filipino).
 *
 * English is the source of truth: templates pass the English sentence straight
 * into `t()` and the Filipino tables (`./locales/fil/*.js`) are keyed by that
 * same English sentence. A missing entry falls back to English, so the UI can
 * never render an empty string.
 *
 * The active locale comes from `business.language` and is refreshed on boot and
 * on every Inertia navigation (see app.js).
 */
import { ref } from 'vue'

const FILIPINO = Object.assign(
  {},
  ...Object.values(import.meta.glob('./locales/fil/*.js', { eager: true, import: 'default' })),
)

const locale = ref('english')

export const isFilipino = () => locale.value === 'filipino'

/** Called on boot and after every Inertia navigation with `business.language`. */
export function setLocale (value) {
  locale.value = value === 'filipino' ? 'filipino' : 'english'
  if (typeof document !== 'undefined') {
    document.documentElement.lang = locale.value === 'filipino' ? 'fil' : 'en'
  }
}

/** Translates an English sentence, interpolating `{name}` placeholders. */
export function t (english, params) {
  const phrase = locale.value === 'filipino' ? FILIPINO[english] ?? english : english
  if (!params) return phrase

  return phrase.replace(/\{(\w+)\}/g, (match, key) => (params[key] ?? match))
}
