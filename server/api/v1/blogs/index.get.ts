import { getQuery } from 'h3'

export default defineEventHandler((event) => {
  const query = getQuery(event)
  const db = readDB()
  let list = [...(db.blogs || [])]

  if (query.q) {
    const q = String(query.q).toLowerCase()
    list = list.filter(
      (b) =>
        (b.title || '').toLowerCase().includes(q) ||
        (b.summary || '').toLowerCase().includes(q) ||
        (b.content || '').toLowerCase().includes(q) ||
        (b.category || '').toLowerCase().includes(q)
    )
  }

  if (query.category) {
    const cat = String(query.category).toLowerCase()
    list = list.filter((b) => (b.category || '').toLowerCase() === cat)
  }

  return {
    success: true,
    total: list.length,
    data: list
  }
})
