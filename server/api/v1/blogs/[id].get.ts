export default defineEventHandler((event) => {
  const id = Number(event.context.params?.id)
  const db = readDB()
  const blog = (db.blogs || []).find((b) => b.id === id)

  if (!blog) {
    throw createError({
      statusCode: 404,
      statusMessage: 'Artikel blog tidak ditemukan'
    })
  }

  return {
    success: true,
    data: blog
  }
})
