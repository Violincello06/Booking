import { readBody, getRouterParam, createError } from 'h3'

export default defineEventHandler(async (event) => {
  requireAdmin(event)
  const id = Number(getRouterParam(event, 'id'))
  const body = await readBody(event)

  const db = readDB()
  const index = db.flights.findIndex(f => f.id === id)

  if (index === -1) {
    throw createError({
      statusCode: 404,
      statusMessage: 'Penerbangan tidak ditemukan.'
    })
  }

  const existing = db.flights[index]

  db.flights[index] = {
    ...existing,
    airlineName: body.airlineName !== undefined ? String(body.airlineName).trim() : existing.airlineName,
    airlineCode: body.airlineCode !== undefined ? String(body.airlineCode).trim().toUpperCase() : existing.airlineCode,
    flightNumber: body.flightNumber !== undefined ? String(body.flightNumber).trim().toUpperCase() : existing.flightNumber,
    originCode: body.originCode !== undefined ? String(body.originCode).trim().toUpperCase() : existing.originCode,
    originCity: body.originCity !== undefined ? String(body.originCity).trim() : existing.originCity,
    destCode: body.destCode !== undefined ? String(body.destCode).trim().toUpperCase() : existing.destCode,
    destCity: body.destCity !== undefined ? String(body.destCity).trim() : existing.destCity,
    depTime: body.depTime !== undefined ? String(body.depTime).trim() : existing.depTime,
    arrTime: body.arrTime !== undefined ? String(body.arrTime).trim() : existing.arrTime,
    duration: body.duration !== undefined ? String(body.duration).trim() : existing.duration,
    price: body.price !== undefined ? Number(body.price) : existing.price,
    seats: body.seats !== undefined ? Number(body.seats) : existing.seats,
    classType: body.classType !== undefined ? String(body.classType).trim() : existing.classType
  }

  writeDB(db)

  return {
    success: true,
    message: 'Data penerbangan berhasil diperbarui.',
    data: db.flights[index]
  }
})
