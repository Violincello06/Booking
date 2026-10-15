<template>
  <div class="travel-app">
    <!-- Top Glassmorphism Navigation Bar -->
    <header class="navbar glass-panel">
      <div class="container nav-content">
        <div class="brand-group">
          <div class="brand-logo">
            <span class="logo-icon">🎻</span>
          </div>
          <div>
            <h2 class="brand-title">Violincello <span class="text-gradient">Travel</span></h2>
            <p class="brand-subtitle">Online Travel & Reservation System</p>
          </div>
        </div>

        <nav class="nav-links">
          <a href="#search" class="nav-link active">Eksplorasi</a>
          <a href="#hotels" class="nav-link">Hotel</a>
          <a href="#flights" class="nav-link">Penerbangan</a>
          <a href="#vouchers" class="nav-link">Promo & Voucher</a>
        </nav>

        <div class="nav-actions">
          <div class="notification-container">
            <button class="btn btn-icon btn-glass" id="btn-notifications" @click="showNotifs = !showNotifs" title="Notifikasi">
              <span class="bell-icon">🔔</span>
              <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount }}</span>
            </button>

            <!-- Notifications Dropdown -->
            <div v-if="showNotifs" class="notif-dropdown glass-card animate-fade-in">
              <div class="notif-header">
                <span class="notif-title">Notifikasi Terkini</span>
                <button v-if="unreadCount > 0" @click="markAllAsRead" class="btn-link-sm">Tandai sudah dibaca</button>
              </div>
              <div class="notif-list">
                <div v-for="notif in notifications" :key="notif.id" class="notif-item" :class="{ 'unread': !notif.is_read }">
                  <div class="notif-type-icon">{{ notif.type === 'payment' ? '💳' : notif.type === 'booking' ? '🏨' : '🎉' }}</div>
                  <div class="notif-content">
                    <div class="notif-item-title">{{ notif.title }}</div>
                    <div class="notif-item-msg">{{ notif.message }}</div>
                    <div class="notif-item-time">{{ notif.time }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button class="btn btn-glass" id="btn-login">Masuk</button>
          <button class="btn btn-primary" id="btn-register">Daftar Akun</button>
        </div>
      </div>
    </header>

    <!-- Hero Section with Dynamic Search Engine -->
    <main class="container hero-section">
      <div class="hero-header text-center">
        <div class="badge badge-primary animate-float">✨ OTA Travel Booking Platform</div>
        <h1 class="hero-title">
          Jelajahi Dunia Bersama <br />
          <span class="text-gradient">Violincello Premium Experience</span>
        </h1>
        <p class="hero-desc">
          Temukan ribuan akomodasi hotel terbaik, tiket pesawat termurah, dan promo voucher eksklusif dalam satu sentuhan.
        </p>
      </div>

      <!-- Live Search & Filter Widget -->
      <section class="search-widget glass-panel" id="search">
        <div class="tab-nav">
          <button 
            class="tab-btn" 
            :class="{ active: activeTab === 'hotel' }"
            @click="activeTab = 'hotel'"
            id="tab-hotel"
          >
            <span>🏨</span> Cari Hotel
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: activeTab === 'flight' }"
            @click="activeTab = 'flight'"
            id="tab-flight"
          >
            <span>✈️</span> Tiket Pesawat
          </button>
        </div>

        <!-- Hotel Search Form -->
        <div v-if="activeTab === 'hotel'" class="search-grid">
          <div class="form-group">
            <label class="form-label" for="hotel-city">Tujuan / Nama Hotel</label>
            <input 
              id="hotel-city" 
              type="text" 
              v-model="hotelSearch.query"
              class="form-control" 
              placeholder="Contoh: Bali, Lombok, Amnaya" 
            />
          </div>
          <div class="form-group">
            <label class="form-label" for="hotel-rating">Kategori Bintang</label>
            <select id="hotel-rating" v-model="hotelSearch.minRating" class="form-control">
              <option value="">Semua Bintang</option>
              <option value="5">Bintang 5 (Luxury)</option>
              <option value="4">Bintang 4+</option>
              <option value="3">Bintang 3+</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="hotel-sort">Urutkan</label>
            <select id="hotel-sort" v-model="hotelSearch.sortBy" class="form-control">
              <option value="latest">Rekomendasi Terbaru</option>
              <option value="price_asc">Harga Terendah</option>
              <option value="price_desc">Harga Tertinggi</option>
              <option value="rating_desc">Rating Tertinggi</option>
            </select>
          </div>
          <div class="form-group">
            <button class="btn btn-primary btn-search" id="btn-search-hotel">
              <span>🔍</span> Cari Hotel
            </button>
          </div>
        </div>

        <!-- Flight Search Form -->
        <div v-else class="search-grid">
          <div class="form-group">
            <label class="form-label" for="flight-origin">Dari (Bandara Asal)</label>
            <input 
              id="flight-origin" 
              type="text" 
              v-model="flightSearch.origin"
              class="form-control" 
              placeholder="CGK (Jakarta)" 
            />
          </div>
          <div class="form-group">
            <label class="form-label" for="flight-destination">Ke (Bandara Tujuan)</label>
            <input 
              id="flight-destination" 
              type="text" 
              v-model="flightSearch.destination"
              class="form-control" 
              placeholder="DPS (Bali)" 
            />
          </div>
          <div class="form-group">
            <label class="form-label" for="flight-date">Tanggal Terbang</label>
            <input 
              id="flight-date" 
              type="date" 
              v-model="flightSearch.date"
              class="form-control" 
            />
          </div>
          <div class="form-group">
            <button class="btn btn-glow btn-search" id="btn-search-flight">
              <span>✈️</span> Cari Tiket
            </button>
          </div>
        </div>
      </section>

      <!-- Featured Section: Luxury Hotels Preview -->
      <section class="catalog-section" id="hotels">
        <div class="section-header">
          <div>
            <span class="badge badge-primary">Rekomendasi Populer</span>
            <h2 class="section-title">Hotel & Resort Pilihan</h2>
          </div>
          <button class="btn btn-glass">Lihat Semua Hotel →</button>
        </div>

        <div class="cards-grid">
          <div v-for="hotel in sampleHotels" :key="hotel.id" class="glass-card hotel-card">
            <div class="hotel-media">
              <img :src="hotel.image" :alt="hotel.name" class="hotel-img" />
              <div class="hotel-badge-floating">
                <span class="badge badge-success">{{ hotel.tag }}</span>
              </div>
            </div>
            <div class="hotel-content">
              <h3 class="hotel-title">{{ hotel.name }}</h3>
              <p class="hotel-location">📍 {{ hotel.city }}, {{ hotel.country }}</p>
              
              <div class="star-rating">
                <span v-for="star in hotel.stars" :key="star">★</span>
                <span style="color: var(--text-dim); margin-left: 0.4rem; font-size: 0.8rem">({{ hotel.ratingScore }} / 5.0)</span>
              </div>

              <div class="amenities-list">
                <span v-for="amenity in hotel.amenities" :key="amenity" class="amenity-chip">
                  {{ amenity }}
                </span>
              </div>

              <div class="hotel-footer">
                <div class="price-box">
                  <span class="price-sub">Mulai dari / malam</span>
                  <span class="price-amount">Rp {{ hotel.price.toLocaleString('id-ID') }}</span>
                </div>
                <button class="btn btn-primary" :id="'btn-book-hotel-' + hotel.id">Pilih Kamar</button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Featured Section: Flights Preview -->
      <section class="catalog-section" id="flights">
        <div class="section-header">
          <div>
            <span class="badge badge-warning">Jadwal Favorit</span>
            <h2 class="section-title">Penerbangan Domestik & Internasional</h2>
          </div>
          <button class="btn btn-glass">Cek Rute Lainnya →</button>
        </div>

        <div class="flights-list">
          <div v-for="flight in sampleFlights" :key="flight.id" class="glass-panel flight-card">
            <div class="flight-info-group">
              <div class="airline-badge">
                <div class="airline-icon">{{ flight.airlineCode }}</div>
                <div>
                  <h4 style="font-size: 1.05rem;">{{ flight.airlineName }}</h4>
                  <span class="badge badge-primary" style="font-size: 0.7rem;">{{ flight.flightNumber }}</span>
                </div>
              </div>

              <div class="flight-route">
                <div class="route-endpoint">
                  <div class="time-val">{{ flight.depTime }}</div>
                  <div class="airport-val">{{ flight.originCode }} ({{ flight.originCity }})</div>
                </div>

                <div class="route-timeline">
                  <span class="duration-pill">{{ flight.duration }}</span>
                  <div class="route-line"></div>
                  <span style="font-size: 0.7rem; color: var(--accent-emerald);">Langsung (Direct)</span>
                </div>

                <div class="route-endpoint">
                  <div class="time-val">{{ flight.arrTime }}</div>
                  <div class="airport-val">{{ flight.destCode }} ({{ flight.destCity }})</div>
                </div>
              </div>
            </div>

            <div class="hotel-footer" style="border: none; padding: 0;">
              <div class="price-box" style="margin-right: 1.5rem; text-align: right;">
                <span class="price-sub">Harga per penumpang</span>
                <span class="price-amount">Rp {{ flight.price.toLocaleString('id-ID') }}</span>
              </div>
              <button class="btn btn-glow" :id="'btn-book-flight-' + flight.id">Pilih Tiket</button>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="footer glass-panel">
      <div class="container footer-content text-center">
        <p style="color: var(--text-muted); font-size: 0.9rem;">
          © 2026 Violincello Travel & Reservation System. Powered by Laravel 13 REST API & Nuxt.
        </p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const activeTab = ref('hotel')

const hotelSearch = ref({
  query: '',
  minRating: '',
  sortBy: 'latest'
})

const flightSearch = ref({
  origin: '',
  destination: '',
  date: ''
})

const sampleHotels = ref([
  {
    id: 1,
    name: 'Amnaya Resort Kuta',
    city: 'Kuta, Bali',
    country: 'Indonesia',
    stars: 5,
    ratingScore: 4.9,
    tag: 'Best Seller',
    image: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80',
    amenities: ['Private Pool', 'Ocean View', 'Breakfast', 'Free WiFi', 'Spa'],
    price: 1850000
  },
  {
    id: 2,
    name: 'The Phoenix Heritage Hotel',
    city: 'Yogyakarta',
    country: 'Indonesia',
    stars: 5,
    ratingScore: 4.8,
    tag: 'Luxury Heritage',
    image: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
    amenities: ['Vintage Architecture', 'Pool Bar', 'Fitness Center', 'Fine Dining'],
    price: 1250000
  },
  {
    id: 3,
    name: 'Mandalika Beachfront Villa',
    city: 'Lombok Tengah',
    country: 'Indonesia',
    stars: 4,
    ratingScore: 4.7,
    tag: 'Trending',
    image: 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=800&q=80',
    amenities: ['White Sand Beach', 'Sunset Terrace', 'Snorkeling Gear'],
    price: 980000
  }
])

const sampleFlights = ref([
  {
    id: 1,
    airlineName: 'Garuda Indonesia',
    airlineCode: 'GA',
    flightNumber: 'GA-404',
    depTime: '08:30',
    originCode: 'CGK',
    originCity: 'Jakarta',
    arrTime: '11:20',
    destCode: 'DPS',
    destCity: 'Bali',
    duration: '1j 50m',
    price: 1450000
  },
  {
    id: 2,
    airlineName: 'Batik Air',
    airlineCode: 'ID',
    flightNumber: 'ID-6512',
    depTime: '13:15',
    originCode: 'CGK',
    originCity: 'Jakarta',
    arrTime: '16:05',
    destCode: 'DPS',
    destCity: 'Bali',
    duration: '1j 50m',
    price: 1120000
  }
])

// Notification state
const showNotifs = ref(false)
const notifications = ref([
  {
    id: 1,
    title: 'Pembayaran Berhasil Dikonfirmasi',
    message: 'Pembayaran untuk pemesanan BK-HTL-849102 sebesar Rp 2.500.000 telah lunas. E-voucher hotel telah terbit.',
    type: 'payment',
    time: '5 menit lalu',
    is_read: false
  },
  {
    id: 2,
    title: 'Pemesanan Hotel Berhasil',
    message: 'Reservasi hotel Grand Melia Resort berhasil dibuat. Selesaikan pembayaran sebelum batas waktu.',
    type: 'booking',
    time: '1 jam lalu',
    is_read: false
  },
  {
    id: 3,
    title: 'Promo Spesial Liburan 10.10',
    message: 'Gunakan kode promo VACATION10 untuk mendapatkan potongan instan 10% hingga Rp 500.000!',
    type: 'promo',
    time: '1 hari lalu',
    is_read: true
  }
])

const unreadCount = computed(() => notifications.value.filter(n => !n.is_read).length)

const markAllAsRead = () => {
  notifications.value.forEach(n => { n.is_read = true })
}
</script>

<style scoped>
.travel-app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.navbar {
  position: sticky;
  top: 1rem;
  z-index: 100;
  margin: 1rem 1.5rem;
  padding: 0.85rem 1.5rem;
}

.nav-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  position: relative;
}

.notification-container {
  position: relative;
}

.btn-icon {
  width: 40px;
  height: 40px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  font-size: 1.15rem;
}

.notif-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: var(--accent-gradient, linear-gradient(135deg, #f43f5e, #e11d48));
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  box-shadow: 0 2px 6px rgba(244, 63, 94, 0.4);
}

.notif-dropdown {
  position: absolute;
  top: calc(100% + 0.75rem);
  right: 0;
  width: 340px;
  background: rgba(15, 23, 42, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: var(--radius-md, 12px);
  padding: 1rem;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
  z-index: 200;
}

.notif-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.notif-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #fff;
}

.btn-link-sm {
  background: none;
  border: none;
  color: var(--primary, #3b82f6);
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
}

.btn-link-sm:hover {
  text-decoration: underline;
}

.notif-list {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  margin-top: 0.75rem;
  max-height: 320px;
  overflow-y: auto;
}

.notif-item {
  display: flex;
  gap: 0.75rem;
  padding: 0.65rem;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.03);
  transition: all 0.2s ease;
}

.notif-item.unread {
  background: rgba(59, 130, 246, 0.1);
  border-left: 3px solid #3b82f6;
}

.notif-type-icon {
  font-size: 1.25rem;
}

.notif-content {
  flex: 1;
}

.notif-item-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: #fff;
}

.notif-item-msg {
  font-size: 0.75rem;
  color: var(--text-muted, #94a3b8);
  margin-top: 0.2rem;
  line-height: 1.35;
}

.notif-item-time {
  font-size: 0.68rem;
  color: var(--text-dim, #64748b);
  margin-top: 0.35rem;
}

.brand-group {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.brand-logo {
  width: 44px;
  height: 44px;
  background: var(--primary-gradient);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 15px rgba(37, 99, 235, 0.4);
}

.logo-icon {
  font-size: 1.4rem;
}

.brand-title {
  font-size: 1.35rem;
  letter-spacing: -0.02em;
}

.brand-subtitle {
  font-size: 0.72rem;
  color: var(--text-dim);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.nav-links {
  display: flex;
  gap: 1.75rem;
}

.nav-link {
  color: var(--text-muted);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.95rem;
  transition: color var(--transition-fast);
}

.nav-link:hover, .nav-link.active {
  color: #38bdf8;
}

.nav-actions {
  display: flex;
  gap: 0.85rem;
}

.hero-section {
  padding-top: 3.5rem;
  padding-bottom: 4rem;
}

.hero-header {
  margin-bottom: 2.75rem;
}

.text-center {
  text-align: center;
}

.hero-title {
  font-size: clamp(2.2rem, 5vw, 3.6rem);
  margin-top: 1rem;
  margin-bottom: 1.25rem;
  letter-spacing: -0.03em;
}

.hero-desc {
  max-width: 650px;
  margin: 0 auto;
  color: var(--text-muted);
  font-size: 1.1rem;
}

.btn-search {
  height: 52px;
  width: 100%;
}

.catalog-section {
  margin-top: 5rem;
}

.section-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  margin-bottom: 2rem;
}

.section-title {
  font-size: 2rem;
  margin-top: 0.4rem;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 2rem;
}

.flights-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.footer {
  margin-top: auto;
  border-radius: 0;
  border-left: none;
  border-right: none;
  border-bottom: none;
  padding: 2rem 0;
}

@media (max-width: 900px) {
  .nav-links {
    display: none;
  }
}
</style>
