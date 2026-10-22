import { getQuery } from 'h3'

export default defineEventHandler((event) => {
  const query = getQuery(event)
  const db = readDB()
  let list = [...db.flights]

  if (query.origin) {
    const orig = String(query.origin).toLowerCase()
    list = list.filter(
      (f) =>
        (f.originCode || '').toLowerCase().includes(orig) ||
        (f.originCity || '').toLowerCase().includes(orig)
    )
  }

  if (query.destination) {
    const dest = String(query.destination).toLowerCase()
    list = list.filter(
      (f) =>
        (f.destCode || '').toLowerCase().includes(dest) ||
        (f.destCity || '').toLowerCase().includes(dest)
    )
  }

  return {
    success: true,
    total: list.length,
    data: list
  }
})
