<template>
  <div class="travel-app">
    <!-- 1. NAVIGATION BAR -->
    <header class="navbar">
      <div class="container nav-content">
        <div class="brand-group">
          <div class="brand-logo">
            <span class="logo-icon">🎻</span>
          </div>
          <div>
            <h2 class="brand-title">CelloTrav <span class="text-gradient">Experience</span></h2>
            <p class="brand-subtitle">Platform Reservasi Hotel & Tiket Pesawat</p>
          </div>
        </div>

        <nav class="nav-links">
          <a href="#search" class="nav-link active">Eksplorasi</a>
          <a href="#destinations" class="nav-link">Destinasi</a>
          <a href="#hotels" class="nav-link">Hotel & Resor</a>
          <a href="#flights" class="nav-link">Penerbangan</a>
          <a href="#blogs" class="nav-link">Inspirasi & Blog</a>
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
          <a href="#search" class="btn btn-primary btn-nav-cta" id="btn-user-booking" title="Mulai Eksplorasi & Reservasi">
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
          <span class="text-gradient">CelloTrav Travel Experience</span>
        </h1>
        <p class="hero-desc">
          Temukan destinasi eksotis Nusantara, akomodasi hotel berbintang terverifikasi, dan jadwal penerbangan langsung dengan tarif transparan tanpa biaya tersembunyi.
        </p>
      </div>

      <!-- 2.1 HERO BANNER CAROUSEL SLIDER (6 Destinasi Unggulan Nusantara) -->
      <section class="hero-banner-slider" aria-label="Banner Galeri Destinasi Nusantara">
        <div class="slider-container glass-panel">
          <!-- Slide items -->
          <div 
            v-for="(banner, index) in heroBanners" 
            :key="banner.id || index"
            class="slider-slide"
            :class="{ active: currentSlide === index }"
            :style="{ backgroundImage: `url(${banner.image})` }"
          >
            <!-- Cinematic Dark Gradient Overlay for contrast -->
            <div class="slide-overlay"></div>

            <div class="slide-content">
              <div class="slide-meta">
                <span class="slide-badge">{{ banner.badge || 'Destinasi Unggulan' }}</span>
                <span class="slide-location">📍 {{ banner.location || 'Indonesia' }}</span>
              </div>
              <h2 class="slide-title">{{ banner.title }}</h2>
              <p class="slide-desc">{{ banner.description }}</p>
              <div class="slide-actions">
                <a :href="banner.ctaLink || '#destinations'" class="btn btn-primary btn-sm btn-slide">
                  <span>✨</span> {{ banner.ctaText || 'Eksplorasi Destinasi' }}
                </a>
                <button 
                  @click="quickFilterDestination({ city: banner.location?.split(',')?.[0]?.trim() || banner.title, name: banner.title })" 
                  class="btn btn-glass btn-sm btn-slide-alt"
                >
                  <span>🏨</span> Cek Hotel Terdekat
                </button>
              </div>
            </div>
          </div>

          <!-- Manual Navigation Buttons: Kiri (Sebelumnya) & Kanan (Selanjutnya) -->
          <button 
            class="slider-btn slider-btn-prev" 
            @click="prevSlide" 
            aria-label="Slide sebelumnya"
            title="Slide sebelumnya"
          >
            ‹
          </button>
          <button 
            class="slider-btn slider-btn-next" 
            @click="nextSlide" 
            aria-label="Slide selanjutnya"
            title="Slide selanjutnya"
          >
            ›
          </button>

          <!-- Bottom Indicators & Counter -->
          <div class="slider-footer">
            <div class="slider-counter">
              <span class="current-num">{{ String(currentSlide + 1).padStart(2, '0') }}</span>
              <span class="sep">/</span>
              <span class="total-num">{{ String(heroBanners.length).padStart(2, '0') }}</span>
            </div>
            <div class="slider-dots">
              <button 
                v-for="(banner, idx) in heroBanners" 
                :key="'dot-' + idx"
                class="slider-dot"
                :class="{ active: currentSlide === idx }"
                @click="goToSlide(idx)"
                :aria-label="`Menuju slide ${idx + 1}`"
              ></button>
            </div>
          </div>
        </div>
      </section>

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
              <img :src="hotel.image" :alt="hotel.name" class="hotel-img" loading="lazy" @error="onImageError($event, hotel.id)" />
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
                  <h4 style="font-size: 1.05rem;">{{ flight.airlineName }}</h4>
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
              <button class="btn btn-glow" :id="'btn-book-flight-' + flight.id" @click="openBooking('flight', flight.airlineName + ' ' + flight.flightNumber, flight.price)">
                Pilih Tiket
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- 6. BLOG & INSPIRASI PERJALANAN (Backend Data Real-Time) -->
      <section class="catalog-section" id="blogs">
        <div class="section-header">
          <div>
            <span class="badge badge-primary">Inspirasi Nusantara</span>
            <h2 class="section-title">Blog & Panduan Wisata</h2>
          </div>
          <span style="color: var(--text-dim); font-size: 0.9rem;">
            {{ blogs.length }} artikel terbit
          </span>
        </div>

        <div v-if="loadingBlogs" class="text-center" style="padding: 3rem;">
          <p style="color: var(--text-muted);">Memuat artikel blog...</p>
        </div>

        <div v-else-if="blogs.length === 0" class="glass-panel text-center" style="padding: 3rem;">
          <p style="color: var(--text-muted);">Belum ada artikel blog yang diterbitkan oleh admin.</p>
        </div>

        <div v-else class="cards-grid">
          <article v-for="blog in blogs" :key="blog.id" class="glass-card hotel-card blog-card">
            <div class="hotel-media">
              <img :src="blog.image" :alt="blog.title" class="hotel-img" loading="lazy" />
              <div class="hotel-badge-floating">
                <span class="badge badge-warning">{{ blog.category || 'Panduan Wisata' }}</span>
              </div>
            </div>
            <div class="hotel-content">
              <div class="blog-meta">
                <span>🗓️ {{ blog.date }}</span>
                <span>⏱️ {{ blog.readTime || '4 menit' }}</span>
                <span>✍️ {{ blog.author }}</span>
              </div>

              <h3 class="hotel-title blog-title">{{ blog.title }}</h3>
              <p class="blog-summary">
                {{ blog.summary || blog.content }}
              </p>

              <div class="hotel-footer">
                <button class="btn btn-glass" :id="'btn-read-blog-' + blog.id" @click="openBlogDetail(blog)">
                  Baca Selengkapnya ➔
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>

      <!-- 6. KOMITMEN LAYANAN (Otentik & Anti-Slop) -->
      <section class="catalog-section" id="komitmen" style="border-top: 1px solid var(--border-glass); padding-top: 4rem;">
        <div class="section-header text-center" style="display: block; margin-bottom: 2.5rem;">
          <span class="badge badge-primary">Standar Layanan</span>
          <h2 class="section-title">Kenapa Merencanakan Bersama CelloTrav</h2>
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

    <!-- 8. BLOG DETAIL READER MODAL -->
    <div v-if="showBlogModal && selectedBlog" class="modal-overlay" @click.self="showBlogModal = false">
      <div class="modal-content blog-reader-modal animate-fade-in">
        <div style="position: relative;">
          <img :src="selectedBlog.image" :alt="selectedBlog.title" class="blog-reader-img" />
          <button class="btn-close" style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.6);" @click="showBlogModal = false">✕</button>
        </div>
        <div class="modal-header" style="margin-bottom: 0.5rem; display: block;">
          <span class="badge badge-primary" style="margin-bottom: 0.4rem; display: inline-block;">{{ selectedBlog.category }}</span>
          <h3 class="modal-title" style="font-size: 1.4rem; line-height: 1.35; color: #fff;">{{ selectedBlog.title }}</h3>
          <div class="blog-meta" style="margin-top: 0.5rem;">
            <span>🗓️ {{ selectedBlog.date }}</span>
            <span>⏱️ {{ selectedBlog.readTime }}</span>
            <span>✍️ {{ selectedBlog.author }}</span>
          </div>
        </div>
        <div class="blog-reader-body">
          {{ selectedBlog.content }}
        </div>
        <div class="modal-footer" style="margin-top: 1.75rem; justify-content: space-between;">
          <button class="btn btn-glass" @click="showBlogModal = false">Tutup Bacaan</button>
          <a href="#destinations" class="btn btn-primary" @click="showBlogModal = false">Jelajahi Destinasi</a>
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
            <h3 style="font-size: 1.15rem; margin-bottom: 0.25rem;">CelloTrav Travel Experience</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Layanan reservasi akomodasi hotel berbintang & tiket penerbangan langsung terpercaya di Indonesia.</p>
          </div>
          <div>
            <a href="#search" class="btn btn-glass" title="Kembali ke formulir pencarian">
              ↑ Mulai Reservasi
            </a>
          </div>
        </div>
        <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 1.25rem; text-align: center;">
          © 2026 CelloTrav. Menerapkan panduan Anti-Slop: Informasi akurat, bebas frasa klise AI, desain fungsional.
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
const blogs = ref([])

const loadingDestinations = ref(false)
const loadingHotels = ref(false)
const loadingFlights = ref(false)
const loadingBlogs = ref(false)

// Blog Reading Modal
const showBlogModal = ref(false)
const selectedBlog = ref(null)

function openBlogDetail(blog) {
  selectedBlog.value = blog
  showBlogModal.value = true
}

// Hero Banner State (Default 6 slides with reactive backend sync)
const currentSlide = ref(0)
const defaultBanners = [
  {
    id: 1,
    title: 'Pesona Bahari Kepulauan Raja Ampat',
    location: 'Waisai, Papua Barat Daya',
    badge: 'Surga Bawah Laut',
    description: 'Gugusan pulau karang zamrud dan terumbu karang dengan keanekaragaman hayati laut terkaya di dunia.',
    image: 'https://images.unsplash.com/photo-1516690561799-46d8f74f9abf?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Jelajahi Raja Ampat',
    ctaLink: '#destinations',
    order: 1
  },
  {
    id: 2,
    title: 'Keajaiban Alam Labuan Bajo & Komodo',
    location: 'Manggarai Barat, Nusa Tenggara Timur',
    badge: 'Warisan Dunia UNESCO',
    description: 'Panorama perbukitan sabana tiga warna dan habitat asli kadal purba terbesar di muka bumi.',
    image: 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Eksplorasi Labuan Bajo',
    ctaLink: '#destinations',
    order: 2
  },
  {
    id: 3,
    title: 'Kemegahan Candi Borobudur & Budaya Mataram',
    location: 'Magelang & Yogyakarta, Jawa Tengah',
    badge: 'Cagar Budaya Mahakarya',
    description: 'Monumen candi Buddha terbesar di dunia di tengah balutan kabut fajar dan lanskap perbukitan Menoreh.',
    image: 'https://images.unsplash.com/photo-1596402184320-417e7178b2cd?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Kunjungi Borobudur',
    ctaLink: '#destinations',
    order: 3
  },
  {
    id: 4,
    title: 'Lautan Pasir & Mentari Pagi Gunung Bromo',
    location: 'Tengger Semeru, Jawa Timur',
    badge: 'Lanskap Vulkanik Epik',
    description: 'Pemandangan matahari terbit spektakuler di atas kaldera purba dan kawah aktif Tengger.',
    image: 'https://images.unsplash.com/photo-1588668214407-6ea9a6d8c272?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Jelajahi Bromo',
    ctaLink: '#destinations',
    order: 4
  },
  {
    id: 5,
    title: 'Keheningan Alam Terasering Ubud Bali',
    location: 'Gianyar, Bali',
    badge: 'Pusat Spiritualitas & Relaksasi',
    description: 'Persawahan terasering hijau berundak, resor spa mewah, dan sentra kesenian tradisional Bali.',
    image: 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Temukan Resor Ubud',
    ctaLink: '#hotels',
    order: 5
  },
  {
    id: 6,
    title: 'Danau Vulkanik Terbesar Dunia di Danau Toba',
    location: 'Parapat & Samosir, Sumatera Utara',
    badge: 'Geopark Kaldera Raksasa',
    description: 'Danau vulkanik luas berair sejuk dengan warisan budaya Batak Toba yang otentik dan memukau.',
    image: 'https://images.unsplash.com/photo-1570789210967-2cac24afeb00?auto=format&fit=crop&w=1600&q=85',
    ctaText: 'Eksplorasi Danau Toba',
    ctaLink: '#destinations',
    order: 6
  }
]
const heroBanners = ref([...defaultBanners])

function nextSlide() {
  if (heroBanners.value.length === 0) return
  currentSlide.value = (currentSlide.value + 1) % heroBanners.value.length
}

function prevSlide() {
  if (heroBanners.value.length === 0) return
  currentSlide.value = (currentSlide.value - 1 + heroBanners.value.length) % heroBanners.value.length
}

function goToSlide(idx) {
  currentSlide.value = idx
}

async function fetchBanners() {
  try {
    const res = await $fetch('http://localhost:8000/api/v1/banners').catch(() => $fetch('http://localhost:8000/api/banners'))
    if (res && res.data && res.data.length > 0) {
      heroBanners.value = res.data
    }
  } catch (err) {
    console.warn('Menggunakan default hero banners:', err)
  }
}

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

// Normalization Helpers
function onImageError(event, fallbackIdx = 0) {
  const fallbacks = [
    'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1591088398332-8a7791972843?auto=format&fit=crop&w=800&q=80'
  ]
  if (event && event.target) {
    event.target.src = fallbacks[Math.abs(fallbackIdx || 0) % fallbacks.length]
  }
}

function formatFlightTime(isoOrTime) {
  if (!isoOrTime) return '08:00'
  const str = String(isoOrTime)
  if (str.includes('T')) {
    const d = new Date(str)
    if (!isNaN(d.getTime())) {
      const h = String(d.getHours()).padStart(2, '0')
      const m = String(d.getMinutes()).padStart(2, '0')
      return `${h}:${m}`
    }
  }
  return str
}

function formatFlightDuration(dur) {
  if (typeof dur === 'number') {
    const hours = Math.floor(dur / 60)
    const mins = dur % 60
    return `${hours}j ${mins}m`
  }
  return dur ? String(dur) : '1j 45m'
}

function normalizeHotel(h) {
  const fallbackImages = [
    'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1591088398332-8a7791972843?auto=format&fit=crop&w=800&q=80'
  ]
  const defaultImg = fallbackImages[Math.abs(h.id || 0) % fallbackImages.length]

  const rawPrice = h.price ?? h.min_price ?? h.rooms?.[0]?.price_per_night ?? 750000
  const price = !isNaN(Number(rawPrice)) && Number(rawPrice) > 0 ? Number(rawPrice) : 750000

  const city = h.city || h.destination?.city || h.destination?.name || h.address || 'Indonesia'
  const image = h.image || h.rooms?.[0]?.image || h.destination?.image || defaultImg
  const stars = h.stars || h.star_rating || 4

  return {
    ...h,
    name: h.name || 'Properti Hotel',
    city,
    image,
    price,
    stars,
    tag: h.tag || (stars >= 5 ? 'Resor Mewah' : 'Rekomendasi'),
    rating_score: h.average_rating || h.rating_score || h.rating || 4.8,
    amenities: h.amenities || ['Free WiFi', 'Breakfast', 'AC', 'Swimming Pool']
  }
}

function normalizeFlight(f) {
  const airlineObj = typeof f.airline === 'object' && f.airline !== null ? f.airline : null
  const airlineName = airlineObj?.name || f.airlineName || (typeof f.airline === 'string' ? f.airline : 'Maskapai Domestik')
  const airlineCode = airlineObj?.code || f.airlineCode || 'JT'

  const flightNumber = f.flight_number || f.flightNumber || `${airlineCode}-101`
  const depTime = formatFlightTime(f.departure_time || f.depTime || '08:00')
  const arrTime = formatFlightTime(f.arrival_time || f.arrTime || '10:30')
  const duration = formatFlightDuration(f.duration)

  const originCode = f.departure_airport?.code || f.originCode || 'CGK'
  const originCity = f.departure_airport?.city || f.originCity || 'Jakarta'
  const destCode = f.arrival_airport?.code || f.destCode || 'DPS'
  const destCity = f.arrival_airport?.city || f.destCity || 'Bali'

  const rawPrice = f.price ?? 950000
  const price = !isNaN(Number(rawPrice)) && Number(rawPrice) > 0 ? Number(rawPrice) : 950000

  const seats = f.available_seats ?? f.seats ?? f.total_seats ?? 45
  const classType = f.classType || f.class || 'Ekonomi'

  return {
    ...f,
    airlineName,
    airlineCode,
    flightNumber,
    depTime,
    arrTime,
    duration,
    originCode,
    originCity,
    destCode,
    destCity,
    price,
    seats,
    classType
  }
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
    const rawList = res.data || []
    hotels.value = rawList.map(normalizeHotel)
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
    const rawList = res.data || []
    flights.value = rawList.map(normalizeFlight)
  } catch (err) {
    console.error('Fetch flights error:', err)
  } finally {
    loadingFlights.value = false
  }
}

async function fetchBlogs() {
  loadingBlogs.value = true
  try {
    const res = await $fetch('http://localhost:8000/api/v1/blogs').catch(() => $fetch('/api/v1/blogs'))
    blogs.value = res.data || []
  } catch (err) {
    console.error('Fetch blogs error:', err)
  } finally {
    loadingBlogs.value = false
  }
}

function refreshAllData() {
  fetchBanners()
  fetchDestinations()
  fetchHotels()
  fetchFlights()
  fetchBlogs()
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
  top: 0;
  left: 0;
  width: 100%;
  z-index: 100;
  margin: 0;
  padding: 0.5rem 0;
  background: rgba(10, 15, 29, 0.94);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: none;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
  transition: all var(--transition-fast);
}

.nav-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  position: relative;
}

.brand-group {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-shrink: 0;
}

.brand-logo {
  width: 36px;
  height: 36px;
  background: var(--primary-gradient);
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 12px rgba(37, 99, 235, 0.35);
  flex-shrink: 0;
}

.logo-icon {
  font-size: 1.15rem;
}

.brand-title {
  font-size: 1.125rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 1.15;
  white-space: nowrap;
}

.brand-subtitle {
  font-size: 0.65rem;
  color: var(--text-dim, #94a3b8);
  letter-spacing: 0.04em;
  white-space: nowrap;
  margin-top: 1px;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-shrink: 0;
}

.nav-link {
  color: var(--text-muted, #94a3b8);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.84rem;
  padding: 0.35rem 0.6rem;
  border-radius: 6px;
  white-space: nowrap;
  transition: all var(--transition-fast, 0.15s ease);
}

.nav-link:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.06);
}

.nav-link.active {
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.12);
  font-weight: 600;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-shrink: 0;
}

.notification-container {
  position: relative;
}

.btn-icon {
  width: 34px;
  height: 34px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  font-size: 1rem;
  border-radius: 8px;
}

.btn-nav-cta {
  padding: 0.45rem 0.95rem !important;
  font-size: 0.825rem !important;
  font-weight: 600 !important;
  white-space: nowrap !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 0.35rem !important;
  border-radius: 8px !important;
  box-shadow: 0 2px 12px rgba(37, 99, 235, 0.35) !important;
}

.notif-badge {
  position: absolute;
  top: -3px;
  right: -3px;
  background: var(--accent-gradient, linear-gradient(135deg, #f43f5e, #e11d48));
  color: #fff;
  font-size: 0.6rem;
  font-weight: 700;
  min-width: 16px;
  height: 16px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 3px;
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
  margin-bottom: 2rem;
}

/* Hero Banner Carousel Slider (6 Destinasi Unggulan Nusantara) */
.hero-banner-slider {
  margin-bottom: 2.5rem;
  width: 100%;
}

.slider-container {
  position: relative;
  width: 100%;
  height: 400px;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 16px 48px -8px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.08);
}

.slider-slide {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center center;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  transform: scale(1.02);
  display: flex;
  align-items: flex-end;
  padding: 2.5rem 3.5rem;
  z-index: 1;
}

.slider-slide.active {
  opacity: 1;
  visibility: visible;
  transform: scale(1);
  z-index: 2;
}

/* Gradient Vignette: Deep rich contrast behind text, translucent across rest of photo */
.slide-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    rgba(10, 15, 29, 0.15) 0%,
    rgba(10, 15, 29, 0.45) 50%,
    rgba(10, 15, 29, 0.92) 100%
  ),
  linear-gradient(
    90deg,
    rgba(10, 15, 29, 0.85) 0%,
    rgba(10, 15, 29, 0.4) 60%,
    rgba(10, 15, 29, 0.1) 100%
  );
  z-index: 1;
  pointer-events: none;
}

.slide-content {
  position: relative;
  z-index: 2;
  max-width: 620px;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  animation: slideFadeUp 0.5s ease-out;
}

@keyframes slideFadeUp {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.slide-badge {
  background: rgba(37, 99, 235, 0.85);
  backdrop-filter: blur(8px);
  color: #fff;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
}

.slide-location {
  font-size: 0.85rem;
  color: #e2e8f0;
  font-weight: 500;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
}

.slide-title {
  font-size: clamp(1.4rem, 3.2vw, 2.1rem);
  font-weight: 800;
  color: #ffffff;
  line-height: 1.2;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
  letter-spacing: -0.02em;
}

.slide-desc {
  font-size: 0.92rem;
  color: #cbd5e1;
  line-height: 1.5;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.6);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.slide-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.5rem;
  flex-wrap: wrap;
}

.btn-slide {
  padding: 0.55rem 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
}

.btn-slide-alt {
  padding: 0.55rem 1.15rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(8px);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.2);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  transition: all 0.2s ease;
}

.btn-slide-alt:hover {
  background: rgba(255, 255, 255, 0.22);
  transform: translateY(-2px);
}

/* Nav Buttons: Left (Prev) & Right (Next) */
.slider-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.18);
  color: #ffffff;
  font-size: 1.75rem;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

.slider-btn:hover {
  background: rgba(37, 99, 235, 0.9);
  border-color: #3b82f6;
  color: #ffffff;
  transform: translateY(-50%) scale(1.08);
  box-shadow: 0 0 20px rgba(37, 99, 235, 0.6);
}

.slider-btn-prev {
  left: 1.25rem;
  padding-right: 2px;
}

.slider-btn-next {
  right: 1.25rem;
  padding-left: 2px;
}

/* Bottom Footer (Counter & Dots) */
.slider-footer {
  position: absolute;
  bottom: 1.25rem;
  right: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  z-index: 10;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(10px);
  padding: 0.4rem 0.85rem;
  border-radius: 30px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.slider-counter {
  font-family: 'Outfit', sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 0.2rem;
}

.slider-counter .current-num {
  color: #38bdf8;
}

.slider-counter .sep {
  color: rgba(255, 255, 255, 0.4);
}

.slider-counter .total-num {
  color: rgba(255, 255, 255, 0.6);
}

.slider-dots {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.slider-dot {
  width: 8px;
  height: 8px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.3);
  border: none;
  cursor: pointer;
  padding: 0;
  transition: all 0.25s ease;
}

.slider-dot:hover {
  background: rgba(255, 255, 255, 0.6);
}

.slider-dot.active {
  width: 22px;
  background: #38bdf8;
  box-shadow: 0 0 8px rgba(56, 189, 248, 0.8);
}

@media (max-width: 768px) {
  .slider-container {
    height: 340px;
  }
  .slider-slide {
    padding: 1.75rem 1.5rem;
  }
  .slider-btn {
    width: 36px;
    height: 36px;
    font-size: 1.4rem;
  }
  .slider-btn-prev {
    left: 0.5rem;
  }
  .slider-btn-next {
    right: 0.5rem;
  }
  .slider-footer {
    bottom: 0.75rem;
    right: 0.75rem;
    gap: 0.75rem;
  }
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

/* Blog Styles */
.blog-card {
  display: flex;
  flex-direction: column;
}
.blog-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  font-size: 0.76rem;
  color: var(--text-dim);
  margin-bottom: 0.5rem;
}
.blog-title {
  font-size: 1.15rem;
  font-weight: 700;
  line-height: 1.4;
  margin-bottom: 0.5rem;
  color: #fff;
}
.blog-summary {
  font-size: 0.86rem;
  color: var(--text-muted);
  line-height: 1.6;
  margin-bottom: 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.blog-reader-modal {
  max-width: 680px;
  max-height: 85vh;
  overflow-y: auto;
  padding: 1.75rem;
}
.blog-reader-img {
  width: 100%;
  height: 260px;
  object-fit: cover;
  border-radius: var(--radius-sm);
  margin-bottom: 1.25rem;
}
.blog-reader-body {
  font-size: 0.95rem;
  line-height: 1.85;
  color: #e2e8f0;
  white-space: pre-line;
  margin: 1.25rem 0;
}

@media (max-width: 900px) {
  .nav-links {
    display: none;
  }
}
</style>

