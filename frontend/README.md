<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Frontend Aplikasi Validasi Dokumen - Laravel Open Admin

Aplikasi frontend berbasis **Laravel** dengan **Open Admin** untuk sistem validasi dokumen menggunakan teknologi AI. Frontend ini berfungsi sebagai antarmuka admin yang terintegrasi dengan [Backend FastAPI](../backend/README.md) untuk memproses ekstraksi dan validasi dokumen secara real-time.

## 📋 Deskripsi Proyek

Frontend Laravel + Open Admin ini menyediakan antarmuka web untuk:
- Upload file dokumen PDF
- Mengelola tiket ekstraksi dokumen
- Melihat hasil ekstraksi dari Azure Document Intelligence
- Melakukan validasi dokumen dengan AI-powered review
- Tracking status pemrosesan dokumen
- Dashboard untuk manajemen dokumen

## ✨ Fitur Utama

- ✅ **Admin Panel (Open Admin)**: Dashboard dan CRUD berbasis Laravel Open Admin
- ✅ **Upload Dokumen**: Dukungan multi-file upload untuk dokumen PDF
- ✅ **Manajemen Tiket**: Sistem tiket untuk tracking setiap dokumen yang diproses
- ✅ **Integrasi Backend API**: Koneksi dengan FastAPI backend melalui Guzzle HTTP (REST API)
- ✅ **Real-time Status**: Monitoring status ekstraksi dan validasi dokumen
- ✅ **Hasil Ekstraksi**: Menampilkan data yang telah diekstrak dengan format yang mudah dibaca
- ✅ **Validasi Data**: Menampilkan hasil validasi typo, tanggal, dan harga dari backend
- ✅ **AI Review**: Menampilkan review tingkat lanjut menggunakan OpenAI
- ✅ **Error Handling**: Penanganan error yang graceful dengan pesan yang informatif
- ✅ **Open Admin Extensions**: CKEditor, Log Viewer, Media Manager, Config, Reporter, Scheduling

## 🛠️ Tech Stack

### Framework & Web Server
- **Laravel** (v8.x) - Web application framework dengan PHP
- **Laravel Open Admin** - Admin panel dan backend UI
- **Blade** - Template engine Laravel

### HTTP & API
- **Guzzle HTTP** - HTTP client untuk request ke backend FastAPI
- **Laravel HTTP Client** - Alternatif built-in HTTP client

### Database & ORM
- **MySQL** - Database
- **Eloquent ORM** - Database abstraction layer
- **Laravel Migrations** - Database schema management

### Open Admin Extensions
- **open-admin-ext/ckeditor** - Rich text editor
- **open-admin-ext/log-viewer** - Log viewer
- **open-admin-ext/media-manager** - Media manager
- **open-admin-ext/config** - Config manager
- **open-admin-ext/reporter** - Reporter
- **open-admin-ext/scheduling** - Task scheduling
- **open-admin-ext/api-tester** - API tester
- **open-admin-ext/grid-sortable** - Grid sortable
- **open-admin-ext/helpers** - Helpers

### Development
- **PHPUnit** - PHP testing framework

## 📁 Struktur Proyek

```
frontend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php      # Dashboard & home
│   │   │   ├── DocumentController.php       # Upload & manajemen dokumen
│   │   │   ├── ExtractionController.php     # Ekstraksi dokumen
│   │   │   ├── ValidationController.php     # Validasi dokumen
│   │   │   └── TicketController.php         # Manajemen tiket
│   │   └── Requests/
│   │       ├── StoreDocumentRequest.php     # Validasi upload dokumen
│   │       └── ProcessDocumentRequest.php   # Validasi proses dokumen
│   ├── Services/
│   │   ├── BackendApiService.php            # Koneksi ke FastAPI backend
│   │   ├── DocumentService.php              # Business logic dokumen
│   │   └── TicketService.php                # Business logic tiket
│   ├── Models/
│   │   ├── Document.php                     # Model dokumen
│   │   ├── Ticket.php                       # Model tiket
│   │   └── ExtractionResult.php             # Model hasil ekstraksi
│   └── Jobs/
│       └── ProcessDocumentJob.php            # Queue job untuk processing
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php                # Layout utama
│   │   │   └── auth.blade.php               # Layout autentikasi
│   │   ├── components/
│   │   │   ├── navbar.blade.php             # Navigation bar
│   │   │   ├── sidebar.blade.php            # Sidebar menu
│   │   │   ├── form-input.blade.php         # Reusable form components
│   │   │   └── alert.blade.php              # Alert components
│   │   ├── pages/
│   │   │   ├── dashboard.blade.php          # Halaman dashboard
│   │   │   ├── documents/
│   │   │   │   ├── index.blade.php          # Daftar dokumen
│   │   │   │   ├── create.blade.php         # Upload dokumen
│   │   │   │   └── show.blade.php           # Detail dokumen & hasil
│   │   │   ├── tickets/
│   │   │   │   ├── index.blade.php          # Daftar tiket
│   │   │   │   └── show.blade.php           # Detail tiket
│   │   │   └── extraction/
│   │   │       └── results.blade.php        # Hasil ekstraksi
│   │   └── auth/
│   │       ├── login.blade.php
│   │       └── register.blade.php
│   ├── css/
│   │   └── app.css                          # Custom CSS
│   └── js/
│       ├── app.js                           # Main JavaScript
│       └── document-handler.js              # Document upload handler
├── routes/
│   ├── web.php                              # Web routes
│   └── api.php                              # API routes (internal)
├── database/
│   ├── migrations/
│   │   ├── create_documents_table.php
│   │   ├── create_tickets_table.php
│   │   └── create_extraction_results_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── AdminUserSeeder.php              # Open Admin user seeder
├── config/
│   ├── app.php
│   ├── database.php
│   ├── admin.php                             # Open Admin config
│   └── services.php                         # Backend API config
├── resources/
│   └── lang/                                # Open Admin translations (en, id, etc.)
├── .env.example
├── artisan
├── composer.json
└── README.md
```

## 🏗️ Arsitektur Sistem

Frontend menggunakan **MVC Pattern** dengan pemisahan tanggung jawab yang jelas:

### 1. Controller Layer (`app/Http/Controllers/`)
- Menangani HTTP requests dari user
- Validasi input menggunakan form requests
- Koordinasi dengan services
- Return responses (views atau JSON)

### 2. Service Layer (`app/Services/`)
- **BackendApiService**: Menghandle komunikasi dengan FastAPI backend
- **DocumentService**: Business logic untuk dokumen
- **TicketService**: Business logic untuk tiket dan tracking

### 3. Model Layer (`app/Models/`)
- Eloquent models untuk database interactions
- Relationships antar entities
- Query builder methods

### 4. View Layer (`resources/views/` & Open Admin)
- Blade templates untuk rendering HTML
- Open Admin menyediakan layout dan komponen admin
- Reusable components untuk UI

### 5. Queue Jobs (`app/Jobs/`)
- Background processing untuk dokumen
- Async handling untuk API calls ke backend

## 🔗 Integrasi dengan Backend

Frontend ini terhubung dengan Backend FastAPI melalui HTTP REST API:

### Endpoints Backend yang Digunakan

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/information-extraction` | POST | Upload file PDF & ekstraksi informasi |
| `/review` | POST | Validasi dokumen dengan multi-layer validation |
| `/health` | GET | Health check backend |

### Alur Komunikasi Frontend-Backend

```
1. User Upload Dokumen (PDF)
   ↓
2. Frontend Validasi File (extension, size)
   ↓
3. Frontend Generate Ticket ID (untuk tracking)
   ↓
4. Frontend Call Backend: POST /information-extraction
   │   ├── Kirim: ticket, files
   │   └── Terima: extraction results, OCR data
   ↓
5. Frontend Simpan Results ke Database
   ↓
6. User Lihat Hasil Ekstraksi
   ↓
7. User Input Ground Truth Data (opsional)
   ↓
8. Frontend Call Backend: POST /review
   │   ├── Kirim: ticket, ground_truth
   │   └── Terima: validation results (typo, date, price)
   ↓
9. Frontend Display Validation Report
   ↓
10. User Review Results & AI Insights
```

## 🚀 Instalasi & Setup

### Prerequisites
- PHP 7.3 atau 8.0+ (sesuai `composer.json`)
- Composer (PHP package manager)
- MySQL (server & database)
- Backend FastAPI harus sudah berjalan di port 8000

### Langkah Instalasi

1. **Masuk ke folder frontend**
```bash
cd frontend
```

2. **Install PHP Dependencies**
```bash
composer install
```

3. **Setup Environment Variables**
```bash
cp .env.example .env
```

Edit file `.env` dan atur konfigurasi:
```env
APP_NAME="Document Validator"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

BACKEND_API_URL=http://localhost:8000
BACKEND_TIMEOUT=300
```

4. **Generate Application Key**
```bash
php artisan key:generate
```

5. **Setup Database MySQL**
Buat database di MySQL, lalu jalankan migrasi:
```bash
php artisan migrate
```

(Opsional) Seed user admin Open Admin:
```bash
php artisan db:seed --class=AdminUserSeeder
```

6. **Jalankan Development Server**
```bash
php artisan serve --port 8080
```

Aplikasi dapat diakses di `http://localhost:8080` (pastikan backend berjalan di port 8000).

## 📡 API Routes

### Dashboard
```http
GET /dashboard
```
Menampilkan dashboard dengan summary dokumen dan tiket

### Documents (Dokumen)
```http
GET /documents                # Daftar semua dokumen
POST /documents               # Upload dokumen baru
GET /documents/{id}           # Detail dokumen & hasil ekstraksi
DELETE /documents/{id}        # Hapus dokumen
```

### Tickets (Tiket)
```http
GET /tickets                  # Daftar semua tiket
GET /tickets/{ticket_id}      # Detail tiket & status
```

### Extraction (Ekstraksi)
```http
POST /extraction/process      # Proses ekstraksi dokumen
GET /extraction/results       # Hasil ekstraksi
```

### Validation (Validasi)
```http
POST /validation/validate     # Jalankan validasi dokumen
GET /validation/results       # Hasil validasi
```

## 🧪 Testing

Jalankan tests dengan PHPUnit:
```bash
php artisan test
```

## 📊 Monitoring & Logging

Frontend menggunakan Laravel logging dengan level INFO. Logs mencakup:
- Document uploads dan processing
- Backend API calls dan responses
- Validation results
- Error tracking
- User actions

Log file berlokasi di `storage/logs/laravel.log`

## 📝 Dokumentasi & Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Open Admin](https://github.com/open-admin-org/open-admin) - Admin panel untuk Laravel
- [Backend API Documentation](../backend/README.md)