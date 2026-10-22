import { getQuery } from 'h3'

export default defineEventHandler((event) => {
  const query = getQuery(event)
  const db = readDB()
  let list = [...db.destinations]

  if (query.q) {
    const q = String(query.q).toLowerCase()
    list = list.filter(
      (d) =>
        (d.name || '').toLowerCase().includes(q) ||
        (d.city || '').toLowerCase().includes(q) ||
        (d.country || '').toLowerCase().includes(q)
    )
  }

  return {
    success: true,
    total: list.length,
    data: list
  }
})
