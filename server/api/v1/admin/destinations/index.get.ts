export default defineEventHandler((event) => {
  requireAdmin(event)
  const db = readDB()
  return {
    success: true,
    total: db.destinations.length,
    data: db.destinations
  }
})
