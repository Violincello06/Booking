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

// Init On Page Load
document.addEventListener('DOMContentLoaded', () => {
  loadDestinations()
  loadHotels()
  loadFlights()
})
