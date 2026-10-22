export default defineEventHandler((event) => {
  requireAdmin(event)
  const db = readDB()
  return {
    success: true,
    total: db.hotels.length,
    data: db.hotels
  }
})
