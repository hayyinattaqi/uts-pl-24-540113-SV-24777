const dateFormatter = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
})

// "2026-12-31" diparse sebagai tanggal lokal agar tidak bergeser karena zona waktu.
function parseDate(value) {
  const [year, month, day] = value.split('-').map(Number)
  return new Date(year, month - 1, day)
}

export function formatDate(value) {
  return value ? dateFormatter.format(parseDate(value)) : '-'
}

export function formatDateTime(value) {
  return value ? dateTimeFormatter.format(new Date(value)) : '-'
}

export function daysUntil(value) {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return Math.round((parseDate(value) - today) / 86400000)
}

export function toInputDate(date) {
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${date.getFullYear()}-${month}-${day}`
}

export const batchStatusLabels = {
  ok: { label: 'Aman', className: 'status-ok' },
  near_expiry: { label: 'Hampir kedaluwarsa', className: 'status-warn' },
  expired: { label: 'Kedaluwarsa', className: 'status-danger' },
}

// Ambil pesan pertama per kolom dari respons 422 Laravel.
export function firstErrors(errors = {}) {
  return Object.fromEntries(Object.entries(errors).map(([field, messages]) => [field, messages[0]]))
}

const longDateFormatter = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

export function todayLabel() {
  return longDateFormatter.format(new Date())
}
