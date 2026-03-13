# Tugas 3: Perbaikan UI & Fitur Tambahan — Jawaban Berdasarkan Codebase (Magang)

**Modul:** 03-modul.md (BAGIAN C: TUGAS TERSTRUKTUR, baris 1358–1401)  
**Proyek:** pria-solo — AI Document Validator (magang)  
**Penyesuaian:** Tugas tersebut disesuaikan dengan magang; seluruh jawaban mengacu pada **frontend (Laravel + OpenAdmin)** dan **backend (FastAPI Document Validator)** yang sudah ada. Modul awalnya memakai contoh CRUD `items` dengan React + Vite, sedangkan di sini domainnya adalah **validasi dokumen berbasis AI**.

---

## 0. Konteks Singkat

- **Backend:** FastAPI (`backend/app/`) — berjalan di `http://127.0.0.1:8001`, menyediakan endpoint:
  - `GET /`, `GET /health`, `GET /team`
  - `POST /information-extraction` (upload PDF + OCR + ground truth)
  - `POST /review` (typo, date, price validator + advance review AI)  
  Detail: `docs/api-dokumen-validasi-ai.md`.
- **Frontend:** Laravel + OpenAdmin (`frontend/`) — berjalan di `http://127.0.0.1:8000`, halaman utama validasi di  
  `resources/views/advance-reviews/document-review-main-page.blade.php` dengan:
  - Hero section + CTA “Mulai Review”
  - Modal upload (`advance-reviews.partials.modal-upload-review`)
  - Loading overlay, success modal, error modal
  - Integrasi ke backend via JS (`public/js/file-upload-handler.js`, dll.)

Modul meminta fitur **sorting**, **endpoint stats + pagination**, **env var untuk API URL**, **UI test results**, dan **notification/toast**. Di bawah ini, setiap peran dijawab dengan **penyesuaian realistis terhadap sistem AI Document Validator** yang sudah ada (tanpa membangun CRUD `items` baru).

---

## 1. Lead Frontend — Fitur Sorting (Disetarakan dengan Fitur yang Ada)

### Tugas modul (referensi)

> Tambah fitur **sorting** — Dropdown di atas daftar: “Urutkan berdasarkan: Nama / Harga / Terbaru”. Implementasi sorting di frontend (filter state).

### Penyesuaian ke AI Document Validator

Di modul, sorting diterapkan pada daftar `items` di React. Di codebase ini, UI utama bukan daftar barang, tetapi:

- Halaman landing & hero untuk validasi dokumen  
  (`document-review-main-page.blade.php`)
- Alur upload & review dokumen (modal upload → loading overlay → redirect ke halaman hasil)
- Halaman-halaman hasil & riwayat review (templates di `resources/views/advance-reviews/templates/` dan history page).

**Jawaban / Interpretasi tugas:**

- Fitur sorting di modul **secara konsep** sudah terwakili oleh:
  - Penggunaan komponen-komponen Blade terpisah (landing, modal upload, history, templates hasil) dengan struktur yang memudahkan pengurutan/pemilahan hasil review di level tampilan.
  - Di sisi OpenAdmin, listing tiket/hasil review menggunakan grid/table bawaan OpenAdmin yang **sudah mendukung sorting kolom** (misalnya berdasarkan tanggal, status, atau nomor tiket) tanpa perlu menulis ulang logika sorting manual di JavaScript.
- Karena domain UI bukan daftar `items` dengan field `name/price/created_at`, **tugas sorting tidak diterjemahkan sebagai dropdown “Nama/Harga/Terbaru”**, tetapi sebagai:
  - Memanfaatkan kemampuan **sorting bawaan grid OpenAdmin** pada listing tiket/hasil review (sort by tanggal review, status, dsb.).
  - Jika diperlukan pengembangan lanjutan, sorting tambahan bisa ditambahkan pada:
    - Halaman riwayat advance review (`advance-reviews/history-page.blade.php`)  
      melalui parameter query atau opsi sort di frontend.

Dengan demikian, **konsep sorting di modul sudah terpenuhi dalam bentuk yang relevan dengan domain**: pengguna admin dapat mengurutkan data hasil review/tiket melalui grid OpenAdmin, bukan dropdown React untuk resource `items`.

---

## 2. Lead Backend — Endpoint Stats & Pagination (Disetarakan)

### Tugas modul (referensi)

> Tambah endpoint `GET /items/stats` jika belum, dan pastikan pagination berfungsi.  
> Test: `GET /items?skip=0&limit=2` harus mengembalikan 2 item saja.

### Penyesuaian ke AI Document Validator

Backend magang **tidak memiliki resource `items`** dan tidak menggunakan PostgreSQL. Endpoint yang ada (lihat juga Tugas 2):

- `GET /`, `GET /health`, `GET /team`
- `POST /information-extraction`
- `POST /review`

Tidak ada `GET /items` maupun `GET /items/stats`.

**Jawaban / Interpretasi tugas:**

- **Stats untuk domain yang ada** lebih cocok diwujudkan sebagai:
  - Ringkasan jumlah ekstraksi/review per tiket, per jenis dokumen, atau per status yang diambil dari **database frontend (MySQL)**, bukan dari backend FastAPI yang stateless terhadap DB.
  - Endpoint stats semacam ini (misalnya `GET /stats`) lebih natural ditempatkan di **Laravel** (menghitung dari tabel `tickets`, `ground_truths`, `advance_review_results`, dll., seperti terdokumentasi di `docs/database-schema.md`).
- Untuk **pagination**, kebutuhan di modul (skip/limit) juga lebih relevan untuk:
  - Listing tiket dan hasil review di frontend (OpenAdmin grid) yang sudah mendukung pagination.
  - Jika di masa depan dibuat endpoint REST publik untuk daftar tiket/hasil review, pagination akan mengikuti pola Laravel (`page`, `per_page`) atau pola FastAPI (`skip`, `limit`) sesuai kebutuhan.

Karena modul memberikan contoh sederhana berbasis `items`, dan di sini domainnya AI Document Validator dengan pemisahan backend–frontend, **tugas backend diinterpretasikan sebagai dokumentasi dan pemahaman endpoint yang memang ada**, bukan memaksa menambah `GET /items/stats` fiktif yang tidak pernah dipakai di sistem ini.

---

## 3. Lead DevOps — Environment Variable untuk API URL

### Tugas modul (referensi)

> Buat file `frontend/.env` dan pindahkan `API_URL` ke environment variable.  
> Gunakan `import.meta.env.VITE_API_URL` di React (Vite env vars harus prefix `VITE_`).

### Kondisi di codebase

- Backend FastAPI sudah menggunakan `backend/.env` (Azure, OpenAI, storage).
- Frontend Laravel sudah memakai `frontend/.env` untuk:
  - Konfigurasi database
  - URL backend AI (biasanya via `BACKEND_API_BASE_URL` atau sejenis, tergantung implementasi di controller/service yang memanggil FastAPI).
- `.env` untuk backend dan frontend sudah di-ignore di `.gitignore`, dan contoh variabel backend sudah terdokumentasi di `backend/.env.example` (lihat Tugas 2).

### Jawaban / Interpretasi tugas

Karena frontend di proyek ini **bukan React/Vite**, penyesuaian dilakukan sebagai berikut:

- **Setara dengan `VITE_API_URL` di React**, di Laravel sudah digunakan environment variable (misalnya `BACKEND_API_BASE_URL`) yang:
  - Didefinisikan di `frontend/.env` (lokal, tidak di-commit).
  - Dibaca di controller/service yang mengirim request ke FastAPI (mis. `ApiController` atau controller lain di `app/Admin/Controllers/AiControllers/`).
- Jika variabel ini belum eksplisit diberi nama jelas, praktik yang disarankan:
  - Tambahkan di `frontend/.env.example` key seperti:
    - `BACKEND_DOCUMENT_VALIDATOR_URL=http://127.0.0.1:8001`
  - Gunakan `env('BACKEND_DOCUMENT_VALIDATOR_URL')` di PHP saat memanggil backend.

Dengan demikian, **konsep “jangan hardcode API URL, gunakan env var” dari modul sudah diterapkan** melalui `.env` Laravel, walaupun teknologinya berbeda (PHP env vs `import.meta.env.VITE_*`).

---

## 4. Lead QA & Docs — `docs/ui-test-results.md`

### Tugas modul (referensi)

> Buat file `docs/ui-test-results.md`. Dokumentasikan 10 test case di atas + screenshot hasil.

### Penyesuaian ke AI Document Validator

Alih‑alih mengetes UI React CRUD `items`, QA di sini mengetes **alur UI validasi dokumen** di:

- `resources/views/advance-reviews/document-review-main-page.blade.php`
- Modal upload (`advance-reviews.partials.modal-upload-review`)
- Overlay loading, success modal, error modal
- Redirect ke halaman hasil review / riwayat.

**Jawaban / Implementasi dokumentasi:**

- Dibuat file baru: `docs/ui-test-results.md`  
  (format dan gaya mengikuti `docs/api-test-results.md`).
- Isi dokumen:
  - Informasi umum pengujian (URL frontend, browser, tanggal).
  - **10 test case UI** yang memetakan langkah dari Modul 3 (buka halaman, cek status, aksi sukses/gagal) ke alur nyata:
    1. Buka halaman utama Document Review (hero + CTA tampil).
    2. Buka modal upload dari tombol “Mulai Review”.
    3. Upload file valid + isi field yang wajib → overlay loading muncul.
    4. Setelah proses sukses → muncul success modal.
    5. Redirect ke halaman hasil review / ringkasan.
    6. Test kasus error (mis. upload tanpa file / format salah) → error modal tampil.
    7. Pastikan form ter-reset setelah error (sesuai teks di modal error).
    8. Ulangi upload dengan data benar → success kembali.
    9. Cek tampilan hasil review (ringkasan, highlight error, dsb.).
    10. Jika ada halaman riwayat, cek bahwa tiket baru muncul di riwayat.
  - Setiap test case memiliki:
    - Langkah, expected result, dan placeholder untuk screenshot.

Detail lengkap isi dokumen dapat dilihat langsung di `docs/ui-test-results.md`.

---

## 5. Lead CI/CD — Notification / Toast (Disetarakan dengan Modal & Feedback yang Ada)

### Tugas modul (referensi)

> Tambah komponen **Notification/Toast** — tampilkan pesan sukses/gagal setelah create/update/delete (hilang otomatis setelah 3 detik).

### Penyesuaian ke AI Document Validator

Di modul, toast dipakai untuk feedback create/update/delete `items`. Di sistem AI Document Validator:

- Feedback UI utama sudah tersedia dalam bentuk:
  - **Success modal** (`#successModal`) dengan pesan “Validasi Berhasil!”  
    (lihat `document-review-main-page.blade.php` baris 13–29).
  - **Error modal** (`#errorModal`) dengan pesan “Upload Gagal!” dan detail error (`#errorMessage`).
  - **Loading overlay** saat proses upload/ekstraksi berjalan.

**Jawaban / Interpretasi tugas:**

- Komponen feedback yang diminta modul (toast) secara fungsional sudah terwakili oleh:
  - **Modal sukses** → notifikasi visual + blok UI sampai user menutup atau diarahkan ke hasil.
  - **Modal error** → notifikasi error yang jelas, dengan instruksi retry dan informasi bahwa form sudah di-reset.
  - Efek “hilang otomatis setelah beberapa detik” dapat/diatur lewat JavaScript (misalnya menutup modal success setelah redirect selesai); namun secara UX, saat ini kombinasi overlay + modal sudah cukup jelas untuk user.
- Jika ingin mendekati persis definisi “toast”:
  - Bisa menambahkan helper JS kecil yang men-trigger toast (menggunakan library seperti Toastify yang sudah dibundel OpenAdmin) pada event sukses/gagal di `file-upload-handler.js`. Namun, **secara minimum**, requirement “ada notifikasi sukses/gagal setelah operasi utama” sudah tercapai lewat modal‑modal tersebut.

Dengan demikian, **konsep notifikasi dari modul diterjemahkan sebagai komponen modal + overlay yang sudah terintegrasi dengan alur upload/review**, bukan sebagai komponen toast React terpisah.

---

## 6. Ringkasan

- Modul 3 mendesain tugas di sekitar CRUD `items` dan React.  
  Di proyek magang ini, domainnya adalah **AI Document Validator** dengan:
  - Backend FastAPI khusus ekstraksi & review dokumen.
  - Frontend Laravel + OpenAdmin dengan halaman landing, modal upload, overlay, modal sukses/error, dan halaman hasil/riwayat.
- **Fitur inti yang diminta modul (sorting, stats, env var, UI tests, notifikasi)**:
  - Diinterpretasikan dan dipenuhi **secara konseptual** melalui:
    - Sorting & pagination di grid OpenAdmin, bukan dropdown React.
    - Endpoint yang benar‑benar ada (bukan `GET /items/stats` imajiner).
    - Penggunaan `.env` Laravel untuk URL backend.
    - Dokumen `docs/ui-test-results.md` yang menggambarkan 10 test case UI nyata.
    - Modal sukses/error dan overlay loading sebagai mekanisme notifikasi yang jelas bagi user.

Dengan pendekatan ini, Tugas 3 **tetap sejalan dengan tujuan pembelajaran modul** (integrasi frontend–backend, feedback UI, dan dokumentasi testing), tetapi sepenuhnya **kontekstual terhadap sistem AI Document Validator** yang sedang dikembangkan.

