/** Money & date helpers mirrored from spec §7.1 (src/lib/format.js). */

export const peso = (n) =>
  '₱' + (Number(n) || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })

export const pesoShort = (n) => {
  const v = Number(n) || 0
  if (Math.abs(v) >= 1000) return '₱' + (v / 1000).toFixed(1) + 'k'
  return peso(Math.round(v))
}

const isoLocal = (d) => {
  const x = new Date()
  const shift = d || x
  const off = shift.getTimezoneOffset()
  return new Date(shift.getTime() - off * 60000).toISOString().slice(0, 10)
}

export const todayISO = () => isoLocal()
export const daysFromNow = (n) => isoLocal(new Date(Date.now() + n * 86400000))

export const friendlyDate = (iso) => {
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const t = isoLocal()
  if (isoLocal(d) === t) return 'Today'
  if (isoLocal(d) === isoLocal(new Date(Date.now() - 86400000))) return 'Yesterday'
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
}

export const friendlyDateTime = (iso) => {
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  return (
    friendlyDate(iso) +
    ', ' +
    d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true })
  )
}

export const paymentLabel = (p) =>
  ({ cash: 'Cash', gcash: 'GCash', maya: 'Maya', utang: 'Utang' }[p] || p)

export const paymentColor = (p) =>
  ({
    cash: 'text-emerald-600',
    gcash: 'text-sky-600',
    maya: 'text-violet-600',
    utang: 'text-orange-600',
  }[p] || 'text-stone-600')

export const greeting = () => {
  const h = new Date().getHours()
  if (h < 12) return 'Magandang umaga'
  if (h < 18) return 'Magandang hapon'
  return 'Magandang gabi'
}

export default { peso, pesoShort, todayISO, daysFromNow, friendlyDate, friendlyDateTime, paymentLabel, paymentColor, greeting }
