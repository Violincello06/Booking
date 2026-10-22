import { readBody, getRouterParam, createError } from 'h3'

export default defineEventHandler(async (event) => {
  requireAdmin(event)
  const id = Number(getRouterParam(event, 'id'))
  const body = await readBody(event)

  const db = readDB()
  const index = db.destinations.findIndex(d => d.id === id)

  if (index === -1) {
    throw createError({
      statusCode: 404,
      statusMessage: 'Destinasi tidak ditemukan.'
    })
  }

  const existing = db.destinations[index]
  db.destinations[index] = {
    ...existing,
    name: body.name !== undefined ? String(body.name).trim() : existing.name,
    city: body.city !== undefined ? String(body.city).trim() : existing.city,
    country: body.country !== undefined ? String(body.country).trim() : existing.country,
    image: body.image !== undefined ? String(body.image).trim() : existing.image,
    description: body.description !== undefined ? String(body.description).trim() : existing.description,
    rating: body.rating !== undefined ? Number(body.rating) : existing.rating,
    popular_badge: body.popular_badge !== undefined ? String(body.popular_badge).trim() : existing.popular_badge
  }

  writeDB(db)

  return {
    success: true,
    message: 'Data destinasi berhasil diperbarui.',
    data: db.destinations[index]
  }
})
