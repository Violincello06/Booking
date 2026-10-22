export default defineEventHandler((event) => {
  const user = requireAuth(event)
  return {
    success: true,
    data: {
      id: user.id,
      name: user.name,
      email: user.email,
      role: user.role,
      avatar: user.avatar,
      phone: user.phone
    }
  }
})
