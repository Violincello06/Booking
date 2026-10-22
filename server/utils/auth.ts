import { H3Event, getHeader, createError } from 'h3'
import { verifyToken, readDB, User } from './db'

export function getUserFromEvent(event: H3Event): User | null {
  const authHeader = getHeader(event, 'authorization') || ''
  if (!authHeader.startsWith('Bearer ')) {
    return null
  }
  const token = authHeader.substring(7).trim()
  const payload = verifyToken(token)
  if (!payload) {
    return null
  }
  const db = readDB()
  const user = db.users.find(u => u.id === payload.id)
  return user || null
}

export function requireAdmin(event: H3Event): User {
  const user = getUserFromEvent(event)
  if (!user) {
    throw createError({
      statusCode: 401,
      statusMessage: 'Unauthorized: Harap login terlebih dahulu.'
    })
  }
  if (user.role !== 'admin') {
    throw createError({
      statusCode: 403,
      statusMessage: 'Forbidden: Akses manajemen sistem hanya untuk Administrator.'
    })
  }
  return user
}

export function requireAuth(event: H3Event): User {
  const user = getUserFromEvent(event)
  if (!user) {
    throw createError({
      statusCode: 401,
      statusMessage: 'Unauthorized: Harap login terlebih dahulu.'
    })
  }
  return user
}
