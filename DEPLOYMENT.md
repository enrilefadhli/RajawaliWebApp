# Panduan Deploy RajawaliWebApp (Ubuntu VPS + Nginx + PHP-FPM + MySQL)

Dokumen ringkas langkah deploy seperti yang kita jalankan di Hostinger VPS (Ubuntu 24.04). Sesuaikan path, domain, dan PHP version jika berbeda.

## 1) Siapkan sistem & dependensi
```bash
apt update && apt upgrade -y
apt install -y nginx
apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl unzip git
cd /tmp && php -r "copy('https://getcomposer.org/installer','composer-setup.php');" && php composer-setup.php --install-dir=/usr/local/bin --filename=composer
```

## 2) Ambil source code
```bash
mkdir -p /var/www/rajawali && cd /var/www/rajawali
git clone <repo-url> RajawaliWebApp   # atau upload manual
cd /var/www/rajawali/RajawaliWebApp
```

## 3) Konfigurasi `.env`
```bash
cp .env.example .env
```
Edit `.env` (DB, APP_URL, dll). Generate key:
```bash
php artisan key:generate
```

## 4) Install dependensi Laravel
```bash
composer install --no-dev --optimize-autoloader
```

## 5) Setup database MySQL
```bash
mysql -u root -p
CREATE DATABASE rajawali CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'rajawali_user'@'localhost' IDENTIFIED BY 'Real1234#';   -- contoh
GRANT ALL PRIVILEGES ON rajawali.* TO 'rajawali_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
Di `.env`:
```
DB_DATABASE=rajawali
DB_USERNAME=rajawali_user
DB_PASSWORD="Real1234#"
```

## 6) Migrasi & seeding
```bash
php artisan migrate:fresh --seed --force
```

## 7) Perizinan Laravel
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
```

## 8) Konfigurasi Nginx (domain aktif: tokorajawali.online)
File `/etc/nginx/sites-available/rajawali`:
```nginx
server {
    listen 80;
    server_name tokorajawali.online www.tokorajawali.online;
    root /var/www/rajawali/RajawaliWebApp/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;  # sesuaikan versi
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```
Aktifkan site:
```bash
ln -s /etc/nginx/sites-available/rajawali /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default  # opsional
nginx -t
systemctl reload nginx
```
Jika port 80 dipakai Apache:
```bash
systemctl stop apache2
systemctl disable apache2
systemctl start nginx
```

## 9) PHP-FPM & service check
```bash
systemctl status nginx
systemctl status php8.3-fpm
ss -lntp | grep :80   # cek siapa pakai port 80
```

## 10) Cache konfigurasi
```bash
php artisan config:clear && php artisan config:cache
```

## 11) DNS
- A `@` -> `72.61.215.250` (TTL 300)
- CNAME `www` -> `tokorajawali.online`
- Pastikan nameserver mengarah ke zona DNS yang Anda edit (Hostinger NS atau sesuai).
Tes:
```bash
dig @1.1.1.1 tokorajawali.online +short
whois tokorajawali.online | grep -i "Name Server"
```
Gunakan domain untuk APP_URL:
```
APP_URL=https://tokorajawali.online
```

## 12) Tes akses
- Browser: `https://tokorajawali.online` (atau `http://` jika SSL belum aktif).
- Stop `php artisan serve` (tidak diperlukan dengan Nginx).

## 13) HTTPS (setelah DNS aktif)
```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d tokorajawali.online -d www.tokorajawali.online
```
Lalu set `APP_URL=https://tokorajawali.online` dan `nginx -t && systemctl reload nginx`.
