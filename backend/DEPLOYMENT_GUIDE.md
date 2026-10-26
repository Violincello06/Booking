# Panduan Produksi & Deployment: Violincello OTA Travel Booking System

Panduan ini berisi instruksi terperinci untuk mendeploy sistem **Violincello Travel Booking & Reservation System** (Backend Laravel 13 + Frontend Nuxt 4) ke server produksi berbasis Linux (Ubuntu 22.04/24.04 LTS).

---

## 🖥️ 1. Kebutuhan Sistem & Dependensi Server

Pastikan server produksi memenuhi spesifikasi minimum berikut:
- **Sistem Operasi**: Ubuntu 22.04 / 24.04 LTS (atau Debian 12 / AlmaLinux 9)
- **Web Server**: Nginx 1.24+ (disertai modul HTTP/2 & SSL)
- **Database**: MySQL 8.0+ atau MariaDB 10.11+
- **PHP**: PHP 8.4-FPM beserta ekstensi wajib:
  ```bash
  sudo apt install -y php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml \
      php8.4-bcmath php8.4-curl php8.4-zip php8.4-intl php8.4-gd
  ```
- **Node.js**: Node.js 20.x LTS & npm
- **Process Manager**: PM2 (untuk Nuxt 4) & Supervisor (untuk Laravel Queue Worker)
- **Composer**: Composer 2.7+

---

## 🚀 2. Langkah Instalasi Backend Laravel

### A. Clone & Setup Dependensi
```bash
cd /var/www
git clone https://github.com/Violincello06/Booking.git violincello
cd /var/www/violincello/travel-booking

# Salin konfigurasi environment produksi
cp .env.production.example .env

# Install dependensi PHP tanpa dev dependencies
composer install --no-dev --optimize-autoloader
```

### B. Konfigurasi Environment & Database
Edit berkas `.env` dan sesuaikan kredensial database produksi:
```env
APP_NAME=Violincello
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.violincello.com
FRONTEND_URL=https://violincello.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=violincello_production
DB_USERNAME=violincello_user
DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE
```

Generate application key & jalankan migrasi database:
```bash
php artisan key:generate
php artisan migrate --force --seed
```

### C. Permissions & Kepemilikan Folder
Berikan hak akses tulis ke direktori `storage` dan `bootstrap/cache`:
```bash
sudo chown -R www-data:www-data /var/www/violincello/travel-booking/storage
sudo chown -R www-data:www-data /var/www/violincello/travel-booking/bootstrap/cache
sudo chmod -R 775 /var/www/violincello/travel-booking/storage
sudo chmod -R 775 /var/www/violincello/travel-booking/bootstrap/cache
```

---

## ⚡ 3. Optimasi & Caching Produksi Laravel

Jalankan perintah optimasi bawaan Laravel untuk mempercepat waktu respon (*zero-overhead bootstrap*):
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> **Catatan**: Jika melakukan perubahan pada berkas `.env` atau rute di masa mendatang, jalankan:
> `php artisan optimize:clear && php artisan optimize`

---

## 🌐 4. Konfigurasi Nginx Server Block (Reverse Proxy & API)

Buat berkas konfigurasi `/etc/nginx/sites-available/api.violincello.com`:
```nginx
server {
    listen 80;
    server_name api.violincello.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.violincello.com;
    root /var/www/violincello/travel-booking/public;

    ssl_certificate /etc/letsencrypt/live/api.violincello.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.violincello.com/privkey.pem;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;
    charset utf-8;

    # Gzip Compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/api.violincello.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## ⏱️ 5. Automasi Crontab (Pembatalan Booking Kedaluwarsa)

Jadwalkan task scheduler Laravel untuk mengeksekusi pemeriksaan dan pelepasan inventaris booking kedaluwarsa secara otomatis setiap 10 menit:
```bash
sudo crontab -u www-data -e
```
Tambahkan baris berikut:
```cron
* * * * * cd /var/www/violincello/travel-booking && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔄 6. Konfigurasi Supervisor (Laravel Queue Worker)

Buat berkas `/etc/supervisor/conf.d/violincello-worker.conf`:
```ini
[program:violincello-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/violincello/travel-booking/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/violincello/travel-booking/storage/logs/worker.log
stopwaitsecs=3600
```
Aktifkan supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start violincello-worker:*
```

---

## 🎨 7. Deployment Frontend Nuxt 4

Di server aplikasi frontend:
```bash
cd /var/www/violincello

# Install dependensi frontend
npm install

# Build production bundle Nuxt 4 (SSR)
npm run build

# Jalankan menggunakan PM2 Process Manager
pm2 start .output/server/index.mjs --name "violincello-frontend" --env PORT=3000
pm2 save
pm2 startup
```

Nginx VirtualHost untuk Frontend (`violincello.com`):
```nginx
server {
    listen 80;
    server_name violincello.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name violincello.com;

    ssl_certificate /etc/letsencrypt/live/violincello.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/violincello.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

---

## 🩺 8. Verifikasi Post-Deployment

Setelah instalasi selesai, verifikasi kesehatan sistem:
1. **Health Check API**:
   ```bash
   curl -i https://api.violincello.com/api/v1/health
   ```
   *Expected*: `200 OK`, `database: "connected"`.
2. **Scheduler Check**:
   ```bash
   cd /var/www/violincello/travel-booking && php artisan schedule:list
   ```
3. **Queue Status**:
   ```bash
   sudo supervisorctl status
   ```
4. **PM2 Frontend Status**:
   ```bash
   pm2 status
   ```

---

## 💾 9. Strategi Backup Database Harian (Opsional)

Buat script backup di `/usr/local/bin/backup-violincello.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/violincello"
DATE=$(date +\%Y\%m\%d_\%H\%M\%S)
mkdir -p $BACKUP_DIR
mysqldump -u violincello_user -p'YOUR_STRONG_PASSWORD_HERE' violincello_production | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz
find $BACKUP_DIR -type f -name "*.sql.gz" -mtime +14 -exec rm {} \;
```
Jadwalkan di crontab setiap jam 02.00 pagi:
```cron
0 2 * * * /usr/local/bin/backup-violincello.sh
```
