import { getRouterParam, createError } from 'h3'

export default defineEventHandler((event) => {
  requireAdmin(event)
  const id = Number(getRouterParam(event, 'id'))

  const db = readDB()
  const index = db.flights.findIndex(f => f.id === id)

  if (index === -1) {
    throw createError({
      statusCode: 404,
      statusMessage: 'Penerbangan tidak ditemukan.'
    })
  }

  const removed = db.flights.splice(index, 1)[0]
  writeDB(db)

  return {
    success: true,
    message: `Penerbangan "${removed.flightNumber}" (${removed.airlineName}) berhasil dihapus.`,
    data: removed
  }
})
