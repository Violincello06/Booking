import { getQuery } from 'h3'

export default defineEventHandler((event) => {
  const query = getQuery(event)
  const db = readDB()
  let list = [...db.hotels]

  // Filter query by hotel name or city
  if (query.query) {
    const q = String(query.query).toLowerCase()
    list = list.filter(
      (h) =>
        (h.name || '').toLowerCase().includes(q) ||
        (h.city || '').toLowerCase().includes(q) ||
        (h.country || '').toLowerCase().includes(q)
    )
  }

  // Filter by min rating / stars
  if (query.star_rating || query.minRating) {
    const minStars = Number(query.star_rating || query.minRating)
    if (!isNaN(minStars) && minStars > 0) {
      list = list.filter((h) => (h.stars || 0) >= minStars)
    }
  }

  // Sorting
  const sortBy = String(query.sort_by || query.sortBy || 'latest')
  if (sortBy === 'price_asc') {
    list.sort((a, b) => (a.price || 0) - (b.price || 0))
  } else if (sortBy === 'price_desc') {
    list.sort((a, b) => (b.price || 0) - (a.price || 0))
  } else if (sortBy === 'rating_desc') {
    list.sort((a, b) => (b.rating_score || b.rating || 0) - (a.rating_score || a.rating || 0))
  } else {
    // latest
    list.sort((a, b) => (b.id || 0) - (a.id || 0))
  }

  return {
    success: true,
    total: list.length,
    data: list
  }
})
