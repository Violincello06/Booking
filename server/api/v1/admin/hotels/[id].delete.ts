import { getRouterParam, createError } from 'h3'

export default defineEventHandler((event) => {
  requireAdmin(event)
  const id = Number(getRouterParam(event, 'id'))

  const db = readDB()
  const index = db.hotels.findIndex(h => h.id === id)

  if (index === -1) {
    throw createError({
      statusCode: 404,
      statusMessage: 'Hotel tidak ditemukan.'
    })
  }

  const removed = db.hotels.splice(index, 1)[0]
  writeDB(db)

  return {
    success: true,
    message: `Hotel "${removed.name}" berhasil dihapus.`,
    data: removed
  }
})
