# AI LinkTree Generator

Generator LinkTree bertenaga AI yang menciptakan halaman link-in-bio yang indah dan dapat disesuaikan dengan saran desain cerdas.

## Fitur

- **Desain Bertenaga AI**: Otomatis menghasilkan halaman LinkTree modern dan responsif menggunakan DeepSeek AI
- **URL Kustom**: Buat URL personal untuk LinkTree Anda (misalnya: `domain.com/lt/namaanda`)
- **Rate Limiting**: Sistem kuota cerdas (3 generasi per hari) untuk mencegah penyalahgunaan
- **Auto Retry**: Mekanisme retry otomatis jika generasi gagal
- **Live Preview**: Pratinjau real-time LinkTree yang dihasilkan
- **SEO Optimized**: Meta tags lengkap termasuk Open Graph, Twitter Cards, dan JSON-LD structured data
- **Favicon Support**: Generasi favicon otomatis dari logo atau emoji
- **Responsive Design**: Desain mobile-first yang bekerja di semua perangkat
- **Database Storage**: Penyimpanan persisten halaman yang dihasilkan
- **CSRF Protection**: Keamanan built-in terhadap serangan CSRF

## Stack Teknologi

### Backend

- **PHP 8.1+**: Native PHP tanpa framework
- **MySQL/MariaDB**: Database untuk menyimpan data LinkTree
- **DeepSeek AI**: Melalui Hugging Face Router API untuk generasi HTML
- **Session Management**: PHP sessions untuk rate limiting dan manajemen state

### Frontend

- **TailBoot**: Library komponen UI modern
- **Lucide Icons**: Library ikon untuk elemen UI
- **JavaScript**: Tanpa dependensi framework
- **jQuery**: Untuk AJAX requests dan manipulasi DOM

## Instalasi

### Prasyarat

- PHP 8.1 atau lebih tinggi
- MySQL 5.7+ atau MariaDB 10.3+
- Apache/Nginx dengan mod_rewrite enabled
- Composer (opsional, untuk dependensi masa depan)

### Langkah-langkah

1. **Clone repository**

   ```bash
   git clone https://github.com/aangbaejuri/linktree.git
   cd linktree
   ```

2. **Buat database**

   ```bash
   mysql -u root -p
   ```

   ```sql
   CREATE DATABASE ai_linktree;
   USE ai_linktree;
   SOURCE database.sql;
   ```

3. **Konfigurasi pengaturan**

   Edit `setup.php`:

   ```php
   $db_hostname = 'localhost';
   $db_name = 'ai_linktree';
   $db_username = 'root';
   $db_password = 'password_anda';

   $access_token_hf = 'token_api_huggingface_anda';
   ```

4. **Konfigurasi web server**

   **Apache** (.htaccess sudah disertakan):

   ```apache
   # Pastikan mod_rewrite enabled
   a2enmod rewrite
   systemctl restart apache2
   ```

   **Nginx**:

   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }

   location ~ /lt/([a-zA-Z0-9_-]+)$ {
       rewrite ^/lt/([a-zA-Z0-9_-]+)$ /lt/index.php?url=$1 last;
   }
   ```

5. **Set permissions**

   ```bash
   chmod 755 -R .
   chmod 644 *.php
   ```

6. **Akses aplikasi**

   Buka `http://domain-anda.com` di browser.

## Konfigurasi

### API Token

Dapatkan token API Hugging Face:

1. Daftar di [Hugging Face](https://huggingface.co/)
2. Buka Settings > Access Tokens
3. Buat token baru
4. Tambahkan ke `setup.php`

### Rate Limiting

Default: 3 generasi per hari per session

Untuk memodifikasi, edit `generate.php`:

```php
if ($_SESSION['generate_count'] >= 3) {
    // Ubah 3 ke limit yang diinginkan
}
```

### Error Reporting

Untuk development:

```php
$report_error = true;  // di setup.php
```

Untuk production:

```php
$report_error = false;  // di setup.php
```

## Cara Penggunaan

### Membuat LinkTree

1. **Isi formulir**:

   - **Deskripsi**: Deskripsikan desain dan style yang diinginkan
   - **Custom URL**: Pilih URL unik (3-50 karakter, alfanumerik, dash, underscore)
   - **URL Logo**: (Opsional) URL gambar logo Anda
   - **Link**: Tambahkan link media sosial/website Anda

2. **Generate**: Klik "Buat LinkTree"

   - AI akan menghasilkan halaman HTML kustom
   - Preview muncul secara real-time
   - Proses memakan waktu 10-30 detik

3. **Simpan**: Klik "Simpan"

   - Halaman disimpan ke database
   - URL otomatis disalin ke clipboard
   - Terbuka di tab baru secara otomatis

4. **Bagikan**: LinkTree Anda tersedia di `http://domain-anda.com/lt/url-kustom-anda`

### Contoh Deskripsi

```
Buat LinkTree dengan tema gradient ungu-pink, modern dan minimalis.
Gunakan font Inter, tombol dengan efek hover smooth, dan background blur.
Tambahkan animated gradient background.
```

## Skema Database

### Tabel `linktrees`

- `id`: Primary key auto-increment
- `custom_url`: Identifier URL unik (VARCHAR 50)
- `url_logo`: URL logo (TEXT, nullable)
- `deskripsi`: Deskripsi/instruksi style (TEXT)
- `html_content`: HTML yang dihasilkan (LONGTEXT)
- `created_at`: Timestamp pembuatan
- `updated_at`: Timestamp update terakhir

### Tabel `linktree_links`

- `id`: Primary key auto-increment
- `linktree_id`: Foreign key ke linktrees
- `url`: URL link (VARCHAR 255)
- `urutan`: Urutan tampilan (INT)
- `created_at`: Timestamp pembuatan

## Fitur Keamanan

- **CSRF Protection**: Validasi berbasis token untuk semua form
- **Input Validation**: Validasi server-side dan client-side
- **SQL Injection Prevention**: Prepared statements untuk semua query
- **XSS Protection**: Sanitasi dan encoding HTML
- **Rate Limiting**: Mencegah penyalahgunaan API
- **URL Validation**: Penegakan format custom URL yang ketat
- **File Access Control**: Aturan .htaccess untuk memblokir file sensitif

## Struktur Proyek

```
ai_linktree/
├── setup.php          # File konfigurasi
├── index.php          # Halaman aplikasi utama
├── generate.php       # Endpoint generasi AI
├── save.php           # Endpoint simpan LinkTree
├── database.sql       # Skema database
├── .htaccess          # Aturan rewrite Apache
├── layout/            # Include header/footer
│   ├── header.php
│   └── footer.php
├── setting/           # Pengaturan inti
│   ├── connect.php    # Koneksi database
│   └── csrf_token.php # Handler token CSRF
└── lt/                # Halaman publik LinkTree
    └── index.php      # Renderer LinkTree
```

## Endpoint API

### GET `/generate.php?get_limit=1`

Mengembalikan status limit generasi saat ini:

```json
{
  "success": true,
  "generate_count": 2,
  "remaining": 1
}
```

### POST `/generate.php`

Generate LinkTree baru:

```json
{
  "success": true,
  "html": "<html>...</html>",
  "generate_count": 3,
  "remaining": 0
}
```

### POST `/save.php`

Simpan LinkTree yang dihasilkan:

```json
{
  "success": true,
  "url": "http://domain.com/lt/url-kustom"
}
```

## Troubleshooting

### "Koneksi database gagal"

- Periksa kredensial database di `setup.php`
- Pastikan service MySQL berjalan
- Verifikasi database sudah dibuat

### "Custom URL sudah digunakan"

- Pilih custom URL yang berbeda
- Cek database untuk URL yang sudah ada

### "Limit generate tercapai"

- Tunggu 24 jam untuk reset
- Atau hapus cookies session

### "Terjadi kesalahan sistem"

- Periksa error logs di `error.log` (jika enabled)
- Verifikasi API token valid
- Pastikan ekstensi cURL enabled

## Tips Performa

1. **Enable OPcache** (php.ini):

   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   ```

2. **Database Indexing**:

   ```sql
   CREATE INDEX idx_custom_url ON linktrees(custom_url);
   ```

3. **CDN untuk Assets**: Gunakan CDN untuk library CSS/JS di production

## Kontribusi

Kontribusi sangat diterima! Silakan ikuti panduan berikut:

1. Fork repository
2. Buat feature branch (`git checkout -b feature/FiturKeren`)
3. Commit perubahan Anda (`git commit -m 'Tambah fitur keren'`)
4. Push ke branch (`git push origin feature/FiturKeren`)
5. Buat Pull Request

## Lisensi

Proyek ini dilisensikan di bawah MIT License - lihat file LICENSE untuk detail.

## Credits

- **Developer**: Aang Baejuri
- **Model AI**: DeepSeek V3.2 via Hugging Face
- **UI Framework**: TailBoot CSS
- **Icons**: Lucide Icons

## Dukungan

Untuk issues dan pertanyaan:

- GitHub Issues: [https://github.com/aangbaejuri/linktree/issues](https://github.com/aangbaejuri/linktree/issues)

## Changelog

### Versi 1.0.0 (02 Januari 2026)

- Rilis awal
- Generasi LinkTree bertenaga AI
- Dukungan Custom URL
- Rate limiting (3x/hari)
- Optimasi SEO
- Mekanisme auto-retry
- Desain responsive
