const express = require('express')
const cors = require('cors')
const fs = require('fs')
const path = require('path')

const app = express()
const PORT = process.env.PORT || 8000
const DB_FILE = path.join(__dirname, 'data', 'db.json')

app.use(cors())
app.use(express.json({ limit: '50mb' }))
app.use(express.urlencoded({ extended: true, limit: '50mb' }))

// Setup Static Uploads Directory
const UPLOADS_DIR = path.join(__dirname, 'public', 'uploads')
if (!fs.existsSync(UPLOADS_DIR)) {
  fs.mkdirSync(UPLOADS_DIR, { recursive: true })
}
app.use('/uploads', express.static(UPLOADS_DIR))

// Serve Portal Manajemen Sistem Admin langsung di root http://localhost:8000/
app.use(express.static(path.join(__dirname, 'public')))
app.use('/admin-panel', express.static(path.join(__dirname, 'public')))

// Database Helpers
function readDB() {
  try {
    const raw = fs.readFileSync(DB_FILE, 'utf-8')
    const parsed = JSON.parse(raw)
    if (!parsed.blogs) parsed.blogs = []
    return parsed
  } catch (err) {
    console.error('Error reading db.json:', err)
    return { users: [], destinations: [], hotels: [], flights: [], blogs: [] }
  }
}

function writeDB(data) {
  try {
    fs.writeFileSync(DB_FILE, JSON.stringify(data, null, 2), 'utf-8')
    return true
  } catch (err) {
    console.error('Error writing db.json:', err)
    return false
  }
}

// Token Generator & Verifier (Mendukung JWT-like Base64 Token & Master Key untuk Postman)
function createToken(user) {
  const payload = {
    id: user.id,
    name: user.name,
    email: user.email,
    role: user.role,
    expiresAt: Date.now() + 365 * 24 * 60 * 60 * 1000 // Aktif selama 1 tahun untuk testing Postman tanpa kendala
  }
  return Buffer.from(JSON.stringify(payload)).toString('base64')
}

// Master Static Tokens untuk kemudahan integrasi Postman & Testing
const MASTER_TOKENS = {
  'violincello-admin-token-2026': { id: 1, name: 'Administrator OTA', email: 'admin@violincello.com', role: 'admin' },
  'violincello-admin-token': { id: 1, name: 'Administrator OTA', email: 'admin@violincello.com', role: 'admin' },
  'admin-token': { id: 1, name: 'Administrator OTA', email: 'admin@violincello.com', role: 'admin' },
  'violincello-user-token-2026': { id: 2, name: 'Pelanggan Wisata', email: 'user@violincello.com', role: 'user' },
  'user-token': { id: 2, name: 'Pelanggan Wisata', email: 'user@violincello.com', role: 'user' }
}

function authMiddleware(req, res, next) {
  let token = ''
  const authHeader = req.headers['authorization'] || ''
  
  if (authHeader.startsWith('Bearer ')) {
    token = authHeader.substring(7).trim()
  } else if (req.headers['x-api-key']) {
    token = String(req.headers['x-api-key']).trim()
  } else if (req.headers['x-token']) {
    token = String(req.headers['x-token']).trim()
  } else if (req.query && req.query.token) {
    token = String(req.query.token).trim()
  }

  if (!token) {
    return res.status(401).json({ 
      success: false, 
      message: 'Harap sertakan token autentikasi Bearer pada Header "Authorization: Bearer <token>".' 
    })
  }

  // 1. Cek Master Key
  if (MASTER_TOKENS[token]) {
    req.user = MASTER_TOKENS[token]
    return next()
  }

  // 2. Decode Base64 Token
  try {
    const decoded = JSON.parse(Buffer.from(token, 'base64').toString('utf-8'))
    if (decoded.expiresAt && decoded.expiresAt < Date.now()) {
      return res.status(401).json({ success: false, message: 'Sesi token telah kedaluwarsa. Silakan perbarui token Anda.' })
    }
    req.user = decoded
    next()
  } catch {
    return res.status(401).json({ 
      success: false, 
      message: 'Token tidak valid. Pastikan format token Bearer sesuai.' 
    })
  }
}

function adminOnly(req, res, next) {
  if (!req.user || req.user.role !== 'admin') {
    return res.status(403).json({ 
      success: false, 
      message: 'Akses ditolak: Hanya Administrator (role: "admin") yang berhak melakukan operasi CRUD manajemen sistem.' 
    })
  }
  next()
}

// -------------------------------------------------------------
// 1. PUBLIC & AUTH API ROUTES (Untuk Postman & Frontend)
// -------------------------------------------------------------
const loginHandler = (req, res) => {
  const { email, password } = req.body || {}
  if (!email || !password) {
    return res.status(400).json({ success: false, message: 'Email dan password wajib diisi.' })
  }

  const db = readDB()
  const user = db.users.find(u => u.email.toLowerCase() === String(email).trim().toLowerCase())

  if (!user || user.password !== password) {
    return res.status(401).json({ success: false, message: 'Kombinasi email atau kata sandi tidak cocok.' })
  }

  const token = createToken(user)
  res.json({
    success: true,
    message: 'Autentikasi berhasil.',
    data: {
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
        phone: user.phone
      },
      token
    }
  })
}

// Multi-path Login Routes (Port 8000 Postman Compatible)
app.post(['/api/auth/login', '/api/login', '/api/v1/auth/login', '/api/v1/login'], loginHandler)

// Route khusus untuk mendapatkan Token CRUD siap pakai untuk Postman
app.get(['/api/tokens', '/api/v1/tokens', '/api/auth/tokens', '/api/v1/auth/tokens'], (req, res) => {
  const db = readDB()
  const adminUser = db.users.find(u => u.role === 'admin') || { id: 1, name: 'Administrator OTA', email: 'admin@violincello.com', role: 'admin' }
  const regularUser = db.users.find(u => u.role === 'user') || { id: 2, name: 'Pelanggan Wisata', email: 'user@violincello.com', role: 'user' }

  const adminToken = createToken(adminUser)
  const userToken = createToken(regularUser)

  res.json({
    success: true,
    message: 'Token autentikasi siap digunakan di Postman untuk operasi CRUD.',
    data: {
      admin: {
        role: 'admin',
        email: adminUser.email,
        description: 'Gunakan token ini untuk operasi CRUD (Create, Read, Update, Delete) Destinasi, Hotel, & Pesawat',
        bearer_token: adminToken,
        master_key: 'violincello-admin-token-2026',
        postman_header: {
          Authorization: `Bearer ${adminToken}`
        }
      },
      user: {
        role: 'user',
        email: regularUser.email,
        description: 'Gunakan token ini untuk pengujian hak akses user biasa (akan menerima 403 Forbidden pada endpoint CRUD Admin)',
        bearer_token: userToken,
        master_key: 'violincello-user-token-2026',
        postman_header: {
          Authorization: `Bearer ${userToken}`
        }
      },
      how_to_use_in_postman: {
        method_1: 'Set Authorization Type ke "Bearer Token", lalu paste nilai bearer_token di atas.',
        method_2: 'Tambahkan Header manual: Key="Authorization", Value="Bearer <bearer_token>"',
        method_3: 'Gunakan Master Key di Header: Key="Authorization", Value="Bearer violincello-admin-token-2026"'
      }
    }
  })
})

// Register Route
app.post(['/api/auth/register', '/api/register', '/api/v1/auth/register', '/api/v1/register'], (req, res) => {
  const { name, email, password, phone } = req.body || {}
  if (!name || !email || !password) {
    return res.status(400).json({ success: false, message: 'Nama, email, dan password wajib diisi.' })
  }

  const db = readDB()
  const exists = db.users.some(u => u.email.toLowerCase() === String(email).trim().toLowerCase())
  if (exists) {
    return res.status(409).json({ success: false, message: 'Email sudah terdaftar.' })
  }

  const newUser = {
    id: db.users.length > 0 ? Math.max(...db.users.map(u => u.id)) + 1 : 1,
    name: String(name).trim(),
    email: String(email).trim().toLowerCase(),
    password: String(password),
    role: 'user',
    phone: phone ? String(phone).trim() : ''
  }

  db.users.push(newUser)
  writeDB(db)

  const token = createToken(newUser)
  res.json({
    success: true,
    message: 'Registrasi berhasil.',
    data: { user: newUser, token }
  })
})

// Profile / Me Route
app.get(['/api/auth/me', '/api/me', '/api/v1/auth/me', '/api/v1/me'], authMiddleware, (req, res) => {
  res.json({
    success: true,
    data: req.user
  })
})

// Katalog Destinasi Publik
app.get(['/api/public/destinations', '/api/destinations', '/api/v1/destinations'], (req, res) => {
  const db = readDB()
  let list = db.destinations || []
  const q = (req.query.q || req.query.query || '').trim().toLowerCase()
  if (q) {
    list = list.filter(d => 
      (d.name || '').toLowerCase().includes(q) || 
      (d.city || '').toLowerCase().includes(q) ||
      (d.country || '').toLowerCase().includes(q)
    )
  }
  res.json({ success: true, total: list.length, data: list })
})

// Katalog Hotel Publik
app.get(['/api/public/hotels', '/api/hotels', '/api/v1/hotels'], (req, res) => {
  const db = readDB()
  let list = db.hotels || []

  const q = (req.query.q || req.query.query || '').trim().toLowerCase()
  if (q) {
    list = list.filter(h => 
      (h.name || '').toLowerCase().includes(q) || 
      (h.city || '').toLowerCase().includes(q) ||
      (h.country || '').toLowerCase().includes(q)
    )
  }
  
  const minStars = Number(req.query.stars || req.query.minRating || req.query.star_rating)
  if (!isNaN(minStars) && minStars > 0) {
    list = list.filter(h => (h.stars || 0) >= minStars)
  }

  const sortBy = req.query.sortBy || req.query.sort_by
  if (sortBy === 'price_asc') {
    list.sort((a, b) => (a.price || 0) - (b.price || 0))
  } else if (sortBy === 'price_desc') {
    list.sort((a, b) => (b.price || 0) - (a.price || 0))
  } else if (sortBy === 'rating_desc') {
    list.sort((a, b) => (b.rating_score || b.rating || 0) - (a.rating_score || a.rating || 0))
  } else {
    list.sort((a, b) => (b.id || 0) - (a.id || 0))
  }

  res.json({ success: true, total: list.length, data: list })
})

// Katalog Tiket Pesawat Publik
app.get(['/api/public/flights', '/api/flights', '/api/v1/flights'], (req, res) => {
  const db = readDB()
  let list = db.flights || []

  if (req.query.origin) {
    const orig = req.query.origin.trim().toLowerCase()
    list = list.filter(f => 
      (f.originCode || '').toLowerCase().includes(orig) || 
      (f.originCity || '').toLowerCase().includes(orig)
    )
  }
  if (req.query.destination) {
    const dest = req.query.destination.trim().toLowerCase()
    list = list.filter(f => 
      (f.destCode || '').toLowerCase().includes(dest) || 
      (f.destCity || '').toLowerCase().includes(dest)
    )
  }

  res.json({ success: true, total: list.length, data: list })
})

// -------------------------------------------------------------
// 2. ADMIN MANAGEMENT API ROUTES (Khusus Admin Sistem - Postman Compatible)
// -------------------------------------------------------------
app.get(['/api/admin/dashboard', '/api/v1/admin/dashboard'], authMiddleware, adminOnly, (req, res) => {
  const db = readDB()
  res.json({
    success: true,
    data: {
      totalDestinations: db.destinations.length,
      totalHotels: db.hotels.length,
      totalFlights: db.flights.length,
      totalBlogs: (db.blogs || []).length,
      totalUsers: db.users.length,
      totalAvailableRooms: db.hotels.reduce((acc, h) => acc + (h.availableRooms || 0), 0),
      totalFlightSeats: db.flights.reduce((acc, f) => acc + (f.seats || 0), 0)
    }
  })
})

// --- API UPLOAD FOTO BLOG (Mendukung base64 / file upload) ---
app.post(['/api/admin/upload', '/api/v1/admin/upload', '/api/upload', '/api/v1/upload'], (req, res) => {
  const { image, filename } = req.body || {}
  if (!image) {
    return res.status(400).json({ success: false, message: 'Data foto (base64 image) wajib disertakan.' })
  }

  try {
    const matches = String(image).match(/^data:([A-Za-z-+\/]+);base64,(.+)$/)
    let fileBuffer
    let ext = 'png'

    if (matches && matches.length === 3) {
      const mime = matches[1]
      ext = mime.split('/')[1] || 'png'
      if (ext === 'jpeg') ext = 'jpg'
      fileBuffer = Buffer.from(matches[2], 'base64')
    } else {
      fileBuffer = Buffer.from(image, 'base64')
    }

    const safeName = `blog_${Date.now()}_${Math.floor(Math.random() * 1000)}.${ext}`
    const targetPath = path.join(UPLOADS_DIR, safeName)
    fs.writeFileSync(targetPath, fileBuffer)

    const publicUrl = `http://localhost:8000/uploads/${safeName}`
    res.json({
      success: true,
      message: 'Foto berhasil diunggah ke server.',
      data: {
        filename: safeName,
        url: publicUrl
      }
    })
  } catch (err) {
    console.error('Error saving uploaded photo:', err)
    res.status(500).json({ success: false, message: 'Gagal memproses unggahan foto.' })
  }
})

// --- PUBLIC BLOG ROUTES (Untuk Landing Page) ---
app.get(['/api/public/blogs', '/api/blogs', '/api/v1/blogs'], (req, res) => {
  const db = readDB()
  let list = db.blogs || []

  if (req.query.q) {
    const q = req.query.q.toLowerCase()
    list = list.filter(b => 
      (b.title || '').toLowerCase().includes(q) || 
      (b.summary || '').toLowerCase().includes(q) ||
      (b.content || '').toLowerCase().includes(q) ||
      (b.category || '').toLowerCase().includes(q)
    )
  }

  if (req.query.category) {
    const cat = req.query.category.toLowerCase()
    list = list.filter(b => (b.category || '').toLowerCase() === cat)
  }

  res.json({ success: true, total: list.length, data: list })
})

app.get(['/api/public/blogs/:id', '/api/blogs/:id', '/api/v1/blogs/:id'], (req, res) => {
  const id = Number(req.params.id)
  const db = readDB()
  const blog = (db.blogs || []).find(b => b.id === id)
  if (!blog) {
    return res.status(404).json({ success: false, message: 'Artikel blog tidak ditemukan.' })
  }
  res.json({ success: true, data: blog })
})

// --- CRUD DESTINASI ---
app.get(['/api/admin/destinations', '/api/v1/admin/destinations'], authMiddleware, adminOnly, (req, res) => {
  const db = readDB()
  res.json({ success: true, data: db.destinations })
})

app.post(['/api/admin/destinations', '/api/v1/admin/destinations'], authMiddleware, adminOnly, (req, res) => {
  const { name, city, country, image, description, rating, badge } = req.body || {}
  if (!name || !city) {
    return res.status(422).json({ success: false, message: 'Nama destinasi dan kota wajib diisi.' })
  }

  const db = readDB()
  const newId = db.destinations.length > 0 ? Math.max(...db.destinations.map(d => d.id)) + 1 : 1
  const item = {
    id: newId,
    name: String(name).trim(),
    city: String(city).trim(),
    country: country ? String(country).trim() : 'Indonesia',
    image: image ? String(image).trim() : 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
    description: description ? String(description).trim() : '',
    rating: rating ? Number(rating) : 4.8,
    badge: badge ? String(badge).trim() : 'Wisata Unggulan'
  }

  db.destinations.unshift(item)
  writeDB(db)
  res.json({ success: true, message: 'Destinasi wisata berhasil ditambahkan ke sistem.', data: item })
})

app.put(['/api/admin/destinations/:id', '/api/v1/admin/destinations/:id'], authMiddleware, adminOnly, (req, res) => {
  const id = Number(req.params.id)
  const db = readDB()
  const idx = db.destinations.findIndex(d => d.id === id)
  if (idx === -1) {
    return res.status(404).json({ success: false, message: 'Destinasi tidak ditemukan.' })
  }

  db.destinations[idx] = {
    ...db.destinations[idx],
    ...req.body,
    id
  }
  writeDB(db)
  res.json({ success: true, message: 'Data destinasi berhasil diperbarui.', data: db.destinations[idx] })
})

app.delete(['/api/admin/destinations/:id', '/api/v1/admin/destinations/:id'], authMiddleware, adminOnly, (req, res) => {
  const id = Number(req.params.id)
  const db = readDB()
  const idx = db.destinations.findIndex(d => d.id === id)
  if (idx === -1) {
    return res.status(404).json({ success: false, message: 'Destinasi tidak ditemukan.' })
  }

  const removed = db.destinations.splice(idx, 1)[0]
  writeDB(db)
  res.json({ success: true, message: `Destinasi "${removed.name}" telah dihapus.`, data: removed })
})

// --- CRUD HOTEL ---
app.get(['/api/admin/hotels', '/api/v1/admin/hotels'], authMiddleware, adminOnly, (req, res) => {
  const db = readDB()
  res.json({ success: true, data: db.hotels })
})

app.post(['/api/admin/hotels', '/api/v1/admin/hotels'], authMiddleware, adminOnly, (req, res) => {
  const { name, city, stars, price, availableRooms, tag, image, amenities, description } = req.body || {}
  if (!name || !city || !price) {
    return res.status(422).json({ success: false, message: 'Nama hotel, kota, dan harga per malam wajib diisi.' })
  }

  const db = readDB()
  const newId = db.hotels.length > 0 ? Math.max(...db.hotels.map(h => h.id)) + 1 : 1

  let parsedAmenities = []
  if (Array.isArray(amenities)) {
    parsedAmenities = amenities
  } else if (typeof amenities === 'string') {
    parsedAmenities = amenities.split(',').map(s => s.trim()).filter(Boolean)
  }

  const item = {
    id: newId,
    name: String(name).trim(),
    city: String(city).trim(),
    stars: stars ? Number(stars) : 4,
    rating: 4.8,
    price: Number(price),
    availableRooms: availableRooms !== undefined ? Number(availableRooms) : 10,
    tag: tag ? String(tag).trim() : 'Pilihan Utama',
    image: image ? String(image).trim() : 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80',
    amenities: parsedAmenities.length > 0 ? parsedAmenities : ['WiFi Gratis', 'Sarapan Termasuk', 'Kolam Renang'],
    description: description ? String(description).trim() : ''
  }

  db.hotels.unshift(item)
  writeDB(db)
  res.json({ success: true, message: 'Hotel baru berhasil didaftarkan.', data: item })
})

app.put(['/api/admin/hotels/:id', '/api/v1/admin/hotels/:id'], authMiddleware, adminOnly, (req, res) => {
  const id = Number(req.params.id)
  const db = readDB()
  const idx = db.hotels.findIndex(h => h.id === id)
  if (idx === -1) {
    return res.status(404).json({ success: false, message: 'Hotel tidak ditemukan.' })
  }

  let updatePayload = { ...req.body }
  if (updatePayload.amenities && typeof updatePayload.amenities === 'string') {
    updatePayload.amenities = updatePayload.amenities.split(',').map(s => s.trim()).filter(Boolean)
  }

  db.hotels[idx] = {
    ...db.hotels[idx],
    ...updatePayload,
    id
  }
  writeDB(db)
  res.json({ success: true, message: 'Data hotel berhasil diperbarui.', data: db.hotels[idx] })
})

app.delete(['/api/admin/hotels/:id', '/api/v1/admin/hotels/:id'], authMiddleware, adminOnly, (req, res) => {
  const id = Number(req.params.id)
  const db = readDB()
  const idx = db.hotels.findIndex(h => h.id === id)
  if (idx === -1) {
    return res.status(404).json({ success: false, message: 'Hotel tidak ditemukan.' })
  }

  const removed = db.hotels.splice(idx, 1)[0]
  writeDB(db)
  res.json({ success: true, message: `Hotel "${removed.name}" telah dihapus.`, data: removed })
})

// --- CRUD TIKET PESAWAT ---
app.get(['/api/admin/flights', '/api/v1/admin/flights'], authMiddleware, adminOnly, (req, res) => {
  const db = readDB()
  res.json({ success: true, data: db.flights })
})

app.post(['/api/admin/flights', '/api/v1/admin/flights'], authMiddleware, adminOnly, (req, res) => {
  const { airline, flightNumber, originCode, originCity, destCode, destCity, depTime, arrTime, duration, price, seats, class: flightClass } = req.body || {}
  if (!airline || !flightNumber || !originCode || !destCode || !price) {
    return res.status(422).json({ success: false, message: 'Maskapai, nomor penerbangan, kode asal, kode tujuan, dan harga wajib diisi.' })
  }

  const db = readDB()
  const newId = db.flights.length > 0 ? Math.max(...db.flights.map(f => f.id)) + 1 : 1

  const item = {
    id: newId,
    airline: String(airline).trim(),
    flightNumber: String(flightNumber).trim().toUpperCase(),
    originCode: String(originCode).trim().toUpperCase(),
    originCity: originCity ? String(originCity).trim() : String(originCode).trim().toUpperCase(),
    destCode: String(destCode).trim().toUpperCase(),
    destCity: destCity ? String(destCity).trim() : String(destCode).trim().toUpperCase(),
    depTime: depTime ? String(depTime).trim() : '08:00',
    arrTime: arrTime ? String(arrTime).trim() : '10:00',
    duration: duration ? String(duration).trim() : '2j 0m',
    price: Number(price),
    seats: seats ? Number(seats) : 30,
    class: flightClass ? String(flightClass).trim() : 'Ekonomi Standar'
  }

  db.flights.unshift(item)
  writeDB(db)
  res.json({ success: true, message: 'Jadwal tiket penerbangan baru berhasil ditambahkan.', data: item })
})

app.put(['/api/admin/flights/:id', '/api/v1/admin/flights/:id'], authMiddleware, adminOnly, (req, res) => {
  const id = Number(req.params.id)
  const db = readDB()
  const idx = db.flights.findIndex(f => f.id === id)
  if (idx === -1) {
    return res.status(404).json({ success: false, message: 'Penerbangan tidak ditemukan.' })
  }

  db.flights[idx] = {
    ...db.flights[idx],
    ...req.body,
    id
  }
  writeDB(db)
  res.json({ success: true, message: 'Data penerbangan berhasil diperbarui.', data: db.flights[idx] })
})

app.delete(['/api/admin/flights/:id', '/api/v1/admin/flights/:id'], authMiddleware, adminOnly, (req, res) => {
  const id = Number(req.params.id)
  const db = readDB()
  const idx = db.flights.findIndex(f => f.id === id)
  if (idx === -1) {
    return res.status(404).json({ success: false, message: 'Penerbangan tidak ditemukan.' })
  }

  const removed = db.flights.splice(idx, 1)[0]
  writeDB(db)
  res.json({ success: true, message: `Penerbangan "${removed.flightNumber}" telah dihapus.`, data: removed })
})

// --- CRUD BLOG & ARTIKEL (Admin Protected) ---
app.get(['/api/admin/blogs', '/api/v1/admin/blogs'], authMiddleware, adminOnly, (req, res) => {
  const db = readDB()
  res.json({ success: true, data: db.blogs || [] })
})

app.post(['/api/admin/blogs', '/api/v1/admin/blogs'], authMiddleware, adminOnly, (req, res) => {
  const { title, category, author, summary, content, image, readTime } = req.body || {}
  if (!title || !content) {
    return res.status(422).json({ success: false, message: 'Judul artikel dan konten artikel wajib diisi.' })
  }

  const db = readDB()
  if (!db.blogs) db.blogs = []

  const newId = db.blogs.length > 0 ? Math.max(...db.blogs.map(b => b.id)) + 1 : 1
  const today = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })

  const item = {
    id: newId,
    title: String(title).trim(),
    slug: String(title).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''),
    category: category ? String(category).trim() : 'Panduan Wisata',
    author: author ? String(author).trim() : 'Tim Redaksi Violincello',
    date: today,
    readTime: readTime ? String(readTime).trim() : '4 menit baca',
    image: image ? String(image).trim() : 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
    summary: summary ? String(summary).trim() : (content.substring(0, 150) + '...'),
    content: String(content).trim(),
    tags: [category || 'Wisata']
  }

  db.blogs.unshift(item)
  writeDB(db)
  res.json({ success: true, message: 'Artikel blog baru berhasil diterbitkan.', data: item })
})

app.put(['/api/admin/blogs/:id', '/api/v1/admin/blogs/:id'], authMiddleware, adminOnly, (req, res) => {
  const id = Number(req.params.id)
  const db = readDB()
  if (!db.blogs) db.blogs = []

  const idx = db.blogs.findIndex(b => b.id === id)
  if (idx === -1) {
    return res.status(404).json({ success: false, message: 'Artikel blog tidak ditemukan.' })
  }

  db.blogs[idx] = {
    ...db.blogs[idx],
    ...req.body,
    id
  }
  writeDB(db)
  res.json({ success: true, message: 'Artikel blog berhasil diperbarui.', data: db.blogs[idx] })
})

app.delete(['/api/admin/blogs/:id', '/api/v1/admin/blogs/:id'], authMiddleware, adminOnly, (req, res) => {
  const id = Number(req.params.id)
  const db = readDB()
  if (!db.blogs) db.blogs = []

  const idx = db.blogs.findIndex(b => b.id === id)
  if (idx === -1) {
    return res.status(404).json({ success: false, message: 'Artikel blog tidak ditemukan.' })
  }

  const removed = db.blogs.splice(idx, 1)[0]
  writeDB(db)
  res.json({ success: true, message: `Artikel "${removed.title}" telah dihapus.`, data: removed })
})

// Start Server
app.listen(PORT, () => {
  console.log(`[Violincello Backend] API Server berjalan di http://localhost:${PORT}`)
  console.log(`[Violincello Backend] Portal Manajemen Sistem Admin: http://localhost:${PORT}/admin-panel`)
})
