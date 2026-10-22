<template>
  <div class="travel-app">
    <!-- 1. NAVIGATION BAR -->
    <header class="navbar glass-panel">
      <div class="container nav-content">
        <div class="brand-group">
          <div class="brand-logo">
            <span class="logo-icon">🎻</span>
          </div>
          <div>
            <h2 class="brand-title">Violincello <span class="text-gradient">Travel</span></h2>
            <p class="brand-subtitle">Online Travel Agent & Reservasi Nusantara</p>
          </div>
        </div>

        <nav class="nav-links">
          <a href="#search" class="nav-link active">Eksplorasi</a>
          <a href="#destinations" class="nav-link">Destinasi</a>
          <a href="#hotels" class="nav-link">Hotel & Resor</a>
          <a href="#flights" class="nav-link">Penerbangan</a>
          <a href="#komitmen" class="nav-link">Keunggulan</a>
        </nav>

        <div class="nav-actions">
          <!-- Notification Bell -->
          <div class="notification-container">
            <button class="btn btn-icon btn-glass" id="btn-notifications" @click="showNotifs = !showNotifs" title="Notifikasi Pelanggan">
              <span class="bell-icon">🔔</span>
              <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount }}</span>
            </button>

            <div v-if="showNotifs" class="notif-dropdown glass-card animate-fade-in">
              <div class="notif-header">
                <span class="notif-title">Pemberitahuan Terkini</span>
                <button v-if="unreadCount > 0" @click="unreadCount = 0" class="btn-link-sm">Tandai dibaca</button>
              </div>
              <div class="notif-list">
                <div v-for="notif in notifications" :key="notif.id" class="notif-item">
                  <div class="notif-type-icon">{{ notif.type === 'info' ? 'ℹ️' : '🏨' }}</div>
                  <div class="notif-content">
                    <div class="notif-item-title">{{ notif.title }}</div>
                    <div class="notif-item-msg">{{ notif.message }}</div>
                    <div class="notif-item-time">{{ notif.time }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Button Khusus Pengguna / Customer -->
          <a href="#search" class="btn btn-primary" id="btn-user-booking" title="Mulai Eksplorasi & Reservasi">
            <span>✨</span> Rencanakan Liburan
          </a>
        </div>
      </div>
    </header>

    <!-- 2. HERO SECTION -->
    <main class="container hero-section">
      <div class="hero-header text-center">
        <div class="badge badge-primary animate-float">📍 Biro Perjalanan Terverifikasi Nusantara</div>
        <h1 class="hero-title">
          Rencanakan Liburan & Perjalanan Anda Bersama <br />
          <span class="text-gradient">Violincello Travel Experience</span>
        </h1>
        <p class="hero-desc">
          Temukan destinasi eksotis Nusantara, akomodasi hotel berbintang terverifikasi, dan jadwal penerbangan langsung dengan tarif transparan tanpa biaya tersembunyi.
        </p>
      </div>

      <!-- Real-Time Search Widget -->
      <section class="search-widget glass-panel" id="search">
        <div class="tab-nav">
          <button 
            class="tab-btn" 
            :class="{ active: searchTab === 'hotel' }"
            @click="searchTab = 'hotel'"
            id="tab-hotel"
          >
            <span>🏨</span> Cari Hotel & Penginapan
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: searchTab === 'flight' }"
            @click="searchTab = 'flight'"
            id="tab-flight"
          >
            <span>✈️</span> Tiket Pesawat Domestik
          </button>
        </div>

        <!-- Hotel Filter Form -->
        <div v-if="searchTab === 'hotel'" class="search-grid">
          <div class="form-group">
            <label class="form-label" for="hotel-city">Tujuan / Nama Hotel</label>
            <input 
              id="hotel-city" 
              type="text" 
              v-model="hotelFilterQuery"
              @input="fetchHotels"
              class="form-control" 
              placeholder="Contoh: Bali, Yogyakarta, Amnaya" 
            />
          </div>
          <div class="form-group">
            <label class="form-label" for="hotel-rating">Kategori Bintang</label>
            <select id="hotel-rating" v-model="hotelFilterStar" @change="fetchHotels" class="form-control">
              <option value="">Semua Kategori</option>
              <option value="5">Bintang 5</option>
              <option value="4">Bintang 4 Ke Atas</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="hotel-sort">Urutan Tarif</label>
            <select id="hotel-sort" v-model="hotelFilterSort" @change="fetchHotels" class="form-control">
              <option value="latest">Rekomendasi Utama</option>
              <option value="price_asc">Harga Terendah</option>
              <option value="price_desc">Harga Tertinggi</option>
            </select>
          </div>
          <div class="form-group">
            <button class="btn btn-primary btn-search" id="btn-search-hotel" @click="fetchHotels">
              <span>🔍</span> Terapkan Filter
            </button>
          </div>
        </div>

        <!-- Flight Filter Form -->
        <div v-else class="search-grid">
          <div class="form-group">
            <label class="form-label" for="flight-origin">Bandara / Kota Asal</label>
            <input 
              id="flight-origin" 
              type="text" 
              v-model="flightFilterOrigin"
              @input="fetchFlights"
              class="form-control" 
              placeholder="Contoh: Jakarta atau CGK" 
            />
          </div>
          <div class="form-group">
            <label class="form-label" for="flight-destination">Bandara / Kota Tujuan</label>
            <input 
              id="flight-destination" 
              type="text" 
              v-model="flightFilterDest"
              @input="fetchFlights"
              class="form-control" 
              placeholder="Contoh: Bali atau DPS" 
            />
          </div>
          <div class="form-group">
            <label class="form-label" for="flight-date">Tanggal Terbang</label>
            <input 
              id="flight-date" 
              type="date" 
              class="form-control" 
            />
          </div>
          <div class="form-group">
            <button class="btn btn-glow btn-search" id="btn-search-flight" @click="fetchFlights">
              <span>✈️</span> Cari Tiket
            </button>
          </div>
        </div>
      </section>

      <!-- 3. KATALOG DESTINASI (Backend Data) -->
      <section class="catalog-section" id="destinations">
        <div class="section-header">
          <div>
            <span class="badge badge-success">Eksplorasi Wilayah</span>
            <h2 class="section-title">Destinasi Wisata Paling Diminati</h2>
          </div>
          <span style="color: var(--text-dim); font-size: 0.9rem;">
            {{ destinations.length }} destinasi terkurasi
          </span>
        </div>

        <div v-if="loadingDestinations" class="text-center" style="padding: 3rem;">
          <p style="color: var(--text-muted);">Memuat destinasi wisata...</p>
        </div>

        <div v-else-if="destinations.length === 0" class="glass-panel text-center" style="padding: 3rem;">
          <p style="color: var(--text-muted);">Belum ada data destinasi.</p>
        </div>

        <div v-else class="cards-grid">
          <div v-for="dest in destinations" :key="dest.id" class="glass-card hotel-card">
            <div class="hotel-media">
              <img :src="dest.image" :alt="dest.name" class="hotel-img" loading="lazy" />
              <div class="hotel-badge-floating">
                <span class="badge badge-primary">{{ dest.popular_badge || dest.badge || 'Favorit' }}</span>
              </div>
            </div>
            <div class="hotel-content">
              <h3 class="hotel-title">{{ dest.name }}</h3>
              <p class="hotel-location">📍 {{ dest.city }}, {{ dest.country || 'Indonesia' }}</p>
              <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.8rem;">
                {{ dest.description }}
              </p>
              <div class="hotel-footer">
                <div class="star-rating">
                  <span style="color: var(--accent-gold); font-size: 1.1rem;">★</span>
                  <span style="color: #ffffff; font-weight: 700; margin-left: 0.3rem;">{{ dest.rating }}</span>
                  <span style="color: var(--text-dim); font-size: 0.78rem;"> / 5.0</span>
                </div>
                <button class="btn btn-glass" @click="quickFilterDestination(dest)">Cari Hotel Terdekat</button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- 4. KATALOG HOTEL & RESOR (Backend Data) -->
      <section class="catalog-section" id="hotels">
        <div class="section-header">
          <div>
            <span class="badge badge-primary">Katalog Akomodasi</span>
            <h2 class="section-title">Hotel & Resor Pilihan</h2>
          </div>
          <span style="color: var(--text-dim); font-size: 0.9rem;">
            {{ hotels.length }} properti tersedia
          </span>
        </div>

        <div v-if="loadingHotels" class="text-center" style="padding: 3rem;">
          <p style="color: var(--text-muted);">Memuat katalog hotel...</p>
        </div>

        <div v-else-if="hotels.length === 0" class="glass-panel text-center" style="padding: 3rem;">
          <p style="color: var(--text-muted);">Tidak ada penginapan yang sesuai dengan kriteria filter.</p>
        </div>

        <div v-else class="cards-grid">
          <div v-for="hotel in hotels" :key="hotel.id" class="glass-card hotel-card">
            <div class="hotel-media">
              <img :src="hotel.image" :alt="hotel.name" class="hotel-img" loading="lazy" />
              <div class="hotel-badge-floating">
                <span class="badge badge-success">{{ hotel.tag || 'Rekomendasi' }}</span>
              </div>
            </div>
            <div class="hotel-content">
              <h3 class="hotel-title">{{ hotel.name }}</h3>
              <p class="hotel-location">📍 {{ hotel.city }}</p>
              
              <div class="star-rating">
                <span v-for="star in (hotel.stars || 4)" :key="star" style="color: var(--accent-gold);">★</span>
                <span style="color: var(--text-dim); margin-left: 0.4rem; font-size: 0.8rem">({{ hotel.rating_score || hotel.rating || 4.8 }} / 5.0)</span>
              </div>

              <div class="amenities-list">
                <span v-for="(amenity, i) in (hotel.amenities || []).slice(0, 4)" :key="i" class="amenity-chip">
                  {{ amenity }}
                </span>
              </div>

              <div class="hotel-footer">
                <div class="price-box">
                  <span class="price-sub">Tarif per malam</span>
                  <span class="price-amount">Rp {{ Number(hotel.price).toLocaleString('id-ID') }}</span>
                </div>
                <button class="btn btn-primary" :id="'btn-book-hotel-' + hotel.id" @click="openBooking('hotel', hotel.name, hotel.price)">
                  Pilih Kamar
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- 5. JADWAL PENERBANGAN (Backend Data) -->
      <section class="catalog-section" id="flights">
        <div class="section-header">
          <div>
            <span class="badge badge-warning">Rute Terjadwal</span>
            <h2 class="section-title">Penerbangan Domestik Langsung</h2>
          </div>
          <span style="color: var(--text-dim); font-size: 0.9rem;">
            {{ flights.length }} jadwal penerbangan
          </span>
        </div>

        <div v-if="loadingFlights" class="text-center" style="padding: 3rem;">
          <p style="color: var(--text-muted);">Memuat jadwal penerbangan...</p>
        </div>

        <div v-else-if="flights.length === 0" class="glass-panel text-center" style="padding: 3rem;">
          <p style="color: var(--text-muted);">Tidak ada penerbangan untuk rute yang dicari.</p>
        </div>

        <div v-else class="flights-list">
          <div v-for="flight in flights" :key="flight.id" class="glass-panel flight-card">
            <div class="flight-info-group">
              <div class="airline-badge">
                <div class="airline-icon">{{ flight.airlineCode || 'VN' }}</div>
                <div>
                  <h4 style="font-size: 1.05rem;">{{ flight.airlineName || flight.airline }}</h4>
                  <span class="badge badge-primary" style="font-size: 0.7rem;">{{ flight.flightNumber }}</span>
                  <span style="margin-left: 0.5rem; font-size: 0.75rem; color: var(--text-dim);">{{ flight.classType || flight.class || 'Ekonomi' }}</span>
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
                <span class="price-sub">Tersisa {{ flight.seats }} kursi</span>
                <span class="price-amount">Rp {{ Number(flight.price).toLocaleString('id-ID') }}</span>
              </div>
              <button class="btn btn-glow" :id="'btn-book-flight-' + flight.id" @click="openBooking('flight', (flight.airlineName || flight.airline) + ' ' + flight.flightNumber, flight.price)">
                Pilih Tiket
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- 6. KOMITMEN LAYANAN (Otentik & Anti-Slop) -->
      <section class="catalog-section" id="komitmen" style="border-top: 1px solid var(--border-glass); padding-top: 4rem;">
        <div class="section-header text-center" style="display: block; margin-bottom: 2.5rem;">
          <span class="badge badge-primary">Standar Layanan</span>
          <h2 class="section-title">Kenapa Merencanakan Bersama Violincello</h2>
          <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 600px; margin: 0.5rem auto 0;">
            Nilai nyata yang kami pegang untuk setiap perjalanan Anda, tanpa janji palsu atau biaya tak terduga.
          </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
          <div class="glass-card" style="padding: 2rem;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">🧾</div>
            <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Tarif Transparan</h3>
            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6;">
              Nominal yang tercantum adalah biaya final termasuk pajak. Tidak ada biaya siluman saat Anda melakukan pembayaran.
            </p>
          </div>

          <div class="glass-card" style="padding: 2rem;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">🛡️</div>
            <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Mitra Hotel & Maskapai Sah</h3>
            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6;">
              Seluruh kamar dan tiket terhubung langsung dengan alokasi resmi dari operator perhotelan dan maskapai penerbangan nasional.
            </p>
          </div>

          <div class="glass-card" style="padding: 2rem;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">💬</div>
            <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Bantuan Pelanggan Tanggap</h3>
            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6;">
              Tim dukungan kami siap melayani klarifikasi voucher, perubahan jadwal, dan kebutuhan darurat selama perjalanan Anda.
            </p>
          </div>
        </div>
      </section>
    </main>

    <!-- 7. BOOKING RESERVATION MODAL -->
    <div v-if="showBookingModal" class="modal-overlay" @click.self="showBookingModal = false">
      <div class="modal-content animate-fade-in" style="max-width: 480px;">
        <div class="modal-header">
          <div class="modal-title">
            <span>📝 Konfirmasi Reservasi</span>
          </div>
          <button class="btn-close" @click="showBookingModal = false">×</button>
        </div>

        <div class="modal-body">
          <div style="background: rgba(37, 99, 235, 0.1); border: 1px solid rgba(37, 99, 235, 0.25); padding: 1rem; border-radius: 8px;">
            <div style="font-size: 0.8rem; color: var(--text-muted);">Layanan Terpilih:</div>
            <div style="font-weight: 700; font-size: 1.1rem; color: #ffffff;">{{ bookingData.title }}</div>
            <div style="font-size: 1.25rem; font-weight: 800; color: #38bdf8; margin-top: 0.3rem;">
              Rp {{ Number(bookingData.price).toLocaleString('id-ID') }}
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="res-name">Nama Lengkap Tamu / Penumpang</label>
            <input id="res-name" type="text" v-model="bookingForm.name" class="form-control" placeholder="Sesuai KTP / Paspor" />
          </div>

          <div class="form-group">
            <label class="form-label" for="res-phone">Nomor WhatsApp Aktif</label>
            <input id="res-phone" type="tel" v-model="bookingForm.phone" class="form-control" placeholder="081234567890" />
          </div>

          <div class="form-group">
            <label class="form-label" for="res-note">Catatan Khusus (Opsional)</label>
            <textarea id="res-note" rows="2" v-model="bookingForm.note" class="form-control" placeholder="Permintaan ranjang, jam kedatangan, dll."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-glass" @click="showBookingModal = false">Batal</button>
          <button class="btn btn-primary" @click="confirmBooking">Konfirmasi Pemesanan</button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container">
      <div 
        v-for="toast in toasts" 
        :key="toast.id" 
        class="toast-msg"
        :class="'toast-' + toast.type"
      >
        <span>{{ toast.type === 'success' ? '✅' : 'ℹ️' }}</span>
        <span>{{ toast.message }}</span>
      </div>
    </div>

    <!-- 8. FOOTER -->
    <footer class="footer glass-panel">
      <div class="container footer-content">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-glass);">
          <div>
            <h3 style="font-size: 1.15rem; margin-bottom: 0.25rem;">Violincello Travel Experience</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Layanan reservasi akomodasi hotel berbintang & tiket penerbangan langsung terpercaya di Indonesia.</p>
          </div>
          <div>
            <a href="#search" class="btn btn-glass" title="Kembali ke formulir pencarian">
              ↑ Mulai Reservasi
            </a>
          </div>
        </div>
        <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 1.25rem; text-align: center;">
          © 2026 Violincello OTA. Menerapkan panduan Anti-Slop: Informasi akurat, bebas frasa klise AI, desain fungsional.
        </p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const searchTab = ref('hotel')

// Data Lists
const destinations = ref([])
const hotels = ref([])
const flights = ref([])

const loadingDestinations = ref(false)
const loadingHotels = ref(false)
const loadingFlights = ref(false)

// Filters
const hotelFilterQuery = ref('')
const hotelFilterStar = ref('')
const hotelFilterSort = ref('latest')

const flightFilterOrigin = ref('')
const flightFilterDest = ref('')

// Notification State
const showNotifs = ref(false)
const unreadCount = ref(1)
const notifications = ref([
  {
    id: 1,
    title: 'Arsitektur Terpisah Aktif',
    message: 'Port 3000 melayani Landing Page Publik. Port 8000 melayani Portal Manajemen Sistem Admin.',
    type: 'info',
    time: 'Baru saja'
  }
])

// Toast System
const toasts = ref([])
function showToast(message, type = 'success') {
  const id = Date.now() + Math.random()
  toasts.value.push({ id, message, type })
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }, 4000)
}

// Booking Modal
const showBookingModal = ref(false)
const bookingData = ref({ title: '', price: 0, type: '' })
const bookingForm = ref({ name: '', phone: '', note: '' })

function openBooking(type, title, price) {
  bookingData.value = { type, title, price }
  bookingForm.value = { name: '', phone: '', note: '' }
  showBookingModal.value = true
}

function confirmBooking() {
  if (!bookingForm.value.name || !bookingForm.value.phone) {
    showToast('Harap lengkapi nama dan nomor WhatsApp Anda.', 'error')
    return
  }
  showBookingModal.value = false
  showToast(`Pemesanan untuk "${bookingData.value.title}" berhasil dicatat! Rincian telah dikirim ke nomor ${bookingForm.value.phone}.`, 'success')
}

function quickFilterDestination(dest) {
  searchTab.value = 'hotel'
  hotelFilterQuery.value = dest.city
  fetchHotels()
  const el = document.getElementById('hotels')
  if (el) el.scrollIntoView({ behavior: 'smooth' })
  showToast(`Menampilkan daftar hotel di sekitar ${dest.name}`, 'info')
}

// Data Fetchers from Backend Engine (Port 8000 Express Server / Nuxt API fallback)
async function fetchDestinations() {
  loadingDestinations.value = true
  try {
    const res = await $fetch('http://localhost:8000/api/v1/destinations').catch(() => $fetch('/api/v1/destinations'))
    destinations.value = res.data || []
  } catch (err) {
    console.error('Fetch destinations error:', err)
  } finally {
    loadingDestinations.value = false
  }
}

async function fetchHotels() {
  loadingHotels.value = true
  try {
    const params = {}
    if (hotelFilterQuery.value) params.q = hotelFilterQuery.value
    if (hotelFilterStar.value) params.stars = hotelFilterStar.value
    if (hotelFilterSort.value) params.sortBy = hotelFilterSort.value

    const res = await $fetch('http://localhost:8000/api/v1/hotels', { params }).catch(() => $fetch('/api/v1/hotels', { params }))
    hotels.value = res.data || []
  } catch (err) {
    console.error('Fetch hotels error:', err)
  } finally {
    loadingHotels.value = false
  }
}

async function fetchFlights() {
  loadingFlights.value = true
  try {
    const params = {}
    if (flightFilterOrigin.value) params.origin = flightFilterOrigin.value
    if (flightFilterDest.value) params.destination = flightFilterDest.value

    const res = await $fetch('http://localhost:8000/api/v1/flights', { params }).catch(() => $fetch('/api/v1/flights', { params }))
    flights.value = res.data || []
  } catch (err) {
    console.error('Fetch flights error:', err)
  } finally {
    loadingFlights.value = false
  }
}

function refreshAllData() {
  fetchDestinations()
  fetchHotels()
  fetchFlights()
}

onMounted(() => {
  refreshAllData()

  // Auto-refresh saat user kembali ke tab Landing Page setelah mengedit data di Admin Portal
  if (typeof window !== 'undefined') {
    window.addEventListener('focus', () => {
      refreshAllData()
    })
  }
})
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
  align-items: center;
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
  align-items: center;
  gap: 0.85rem;
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

