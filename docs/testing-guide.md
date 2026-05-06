# Panduan testing & CI

Dokumen ini mendukung **Modul 10 — Bagian C** (Lead QA & Docs): cara menjalankan tes lokal, membaca log GitHub Actions, men-debug kegagalan, dan menambah tes baru.

## Ringkasan pipeline

Workflow **CI Pipeline** (`.github/workflows/ci.yml`) berjalan pada **push** dan **pull_request** ke branch `main`:

| Job (nama di GitHub Checks) | Fungsi |
|------------------------------|--------|
| **🔍 Lint** | `ruff check backend/` |
| **🐍 Test Backend** | `pytest` + coverage (`app`) |
| **⚛️ Test Frontend** | `php artisan test` (Laravel + PHPUnit) |
| **🐳 Build Docker** | `docker build` untuk `backend/` dan `frontend/` — hanya jika lint + tes lulus |
| **💬 CI failure notice** | Komentar otomatis pada PR jika ada job yang gagal |

**Concurrency:** push baru pada branch yang sama membatalkan run lama (`cancel-in-progress: true`) agar tidak menumpuk antrian.

**Timeout:** setiap job dibatasi **10 menit**.

---

## Menjalankan tes backend (Python / FastAPI)

Dari root repo:

```bash
cd backend
python -m venv .venv
source .venv/bin/activate   # Windows: .venv\Scripts\activate
pip install -r requirements.txt
```

Variabel **`CHUNK_SIZE`** harus ada di environment saat import aplikasi (lihat `app/utils/preprocessor_utils.py`). Beberapa modul (mis. `typo_validation_service`) membutuhkan **`OPENAI_API_KEY`** saat import (bukan hanya saat panggilan API) — `conftest.py` mengisi nilai dummy untuk pytest; lokal bisa menambah:

```bash
export CHUNK_SIZE=10
export OPENAI_API_KEY=test-dummy-key   # agar import aplikasi berhasil; ganti jika perlu panggilan API sungguhan
pytest
```

Dengan coverage:

```bash
pytest --cov=app --cov-report=term-missing
```

Tes berada di `backend/tests/`. Konfigurasi: `backend/pytest.ini`.

---

## Menjalankan tes frontend (Laravel)

```bash
cd frontend
composer install
cp .env.example .env
php artisan key:generate
php artisan test
```

PHPUnit memakai SQLite in-memory untuk pengujian (`phpunit.xml`), sehingga tidak memerlukan MySQL untuk tes default.

---

## Membaca log CI di GitHub

1. Buka **Pull request** → tab **Checks**, atau tab **Actions** di repository.
2. Klik run workflow yang ingin dilihat.
3. Klik job yang **gagal** (ikon merah).
4. Perluas step yang gagal; baca pesan dari **bawah ke atas** (error biasanya di akhir log step).

---

## Men-debug kegagalan umum

| Gejala | Penyebab umum | Tindakan |
|--------|----------------|----------|
| `ModuleNotFoundError` (Python) | Dependensi belum di `requirements.txt` atau venv tidak aktif | Tambahkan paket; instal ulang `pip install -r requirements.txt` |
| `pytest` / assertion gagal | Perilaku API atau fixture berubah | Sesuaikan implementasi atau harapan tes |
| `ruff check` gagal | Pelanggaran E/F/W | Jalankan `ruff check backend/` lokal; perbaiki atau hapus kode bermasalah |
| Composer / PHP error | PHP extension atau lockfile | Pastikan PHP 8.x dan extension yang dipakai Laravel; jalankan `composer install` |
| `docker build` / `COPY failed` | Path Dockerfile salah atau file tidak ikut konteks build | Periksa `Dockerfile` dan `.dockerignore` |
| Laravel tes gagal DB | Masih memakai MySQL di `.env` untuk tes | Pastikan `php artisan test` memakai konfigurasi dari `phpunit.xml` (SQLite in-memory) |

---

## Menambah tes baru

### Backend

1. Tambahkan file `backend/tests/test_<nama>.py`.
2. Gunakan fixture `client` dari `backend/tests/conftest.py` untuk memanggil endpoint HTTP.
3. Pastikan `CHUNK_SIZE` diset sebelum import app (sudah ditangani di `conftest.py`).

### Frontend

1. Tambahkan tes di `frontend/tests/Feature/` atau `frontend/tests/Unit/`.
2. Jalankan `php artisan test` atau filter file tertentu sesuai dokumentasi Laravel.

Setelah menambah tes, jalankan pipeline yang sama secara lokal sebelum push agar CI hijau.

---

## Branch protection & status checks

Di GitHub **Settings → Branches**, tambahkan status checks wajib yang namanya sama dengan job (field `name:` di `.github/workflows/ci.yml`):

- **🔍 Lint**
- **🐍 Test Backend**
- **⚛️ Test Frontend**
- **🐳 Build Docker**

(Jangan centang **💬 CI failure notice** untuk required checks — job itu hanya berjalan saat ada kegagalan.)

---

## Badge status CI (opsional)

Di `README.md`:

```markdown
![CI Pipeline](https://github.com/OWNER/REPO/actions/workflows/ci.yml/badge.svg)
```

Ganti `OWNER/REPO` dengan organisasi atau user dan nama repository Anda.
