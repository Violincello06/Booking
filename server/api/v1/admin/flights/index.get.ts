export default defineEventHandler((event) => {
  requireAdmin(event)
  const db = readDB()
  return {
    success: true,
    total: db.flights.length,
    data: db.flights
  }
})
