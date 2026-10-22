import fs from 'node:fs'
import path from 'node:path'

export interface Destination {
  id: number
  name: string
  city: string
  country: string
  image: string
  description: string
  rating: number
  popular_badge: string
  createdAt: string
}

export interface Hotel {
  id: number
  destination_id?: number
  name: string
  city: string
  country: string
  stars: number
  rating_score: number
  price: number
  image: string
  tag: string
  amenities: string[]
  total_rooms: number
  available_rooms: number
  description?: string
  createdAt: string
}

export interface Flight {
  id: number
  airlineName: string
  airlineCode: string
  flightNumber: string
  originCode: string
  originCity: string
  destCode: string
  destCity: string
  depTime: string
  arrTime: string
  duration: string
  price: number
  seats: number
  classType: string
  createdAt: string
}

export interface User {
  id: number
  name: string
  email: string
  password: string // in demo plain or hashed
  role: 'admin' | 'user'
  phone?: string
  avatar?: string
}

interface DBData {
  destinations: Destination[]
  hotels: Hotel[]
  flights: Flight[]
  users: User[]
}

const BACKEND_DB_PATH = path.resolve(process.cwd(), 'backend', 'data', 'db.json')
const FALLBACK_DB_PATH = path.resolve(process.cwd(), '.data', 'app_database.json')
const DB_PATH = fs.existsSync(BACKEND_DB_PATH) ? BACKEND_DB_PATH : FALLBACK_DB_PATH

function getInitialData(): DBData {
  return {
    users: [
      {
        id: 1,
        name: 'Administrator OTA',
        email: 'admin@violincello.com',
        password: 'admin123',
        role: 'admin',
        phone: '+6281234567890',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80'
      },
      {
        id: 2,
        name: 'Customer Violincello',
        email: 'user@violincello.com',
        password: 'user123',
        role: 'user',
        phone: '+6289876543210',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80'
      }
    ],
    destinations: [
      {
        id: 1,
        name: 'Pulau Dewata Bali',
        city: 'Denpasar / Badung',
        country: 'Indonesia',
        image: 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=800&q=80',
        description: 'Pesona pantai tropis, pura ikonik, dan budaya seni kelas dunia.',
        rating: 4.9,
        popular_badge: 'Destinasi Terpopuler',
        createdAt: '2026-01-10T10:00:00Z'
      },
      {
        id: 2,
        name: 'Labuan Bajo & Pulau Komodo',
        city: 'Manggarai Barat',
        country: 'Indonesia',
        image: 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=800&q=80',
        description: 'Gugusan pulau eksotis dengan keindahan bawah laut dan habitat purba Komodo.',
        rating: 4.95,
        popular_badge: 'Super Prioritas',
        createdAt: '2026-01-12T10:00:00Z'
      },
      {
        id: 3,
        name: 'Candi Borobudur & Yogyakarta',
        city: 'Magelang / Yogyakarta',
        country: 'Indonesia',
        image: 'https://images.unsplash.com/photo-1596402184320-417e7178b2cd?auto=format&fit=crop&w=800&q=80',
        description: 'Pusat warisan budaya Nusantara yang megah dengan ketenangan lanskap alam.',
        rating: 4.85,
        popular_badge: 'Heritage & Budaya',
        createdAt: '2026-01-15T10:00:00Z'
      },
      {
        id: 4,
        name: 'Raja Ampat Paradise',
        city: 'Papua Barat Daya',
        country: 'Indonesia',
        image: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
        description: 'Surga karts karang dan keanekaragaman hayati laut terbaik di dunia.',
        rating: 5.0,
        popular_badge: 'Premium Diving',
        createdAt: '2026-02-01T10:00:00Z'
      }
    ],
    hotels: [
      {
        id: 1,
        destination_id: 1,
        name: 'Amnaya Resort Kuta',
        city: 'Kuta, Bali',
        country: 'Indonesia',
        stars: 5,
        rating_score: 4.9,
        price: 1850000,
        tag: 'Best Seller',
        image: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80',
        amenities: ['Private Pool', 'Ocean View', 'Breakfast Included', 'Free Ultra WiFi', 'Luxury Spa'],
        total_rooms: 40,
        available_rooms: 12,
        description: 'Resort bintang lima dengan sentuhan arsitektur khas Bali modern dan pelayanan istimewa.',
        createdAt: '2026-01-15T12:00:00Z'
      },
      {
        id: 2,
        destination_id: 3,
        name: 'The Phoenix Heritage Hotel',
        city: 'Yogyakarta',
        country: 'Indonesia',
        stars: 5,
        rating_score: 4.8,
        price: 1250000,
        tag: 'Luxury Heritage',
        image: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
        amenities: ['Vintage Architecture', 'Pool Bar', 'Fitness Center', 'Fine Dining Restaurant'],
        total_rooms: 55,
        available_rooms: 18,
        description: 'Hotel bersejarah bernuansa kolonial anggun di jantung kota Yogyakarta.',
        createdAt: '2026-01-16T12:00:00Z'
      },
      {
        id: 3,
        destination_id: 1,
        name: 'Mandalika Beachfront Villa',
        city: 'Lombok Tengah',
        country: 'Indonesia',
        stars: 4,
        rating_score: 4.7,
        price: 980000,
        tag: 'Trending Resort',
        image: 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=800&q=80',
        amenities: ['White Sand Beach', 'Sunset Terrace', 'Snorkeling Gear', 'Infinity Pool'],
        total_rooms: 25,
        available_rooms: 7,
        description: 'Akomodasi tepi pantai berpasir putih dengan panorama matahari terbenam memukau.',
        createdAt: '2026-01-18T12:00:00Z'
      },
      {
        id: 4,
        destination_id: 2,
        name: 'Ayana Komodo Waecicu Beach',
        city: 'Labuan Bajo',
        country: 'Indonesia',
        stars: 5,
        rating_score: 4.95,
        price: 3200000,
        tag: 'Ultra Luxury',
        image: 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=800&q=80',
        amenities: ['Private Pier', 'Yacht Charter', 'Rooftop Lounge', 'Coral Diving Center'],
        total_rooms: 60,
        available_rooms: 15,
        description: 'Resor mewah bintang lima pertama di Labuan Bajo dengan dermaga pribadi spektakuler.',
        createdAt: '2026-01-20T12:00:00Z'
      }
    ],
    flights: [
      {
        id: 1,
        airlineName: 'Garuda Indonesia',
        airlineCode: 'GA',
        flightNumber: 'GA-404',
        originCode: 'CGK',
        originCity: 'Jakarta',
        destCode: 'DPS',
        destCity: 'Bali',
        depTime: '08:30',
        arrTime: '11:20',
        duration: '1j 50m',
        price: 1450000,
        seats: 32,
        classType: 'Ekonomi Flexi',
        createdAt: '2026-01-10T08:00:00Z'
      },
      {
        id: 2,
        airlineName: 'Batik Air',
        airlineCode: 'ID',
        flightNumber: 'ID-6512',
        originCode: 'CGK',
        originCity: 'Jakarta',
        destCode: 'DPS',
        destCity: 'Bali',
        depTime: '13:15',
        arrTime: '16:05',
        duration: '1j 50m',
        price: 1120000,
        seats: 45,
        classType: 'Ekonomi Standar',
        createdAt: '2026-01-10T08:30:00Z'
      },
      {
        id: 3,
        airlineName: 'Citilink Indonesia',
        airlineCode: 'QG',
        flightNumber: 'QG-680',
        originCode: 'SUB',
        originCity: 'Surabaya',
        destCode: 'LBJ',
        destCity: 'Labuan Bajo',
        depTime: '07:45',
        arrTime: '10:15',
        duration: '1j 30m',
        price: 1280000,
        seats: 28,
        classType: 'Ekonomi Smart',
        createdAt: '2026-01-11T09:00:00Z'
      },
      {
        id: 4,
        airlineName: 'Garuda Indonesia',
        airlineCode: 'GA',
        flightNumber: 'GA-208',
        originCode: 'CGK',
        originCity: 'Jakarta',
        destCode: 'YIA',
        destCity: 'Yogyakarta',
        depTime: '06:10',
        arrTime: '07:25',
        duration: '1j 15m',
        price: 990000,
        seats: 50,
        classType: 'Ekonomi Standar',
        createdAt: '2026-01-12T10:00:00Z'
      }
    ]
  }
}

export function getDBPath(): string {
  const backendPath = path.resolve(process.cwd(), 'backend', 'data', 'db.json')
  if (fs.existsSync(backendPath)) return backendPath
  return path.resolve(process.cwd(), '.data', 'app_database.json')
}

export function readDB(): DBData {
  try {
    const activePath = getDBPath()
    const dir = path.dirname(activePath)
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true })
    }

    if (!fs.existsSync(activePath)) {
      const initial = getInitialData()
      fs.writeFileSync(activePath, JSON.stringify(initial, null, 2), 'utf-8')
      return initial
    }

    const content = fs.readFileSync(activePath, 'utf-8')
    return JSON.parse(content) as DBData
  } catch (err) {
    console.error('Error reading DB:', err)
    return getInitialData()
  }
}

export function writeDB(data: DBData): void {
  try {
    const activePath = getDBPath()
    const dir = path.dirname(activePath)
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true })
    }
    fs.writeFileSync(activePath, JSON.stringify(data, null, 2), 'utf-8')
  } catch (err) {
    console.error('Error writing DB:', err)
  }
}

// Token helper: encode simple auth token
export function generateToken(user: User): string {
  const payload = {
    id: user.id,
    email: user.email,
    role: user.role,
    name: user.name,
    exp: Date.now() + 24 * 60 * 60 * 1000
  }
  return Buffer.from(JSON.stringify(payload)).toString('base64')
}

export function verifyToken(token: string): { id: number; email: string; role: string; name: string } | null {
  try {
    const decoded = Buffer.from(token, 'base64').toString('utf-8')
    const parsed = JSON.parse(decoded)
    if (parsed.exp && parsed.exp < Date.now()) {
      return null
    }
    return parsed
  } catch {
    return null
  }
}
