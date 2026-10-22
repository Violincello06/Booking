import { readBody, createError } from 'h3'

export default defineEventHandler(async (event) => {
  requireAdmin(event)
  const body = await readBody(event)

  const {
    name,
    city,
    country,
    stars,
    rating_score,
    price,
    image,
    tag,
    amenities,
    total_rooms,
    available_rooms,
    description,
    destination_id
  } = body || {}

  if (!name || !city || !price) {
    throw createError({
      statusCode: 422,
      statusMessage: 'Nama hotel, kota, dan harga per malam wajib diisi.'
    })
  }

  const db = readDB()
  const newId = db.hotels.length > 0 ? Math.max(...db.hotels.map(h => h.id)) + 1 : 1

  let parsedAmenities: string[] = []
  if (Array.isArray(amenities)) {
    parsedAmenities = amenities
  } else if (typeof amenities === 'string') {
    parsedAmenities = amenities.split(',').map((s: string) => s.trim()).filter(Boolean)
  }
  if (parsedAmenities.length === 0) {
    parsedAmenities = ['Free WiFi', 'AC', 'Breakfast Included', 'Swimming Pool']
  }

  const newHotel: Hotel = {
    id: newId,
    destination_id: destination_id ? Number(destination_id) : undefined,
    name: String(name).trim(),
    city: String(city).trim(),
    country: country ? String(country).trim() : 'Indonesia',
    stars: stars ? Number(stars) : 4,
    rating_score: rating_score ? Number(rating_score) : 4.8,
    price: Number(price),
    tag: tag ? String(tag).trim() : 'Recommended',
    image: image ? String(image).trim() : 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80',
    amenities: parsedAmenities,
    total_rooms: total_rooms ? Number(total_rooms) : 20,
    available_rooms: available_rooms !== undefined ? Number(available_rooms) : (total_rooms ? Number(total_rooms) : 10),
    description: description ? String(description).trim() : '',
    createdAt: new Date().toISOString()
  }

  db.hotels.unshift(newHotel)
  writeDB(db)

  return {
    success: true,
    message: 'Data hotel baru berhasil ditambahkan.',
    data: newHotel
  }
})
