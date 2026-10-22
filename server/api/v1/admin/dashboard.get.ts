export default defineEventHandler((event) => {
  requireAdmin(event)
  const db = readDB()

  return {
    success: true,
    data: {
      stats: {
        totalDestinations: db.destinations.length,
        totalHotels: db.hotels.length,
        totalFlights: db.flights.length,
        totalUsers: db.users.length,
        totalAvailableRooms: db.hotels.reduce((acc, h) => acc + (h.available_rooms || 0), 0),
        totalFlightSeats: db.flights.reduce((acc, f) => acc + (f.seats || 0), 0)
      }
    }
  }
})
