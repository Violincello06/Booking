import { getRouterParam, createError } from 'h3'

export default defineEventHandler((event) => {
  requireAdmin(event)
  const id = Number(getRouterParam(event, 'id'))

  const db = readDB()
  const index = db.destinations.findIndex(d => d.id === id)

  if (index === -1) {
    throw createError({
      statusCode: 404,
      statusMessage: 'Destinasi tidak ditemukan.'
    })
  }

  const removed = db.destinations.splice(index, 1)[0]
  writeDB(db)

  return {
    success: true,
    message: `Destinasi "${removed.name}" berhasil dihapus.`,
    data: removed
  }
})
