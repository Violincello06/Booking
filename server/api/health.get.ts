export default defineEventHandler((event) => {
  return {
    status: 'ok',
    message: 'Violincello Nitro API is active',
    timestamp: new Date().toISOString()
  }
})
