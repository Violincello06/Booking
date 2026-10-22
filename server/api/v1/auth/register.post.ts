import { readBody, createError } from 'h3'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const { name, email, password, phone } = body || {}

  if (!name || !email || !password) {
    throw createError({
      statusCode: 422,
      statusMessage: 'Nama lengkap, email, dan kata sandi wajib diisi.'
    })
  }

  const db = readDB()
  const exists = db.users.some(
    (u) => u.email.toLowerCase() === String(email).trim().toLowerCase()
  )

  if (exists) {
    throw createError({
      statusCode: 409,
      statusMessage: 'Email ini sudah terdaftar. Silakan login.'
    })
  }

  const newUser = {
    id: db.users.length > 0 ? Math.max(...db.users.map((u) => u.id)) + 1 : 1,
    name: String(name).trim(),
    email: String(email).trim().toLowerCase(),
    password: String(password),
    role: 'user' as const, // Registrasi publik selalu role user
    phone: phone ? String(phone).trim() : '',
    avatar: `https://api.dicebear.com/7.x/avataaars/svg?seed=${encodeURIComponent(name)}`
  }

  db.users.push(newUser)
  writeDB(db)

  const token = generateToken(newUser)

  return {
    success: true,
    message: 'Registrasi berhasil. Selamat datang di Violincello Travel!',
    data: {
      user: {
        id: newUser.id,
        name: newUser.name,
        email: newUser.email,
        role: newUser.role,
        avatar: newUser.avatar,
        phone: newUser.phone
      },
      token
    }
  }
})
