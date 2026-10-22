import { readBody, createError } from 'h3'

export default defineEventHandler(async (event) => {
  requireAdmin(event)
  const body = await readBody(event)

  const {
    airlineName,
    airlineCode,
    flightNumber,
    originCode,
    originCity,
    destCode,
    destCity,
    depTime,
    arrTime,
    duration,
    price,
    seats,
    classType
  } = body || {}

  if (!airlineName || !flightNumber || !originCode || !destCode || !price) {
    throw createError({
      statusCode: 422,
      statusMessage: 'Maskapai, nomor penerbangan, rute asal, rute tujuan, dan harga wajib diisi.'
    })
  }

  const db = readDB()
  const newId = db.flights.length > 0 ? Math.max(...db.flights.map(f => f.id)) + 1 : 1

  const newFlight: Flight = {
    id: newId,
    airlineName: String(airlineName).trim(),
    airlineCode: airlineCode ? String(airlineCode).trim().toUpperCase() : 'VN',
    flightNumber: String(flightNumber).trim().toUpperCase(),
    originCode: String(originCode).trim().toUpperCase(),
    originCity: originCity ? String(originCity).trim() : String(originCode).trim().toUpperCase(),
    destCode: String(destCode).trim().toUpperCase(),
    destCity: destCity ? String(destCity).trim() : String(destCode).trim().toUpperCase(),
    depTime: depTime ? String(depTime).trim() : '08:00',
    arrTime: arrTime ? String(arrTime).trim() : '10:00',
    duration: duration ? String(duration).trim() : '2j 0m',
    price: Number(price),
    seats: seats ? Number(seats) : 40,
    classType: classType ? String(classType).trim() : 'Ekonomi Standar',
    createdAt: new Date().toISOString()
  }

  db.flights.unshift(newFlight)
  writeDB(db)

  return {
    success: true,
    message: 'Jadwal tiket penerbangan baru berhasil ditambahkan.',
    data: newFlight
  }
})
