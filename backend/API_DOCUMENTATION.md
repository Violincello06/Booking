# Violincello Travel Booking & Reservation API Reference (v1.0)

Selamat datang di dokumentasi resmi REST API **Violincello Online Travel Agent (OTA)**. Sistem ini dibangun menggunakan arsitektur Laravel 13, MySQL, dan Laravel Sanctum Bearer Token.

---

## 🌐 Base URL & Konvensi

- **Base URL**: `http://127.0.0.1:8000/api/v1` (Lokal)
- **Format Header Standar**:
  ```http
  Accept: application/json
  Content-Type: application/json
  Authorization: Bearer <your-sanctum-token>
  ```

---

## 📑 Daftar Grup Endpoint

### 1. Health & Server Status
| Method | Endpoint | Auth | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/health` | Publik | Cek kesehatan server, konektivitas database MySQL, dan versi aplikasi |

---

### 2. Autentikasi & Profil Pengguna
| Method | Endpoint | Auth | Deskripsi |
| :--- | :--- | :--- | :--- |
| `POST` | `/register` | Publik | Registrasi pengguna baru (`name`, `email`, `password`, `phone`) |
| `POST` | `/login` | Publik | Autentikasi akun & penerbitan Bearer Token Sanctum |
| `GET` | `/me` | User / Staff / Admin | Mendapatkan data profil dan role pengguna aktif |
| `POST` | `/logout` | User / Staff / Admin | Revokasi access token sesi aktif |

---

### 3. Pencarian & Katalog Publik
| Method | Endpoint | Auth | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/search/hotels` | Publik | Filter multi-kriteria: `query`, `star_rating`, `min_price`, `max_price`, `sort_by` |
| `GET` | `/search/flights` | Publik | Filter penerbangan: `origin`, `destination`, `date`, `passengers`, `sort_by` |
| `GET` | `/destinations` | Publik | Katalog destinasi wisata populer |
| `GET` | `/hotels` | Publik | Katalog hotel dengan harga terendah (`start_from`) |
| `GET` | `/hotels/{id}` | Publik | Detail hotel beserta kamar dan fasilitas |
| `GET` | `/hotels/{id}/reviews`| Publik | Ulasan hotel terbitan & ringkasan statistik bintang |
| `GET` | `/vouchers` | Publik | Katalog voucher promo diskon aktif |

---

### 4. Mesin Reservasi (Booking Engine)
| Method | Endpoint | Auth | Deskripsi |
| :--- | :--- | :--- | :--- |
| `POST` | `/bookings/hotel` | User | Reservasi kamar hotel (concurrency pessimistic locking) |
| `POST` | `/bookings/flight` | User | Pemesanan tiket pesawat (validasi ketersediaan kursi) |
| `GET` | `/bookings` | User | Riwayat seluruh pemesanan pengguna (terpaginasi) |
| `GET` | `/bookings/{code}` | User | Detail pemesanan dan manifest tamu/penumpang |
| `POST` | `/bookings/{code}/cancel`| User | Pembatalan pemesanan pending & restorasi stok kamar/kursi |

---

### 5. Promo Vouchers & Diskon
| Method | Endpoint | Auth | Deskripsi |
| :--- | :--- | :--- | :--- |
| `POST` | `/vouchers/apply` | User | Klaim kupon diskon (persentase / nominal dengan batas kuota) |
| `GET` | `/vouchers/usages` | User | Riwayat penghematan dan penggunaan voucher pengguna |

---

### 6. Mock Payment Gateway
| Method | Endpoint | Auth | Deskripsi |
| :--- | :--- | :--- | :--- |
| `POST` | `/payments/checkout` | User | Inisialisasi sesi bayar (VA BCA/Mandiri/BNI/BRI, GoPay, OVO, QRIS) |
| `POST` | `/payments/{code}/simulate` | User | Simulasi aksi gateway (`success` atau `failed`) |
| `GET` | `/payments/{code}/status` | User | Cek status transaksi pembayaran |
| `GET` | `/bookings/{code}/confirmation` | User | Penerbitan tiket elektronik / konfirmasi reservasi resmi |

---

### 7. Ulasan & Penilaian (Reviews)
| Method | Endpoint | Auth | Deskripsi |
| :--- | :--- | :--- | :--- |
| `POST` | `/reviews` | User | Mengirim ulasan terverifikasi (hanya pemesanan confirmed/completed) |
| `GET` | `/reviews/my` | User | Daftar ulasan yang pernah dikirim pengguna |

---

### 8. Sistem Notifikasi
| Method | Endpoint | Auth | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/notifications` | User | Inbox notifikasi transaksi in-app terpaginasi |
| `GET` | `/notifications/unread-count` | User | Counter angka badge belum dibaca untuk navbar lonceng |
| `PATCH`| `/notifications/{id}/read` | User | Tandai satu notifikasi sebagai telah dibaca |
| `POST` | `/notifications/read-all` | User | Tandai seluruh notifikasi aktif sebagai telah dibaca |
| `DELETE`| `/notifications/{id}` | User | Hapus notifikasi dari kotak masuk |

---

### 9. Dashboard Admin & Analitik
| Method | Endpoint | Auth | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/dashboard` | Admin | Ringkasan metrik KPI (Pendapatan, Booking, Pengguna, Inventaris) |
| `GET` | `/admin/dashboard/charts` | Admin | Tren analitik deret waktu (harian 14-90 hari & bulanan tahun berjalan) |
| `GET` | `/admin/dashboard/top-performers`| Admin | Peringkat hotel, maskapai, dan voucher dengan performa tertinggi |
| `GET` | `/admin/reports/bookings`| Admin | Laporan tabular pemesanan dengan filter tanggal, tipe, dan status |
| `GET` | `/admin/reports/revenue` | Admin | Laporan rincian pendapatan berdasarkan kanal pembayaran |
| `POST` | `/admin/notifications/broadcast`| Admin | Siaran notifikasi pengumuman/promo massal ke seluruh pengguna |

---

### 10. Master CRUD Admin (50 Rute)
| Resource | Endpoint | Auth | Method |
| :--- | :--- | :--- | :--- |
| Destinasi | `/admin/destinations` | Admin | `GET`, `POST`, `PUT`, `DELETE` |
| Hotel | `/admin/hotels` | Admin | `GET`, `POST`, `PUT`, `DELETE` |
| Kamar | `/admin/rooms` | Admin | `GET`, `POST`, `PUT`, `DELETE` |
| Maskapai | `/admin/airlines` | Admin | `GET`, `POST`, `PUT`, `DELETE` |
| Bandara | `/admin/airports` | Admin | `GET`, `POST`, `PUT`, `DELETE` |
| Penerbangan | `/admin/flights` | Admin | `GET`, `POST`, `PUT`, `DELETE` |
| Voucher Promo | `/admin/vouchers` | Admin | `GET`, `POST`, `PUT`, `DELETE` |
| Pengguna | `/admin/users` | Admin | `GET`, `POST`, `PUT`, `DELETE` |
| Moderasi Review| `/admin/reviews/{id}/status`| Admin | `PATCH` (`published`, `pending`, `hidden`) |

---

## 🛠️ Cara Impor Postman Collection & Environment

1. Buka aplikasi **Postman**.
2. Klik tombol **Import** di pojok kiri atas.
3. Pilih file:
   - `Violincello_Travel_Booking.postman_collection.json`
   - `Violincello_Travel_Booking.postman_environment.json`
4. Pilih Environment aktif **"Violincello Local Environment"**.
5. Jalankan request **Login** atau **Register**, token akan tersimpan otomatis di variabel `{{token}}`.
