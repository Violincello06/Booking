// -------------------------------------------------------------
// VIOLINCELLO OTA FRONTEND APPLICATION ENGINE
// Connected to Backend REST API at http://localhost:8000
// -------------------------------------------------------------

const API_BASE = 'http://localhost:8000/api/public'

let activeSearchTab = 'hotel'
let destinationsData = []
let hotelsData = []
let flightsData = []

// Switch Search Tab
function setTab(tab) {
  activeSearchTab = tab
  document.getElementById('tab-hotel').classList.toggle('active', tab === 'hotel')
  document.getElementById('tab-flight').classList.toggle('active', tab === 'flight')

  document.getElementById('search-form-hotel').style.display = tab === 'hotel' ? 'grid' : 'none'
  document.getElementById('search-form-flight').style.display = tab === 'flight' ? 'grid' : 'none'
}

// Fetch Destinations from Backend
async function loadDestinations() {
  const container = document.getElementById('destinations-list')
  try {
    const res = await fetch(`${API_BASE}/destinations`)
    const json = await res.json()
    destinationsData = json.data || []

    if (destinationsData.length === 0) {
      container.innerHTML = `<p style="color: var(--text-secondary); grid-column: 1/-1;">Belum ada data destinasi tersedia.</p>`
      return
    }

    container.innerHTML = destinationsData.map(d => `
      <div class="card">
        <div class="card-media">
          <img src="${d.image}" alt="${d.name}" class="card-img" loading="lazy">
          <span class="card-badge">${d.badge || 'Destinasi Favorit'}</span>
        </div>
        <div class="card-body">
          <h3 class="card-title">${d.name}</h3>
          <p class="card-location">📍 ${d.city}, ${d.country || 'Indonesia'}</p>
          <p class="card-desc">${d.description || 'Keindahan alam Nusantara yang mempesona.'}</p>
          <div class="card-footer">
            <span style="font-size: 0.9rem; font-weight: 600; color: #fff;">⭐ ${d.rating || 4.8} / 5.0</span>
            <button class="btn btn-outline" onclick="selectDestination('${d.city}')">Cari Hotel Terdekat</button>
          </div>
        </div>
      </div>
    `).join('')
  } catch (err) {
    container.innerHTML = `<p style="color: #ef4444; grid-column: 1/-1;">Gagal memuat data destinasi dari backend server.</p>`
  }
}

// Fetch Hotels from Backend
async function loadHotels() {
  const container = document.getElementById('hotels-list')
  const query = document.getElementById('filter-hotel-city').value
  const stars = document.getElementById('filter-hotel-stars').value
  const sortBy = document.getElementById('filter-hotel-sort').value

  const params = new URLSearchParams()
  if (query) params.append('q', query)
  if (stars) params.append('stars', stars)
  if (sortBy) params.append('sortBy', sortBy)

  try {
    const res = await fetch(`${API_BASE}/hotels?${params.toString()}`)
    const json = await res.json()
    hotelsData = json.data || []

    if (hotelsData.length === 0) {
      container.innerHTML = `<p style="color: var(--text-secondary); grid-column: 1/-1;">Tidak ada hotel yang cocok dengan kriteria pencarian.</p>`
      return
    }

    container.innerHTML = hotelsData.map(h => `
      <div class="card">
        <div class="card-media">
          <img src="${h.image}" alt="${h.name}" class="card-img" loading="lazy">
          <span class="card-badge">${h.tag || 'Rekomendasi'}</span>
        </div>
        <div class="card-body">
          <h3 class="card-title">${h.name}</h3>
          <p class="card-location">📍 ${h.city}</p>
          <div style="margin-bottom: 0.75rem;">
            <span style="color: #fbbf24;">${'★'.repeat(h.stars || 4)}</span>
            <span style="font-size: 0.8rem; color: var(--text-muted); margin-left: 0.3rem;">(${h.rating || 4.8})</span>
          </div>
          <p class="card-desc">${h.description || 'Akomodasi berkualitas dengan fasilitas lengkap.'}</p>
          <div class="card-footer">
            <div>
              <span style="font-size: 0.72rem; color: var(--text-secondary); display: block;">Mulai dari / malam</span>
              <span class="price-val">Rp ${Number(h.price).toLocaleString('id-ID')}</span>
            </div>
            <button class="btn btn-primary" onclick="openBookingModal('hotel', '${h.name}', ${h.price})">Reservasi</button>
          </div>
        </div>
      </div>
    `).join('')
  } catch (err) {
    container.innerHTML = `<p style="color: #ef4444; grid-column: 1/-1;">Gagal memuat hotel dari backend.</p>`
  }
}

// Fetch Flights from Backend
async function loadFlights() {
  const container = document.getElementById('flights-list')
  const origin = document.getElementById('filter-flight-orig').value
  const destination = document.getElementById('filter-flight-dest').value

  const params = new URLSearchParams()
  if (origin) params.append('origin', origin)
  if (destination) params.append('destination', destination)

  try {
    const res = await fetch(`${API_BASE}/flights?${params.toString()}`)
    const json = await res.json()
    flightsData = json.data || []

    if (flightsData.length === 0) {
      container.innerHTML = `<p style="color: var(--text-secondary);">Tidak ada jadwal penerbangan untuk rute tersebut.</p>`
      return
    }

    container.innerHTML = flightsData.map(f => `
      <div class="flight-row">
        <div class="flight-airline">
          <div class="airline-badge">✈️</div>
          <div>
            <h4 style="font-size: 1.05rem;">${f.airline}</h4>
            <span style="font-size: 0.75rem; color: var(--text-secondary);">${f.flightNumber} • ${f.class || 'Ekonomi'}</span>
          </div>
        </div>

        <div class="flight-route-info">
          <div class="route-point">
            <div class="route-time">${f.depTime}</div>
            <div class="route-city">${f.originCode} (${f.originCity})</div>
          </div>
          <div style="text-align: center; color: var(--text-muted); font-size: 0.8rem; min-width: 90px;">
            <div>${f.duration}</div>
            <div style="height: 1px; background: var(--border-subtle); margin: 0.3rem 0;"></div>
            <div style="color: #34d399; font-size: 0.72rem;">Langsung</div>
          </div>
          <div class="route-point">
            <div class="route-time">${f.arrTime}</div>
            <div class="route-city">${f.destCode} (${f.destCity})</div>
          </div>
        </div>

        <div style="display: flex; align-items: center; gap: 1.5rem;">
          <div style="text-align: right;">
            <span style="font-size: 0.72rem; color: var(--text-secondary); display: block;">Tersisa ${f.seats} kursi</span>
            <span class="price-val">Rp ${Number(f.price).toLocaleString('id-ID')}</span>
          </div>
          <button class="btn btn-primary" onclick="openBookingModal('flight', '${f.airline} ${f.flightNumber} (${f.originCode}-${f.destCode})', ${f.price})">Pilih Tiket</button>
        </div>
      </div>
    `).join('')
  } catch (err) {
    container.innerHTML = `<p style="color: #ef4444;">Gagal memuat jadwal penerbangan dari backend.</p>`
  }
}

function selectDestination(cityName) {
  setTab('hotel')
  document.getElementById('filter-hotel-city').value = cityName
  loadHotels()
  const el = document.getElementById('hotels')
  if (el) el.scrollIntoView({ behavior: 'smooth' })
}

// Booking Modal Logic
let currentBooking = null

function openBookingModal(type, title, price) {
  currentBooking = { type, title, price }
  document.getElementById('modal-item-title').textContent = title
  document.getElementById('modal-item-price').textContent = `Rp ${Number(price).toLocaleString('id-ID')}`
  document.getElementById('booking-modal').style.display = 'flex'
}

function closeBookingModal() {
  document.getElementById('booking-modal').style.display = 'none'
}

function submitBooking(e) {
  e.preventDefault()
  const name = document.getElementById('book-name').value
  const phone = document.getElementById('book-phone').value

  if (!name || !phone) {
    alert('Harap lengkapi nama dan nomor WhatsApp pemesan.')
    return
  }

  alert(`Pemesanan Berhasil!\n\nNama Pemesan: ${name}\nLayanan: ${currentBooking.title}\nTotal: Rp ${Number(currentBooking.price).toLocaleString('id-ID')}\n\nKonfirmasi e-voucher dan rincian perjalanan telah dikirimkan ke nomor ${phone}.`)
  closeBookingModal()
  document.getElementById('booking-form').reset()
}

// Hero Banner Carousel Logic (6 Destinasi Unggulan)
let currentHeroSlide = 0
let heroBannersData = [
  {
    id: 1,
    title: 'Pesona Bahari Kepulauan Raja Ampat',
    location: 'Waisai, Papua Barat Daya',
    badge: 'Surga Bawah Laut',
    description: 'Gugusan pulau karang zamrud dan terumbu karang dengan keanekaragaman hayati laut terkaya di dunia.',
    image: 'https://images.unsplash.com/photo-1516690561799-46d8f74f9abf?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Jelajahi Raja Ampat'
  },
  {
    id: 2,
    title: 'Keajaiban Alam Labuan Bajo & Komodo',
    location: 'Manggarai Barat, Nusa Tenggara Timur',
    badge: 'Warisan Dunia UNESCO',
    description: 'Panorama perbukitan sabana tiga warna dan habitat asli kadal purba terbesar di muka bumi.',
    image: 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Eksplorasi Labuan Bajo'
  },
  {
    id: 3,
    title: 'Kemegahan Candi Borobudur & Budaya Mataram',
    location: 'Magelang & Yogyakarta, Jawa Tengah',
    badge: 'Cagar Budaya Mahakarya',
    description: 'Monumen candi Buddha terbesar di dunia di tengah balutan kabut fajar dan lanskap perbukitan Menoreh.',
    image: 'https://images.unsplash.com/photo-1596402184320-417e7178b2cd?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Kunjungi Borobudur'
  },
  {
    id: 4,
    title: 'Lautan Pasir & Mentari Pagi Gunung Bromo',
    location: 'Tengger Semeru, Jawa Timur',
    badge: 'Lanskap Vulkanik Epik',
    description: 'Pemandangan matahari terbit spektakuler di atas kaldera purba dan kawah aktif Tengger.',
    image: 'https://images.unsplash.com/photo-1588668214407-6ea9a6d8c272?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Jelajahi Bromo'
  },
  {
    id: 5,
    title: 'Keheningan Alam Terasering Ubud Bali',
    location: 'Gianyar, Bali',
    badge: 'Pusat Spiritualitas & Relaksasi',
    description: 'Persawahan terasering hijau berundak, resor spa mewah, dan sentra kesenian tradisional Bali.',
    image: 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Temukan Resor Ubud'
  },
  {
    id: 6,
    title: 'Danau Vulkanik Terbesar Dunia di Danau Toba',
    location: 'Parapat & Samosir, Sumatera Utara',
    badge: 'Geopark Kaldera Raksasa',
    description: 'Danau vulkanik luas berair sejuk dengan warisan budaya Batak Toba yang otentik dan memukau.',
    image: 'https://images.unsplash.com/photo-1570789210967-2cac24afeb00?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Eksplorasi Danau Toba'
  }
]

async function loadHeroBanners() {
  try {
    const res = await fetch('http://localhost:8000/api/v1/banners')
    const json = await res.json()
    if (json && json.data && json.data.length > 0) {
      heroBannersData = json.data
    }
  } catch (err) {
    // Gunakan 6 data default jika API belum aktif
  }
  renderHeroSlider()
}

function renderHeroSlider() {
  const track = document.getElementById('slider-track')
  const dotsContainer = document.getElementById('slider-dots')
  const counter = document.getElementById('slider-counter')
  if (!track) return

  track.innerHTML = heroBannersData.map((b, i) => `
    <div class="slider-slide ${i === currentHeroSlide ? 'active' : ''}" style="background-image: url('${b.image}')">
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <div class="slide-meta">
          <span class="slide-badge">${b.badge || 'Destinasi Unggulan'}</span>
          <span class="slide-location">📍 ${b.location || 'Indonesia'}</span>
        </div>
        <h2 class="slide-title">${b.title}</h2>
        <p class="slide-desc">${b.description || ''}</p>
        <div class="slide-actions">
          <a href="#destinations" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">✨ ${b.ctaText || 'Eksplorasi Destinasi'}</a>
          <button class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.85rem;" onclick="selectDestination('${b.location?.split(',')?.[0]?.trim() || b.title}')">🏨 Cek Hotel Terdekat</button>
        </div>
      </div>
    </div>
  `).join('')

  if (dotsContainer) {
    dotsContainer.innerHTML = heroBannersData.map((_, i) => `
      <button class="slider-dot ${i === currentHeroSlide ? 'active' : ''}" onclick="goToHeroSlide(${i})" aria-label="Slide ${i + 1}"></button>
    `).join('')
  }

  if (counter) {
    counter.textContent = `${String(currentHeroSlide + 1).padStart(2, '0')} / ${String(heroBannersData.length).padStart(2, '0')}`
  }
}

function nextHeroSlide() {
  if (heroBannersData.length === 0) return
  currentHeroSlide = (currentHeroSlide + 1) % heroBannersData.length
  renderHeroSlider()
}

function prevHeroSlide() {
  if (heroBannersData.length === 0) return
  currentHeroSlide = (currentHeroSlide - 1 + heroBannersData.length) % heroBannersData.length
  renderHeroSlider()
}

function goToHeroSlide(idx) {
  currentHeroSlide = idx
  renderHeroSlider()
}

// Init On Page Load
document.addEventListener('DOMContentLoaded', () => {
  loadHeroBanners()
  loadDestinations()
  loadHotels()
  loadFlights()
})
