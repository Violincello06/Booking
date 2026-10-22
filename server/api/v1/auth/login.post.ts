import { readBody, createError } from 'h3'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const { email, password } = body || {}

  if (!email || !password) {
    throw createError({
      statusCode: 422,
      statusMessage: 'Email dan kata sandi wajib diisi.'
    })
  }

  const db = readDB()
  const user = db.users.find(
    (u) => u.email.toLowerCase() === String(email).trim().toLowerCase()
  )

  if (!user || user.password !== password) {
    throw createError({
      statusCode: 401,
      statusMessage: 'Email atau kata sandi tidak cocok.'
    })
  }

  const token = generateToken(user)

  return {
    success: true,
    message: 'Autentikasi berhasil.',
    data: {
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
        avatar: user.avatar,
        phone: user.phone
      },
      token
    }
  }
})
