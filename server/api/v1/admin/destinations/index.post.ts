import { readBody, createError } from 'h3'

export default defineEventHandler(async (event) => {
  requireAdmin(event)
  const body = await readBody(event)

  const { name, city, country, image, description, rating, popular_badge } = body || {}

  if (!name || !city || !country) {
    throw createError({
      statusCode: 422,
      statusMessage: 'Nama destinasi, kota, dan negara wajib diisi.'
    })
  }

  const db = readDB()
  const newId = db.destinations.length > 0 ? Math.max(...db.destinations.map(d => d.id)) + 1 : 1

  const newDest: Destination = {
    id: newId,
    name: String(name).trim(),
    city: String(city).trim(),
    country: String(country).trim(),
    image: image ? String(image).trim() : 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
    description: description ? String(description).trim() : '',
    rating: rating ? Number(rating) : 4.8,
    popular_badge: popular_badge ? String(popular_badge).trim() : 'Destinasi Favorit',
    createdAt: new Date().toISOString()
  }

  db.destinations.unshift(newDest)
  writeDB(db)

  return {
    success: true,
    message: 'Destinasi wisata baru berhasil ditambahkan.',
    data: newDest
  }
})
