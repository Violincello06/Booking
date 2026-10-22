import { readBody, getRouterParam, createError } from 'h3'

export default defineEventHandler(async (event) => {
  requireAdmin(event)
  const id = Number(getRouterParam(event, 'id'))
  const body = await readBody(event)

  const db = readDB()
  const index = db.hotels.findIndex(h => h.id === id)

  if (index === -1) {
    throw createError({
      statusCode: 404,
      statusMessage: 'Hotel tidak ditemukan.'
    })
  }

  const existing = db.hotels[index]

  let parsedAmenities = existing.amenities
  if (body.amenities !== undefined) {
    if (Array.isArray(body.amenities)) {
      parsedAmenities = body.amenities
    } else if (typeof body.amenities === 'string') {
      parsedAmenities = body.amenities.split(',').map((s: string) => s.trim()).filter(Boolean)
    }
  }

  db.hotels[index] = {
    ...existing,
    name: body.name !== undefined ? String(body.name).trim() : existing.name,
    city: body.city !== undefined ? String(body.city).trim() : existing.city,
    country: body.country !== undefined ? String(body.country).trim() : existing.country,
    stars: body.stars !== undefined ? Number(body.stars) : existing.stars,
    rating_score: body.rating_score !== undefined ? Number(body.rating_score) : existing.rating_score,
    price: body.price !== undefined ? Number(body.price) : existing.price,
    tag: body.tag !== undefined ? String(body.tag).trim() : existing.tag,
    image: body.image !== undefined ? String(body.image).trim() : existing.image,
    amenities: parsedAmenities,
    total_rooms: body.total_rooms !== undefined ? Number(body.total_rooms) : existing.total_rooms,
    available_rooms: body.available_rooms !== undefined ? Number(body.available_rooms) : existing.available_rooms,
    description: body.description !== undefined ? String(body.description).trim() : existing.description
  }

  writeDB(db)

  return {
    success: true,
    message: 'Data hotel berhasil diperbarui.',
    data: db.hotels[index]
  }
})
