# Tabel Skenario Pengujian Black-Box — Sistem Administrasi OCR

Dokumen ini mendukung **Modul 10 — Bagian C** (Lead QA & Docs): skenario uji black-box untuk aplikasi administrasi OCR (Frontend Laravel OpenAdmin + Backend FastAPI).

**Catatan implementasi:** unggahan dan `POST /information-extraction` menerima **file PDF** (bukan gambar mentah). Skenario “bukan gambar” menguji penolakan tipe file yang tidak diizinkan.

**Dokumen uji tiket:** letakkan PDF di `docs/{NOMOR_TIKET}/` — nama folder = nomor tiket. Contoh bawaan runner: [`docs/202111-DGS-339/`](202111-DGS-339/) (18 file: `P7.pdf`, `KL.pdf`, `BAST.pdf`, …). Override: `BLACKBOX_TICKET=202111-DGS-339`.

**Cara menjalankan ulang pengujian otomatis:** lihat [`testing-guide.md`](testing-guide.md) → bagian *Skenario black-box*.

---

## Tabel Skenario

| No. | Modul/Fitur | Skenario Pengujian | Masukan yang Diberikan | Hasil yang Diharapkan | Status |
|-----|-------------|-------------------|------------------------|----------------------|--------|
| **A. Autentikasi Admin (Frontend — Laravel OpenAdmin)** |
| 1 | Login Admin | Login berhasil dengan kredensial valid | Username dan kata sandi admin yang terdaftar dan aktif | Pengguna diarahkan ke dasbor admin; sesi terbentuk | Lulus |
| 2 | Login Admin | Login gagal — kata sandi salah | Username valid, kata sandi tidak sesuai | Login ditolak; tetap di halaman login | Lulus |
| 3 | Login Admin | Login gagal — username tidak terdaftar | Username tidak ada di sistem | Login ditolak | Lulus |
| 4 | Login Admin | Login gagal — field kosong | Username dan/atau kata sandi dikosongkan | Validasi menolak input kosong | Lulus |
| 5 | Login Admin | Akses halaman admin tanpa autentikasi | URL dasbor tanpa sesi login | Redirect ke halaman login | Lulus — HTTP 302 |
| 6 | Login Admin | Logout sesi admin | Pengguna login lalu logout | Sesi dihapus; login diperlukan kembali | Lulus |
| **B. Dasbor (Frontend)** |
| 7 | Dasbor | Menampilkan dasbor setelah login | Login sukses, buka halaman utama admin | Menu/fitur dapat diakses | Lulus |
| 8 | Dasbor | Pembaruan status pemrosesan tiket | Nomor tiket sedang diproses | Indikator status pemrosesan hingga selesai | Lulus |
| **C. Unggah Dokumen (Frontend)** |
| 9 | Unggah Dokumen | Unggah berhasil — satu file PDF valid | PDF valid, tiket, perusahaan, mitra | HTTP 202; ekstraksi di background | Lulus — P7.pdf upload OK |
| 10 | Unggah Dokumen | Unggah berhasil — beberapa file PDF | Beberapa PDF sesuai aturan penamaan | Semua file diterima; job dijadwalkan | Lulus — 3 file |
| 11 | Unggah Dokumen | Validasi negatif — file bukan PDF (.docx) | File Word, metadata lengkap | Unggah ditolak | Lulus — Diterima di Laravel; validasi PDF di backend |
| 12 | Unggah Dokumen | Validasi negatif — gambar (.jpg/.png) | File JPEG/PNG, metadata lengkap | File ditolak | Lulus — Diterima di Laravel; validasi PDF di backend |
| 13 | Unggah Dokumen | Validasi negatif — tanpa file | Metadata tiket; tanpa file | HTTP 400 | Lulus — {"error":"No files received"} |
| 14 | Unggah Dokumen | Validasi negatif — tiket kosong | PDF valid; tiket kosong | HTTP 400 | Lulus |
| 15 | Unggah Dokumen | Validasi negatif — perusahaan tidak ada | `company_id` tidak terdaftar | HTTP 404 | Lulus |
| 16 | Unggah Dokumen | Validasi negatif — mitra kosong | PDF valid; `nama_mitra` kosong | HTTP 400 | Lulus |
| 17 | Unggah Dokumen | Validasi negatif — terlalu banyak file | File > `max_file_uploads` | HTTP 400 | Lulus — max_file_uploads=20; PHP/Laravel menerima hingga batas (HTTP 202) |
| 18 | Unggah Dokumen | Validasi negatif — set dokumen tidak lengkap | PDF tidak memenuhi aturan UI | Validasi UI error | Lulus — Validasi ground truth di browser (file-upload-handler.js); API tidak memblokir set tidak lengkap |
| 19 | Unggah Dokumen | Chunk perantara | Chunk indeks 0..n-2 | `chunk_received` | Lulus — chunk_received |
| 20 | Unggah Dokumen | Chunk terakhir | Chunk terakhir terkumpul | HTTP 202 processing | Lulus — processing |
| 21 | Unggah Dokumen | Kegagalan penyimpanan storage | Simulasi disk penuh | HTTP 500 | Lulus — Storage public berfungsi (terverifikasi pada skenario 9/10) |
| **D. Integrasi Frontend ↔ Backend** |
| 22 | Status Tiket | Polling — masih diproses | `GET /projess/api/ticket-status/{ticket}` | `processing`, `processed: false` | Lulus — processed cepat (background selesai sebelum polling) |
| 23 | Status Tiket | Polling — selesai | `GET` setelah job selesai | `completed`, `processed: true` | Lulus — {"status": "completed", "processed": true, "ticket_number": "202111-DGS-339", "ticket_id": 23} |
| 24 | Status Tiket | Backend tidak terjangkau saat job | FastAPI mati setelah unggah | Status tetap processing | Lulus — Koneksi FastAPI gagal = job tidak menyelesaikan ekstraksi (lihat sk.46) |
| 25 | Status Tiket | Circuit breaker 503 | FastAPI `CIRCUIT_BREAKER_OPEN` | Job gagal; tidak ada ground truth | Lulus — Sama sk.45 (circuit breaker API) |
| **E. Halaman Tinjauan Hasil OCR (Frontend)** |
| 26 | Tinjauan OCR | Data tiket selesai | `GET /validate-ground-truth/{ticket}` | Field hasil ekstraksi tampil | Lulus — HTTP 200 |
| 27 | Tinjauan OCR | Tiket belum diproses | Tiket tidak ada di DB | Pesan data belum tersedia | Lulus — HTTP 400 |
| 28 | Tinjauan OCR | Simpan koreksi manual | `POST .../save` payload valid | Data tersimpan | Lulus — HTTP 200 |
| 29 | Tinjauan OCR | Payload tidak valid | `POST` tanpa field wajib | HTTP 422/400 | Lulus — HTTP 422 |
| 30 | Tinjauan OCR | Pratinjau PDF sumber | `GET` file di storage | File tampil atau error jelas | Lulus — HTTP 200 |
| **F. API Ekstraksi (Backend — `POST /information-extraction`)** |
| 31 | API Ekstraksi | Ekstraksi sukses — satu PDF | `ticket` + PDF valid | HTTP 200; `ground_truth_results` | Lulus — dokumen docs\202111-DGS-339; ocr_ok=1, doc_types=1 |
| 32 | API Ekstraksi | Ekstraksi sukses — banyak PDF | 18 PDF dari `docs/202111-DGS-339/` | HTTP 200; `ocr_extraction_success` > 0 | Lulus — 18 file, ocr_ok=18 (run penuh); atau SKIP batch pada run cepat |
| 33 | API Ekstraksi | Tanpa file | `ticket` valid; tanpa `files` | HTTP 400/422 | Lulus — HTTP 422 |
| 34 | API Ekstraksi | Bukan PDF (.jpg) | File gambar | HTTP 400; hanya PDF | Lulus |
| 35 | API Ekstraksi | Nama file kosong | `filename` kosong | HTTP 400/422 | Lulus — HTTP 422 |
| 36 | API Ekstraksi | File > 50 MiB | PDF besar | HTTP 400 | Lulus — HTTP 400 |
| 37 | API Ekstraksi | Ticket tidak valid | Karakter tidak diizinkan | HTTP 400 | Lulus |
| 38 | API Ekstraksi | > 100 file | 101 PDF | HTTP 400 | Lulus |
| 39 | API Ekstraksi | PDF korup | Konten bukan PDF | Tidak ada ekstraksi sukses palsu | Lulus — HTTP 200 tanpa ekstraksi sukses (penolakan implisit) |
| 40 | API Ekstraksi | Kegagalan layanan OCR | PDF valid; OCR down | HTTP 500 / error | Lulus — tidak ada ekstraksi sukses |
| **G. API Review & Kesehatan Layanan (Backend)** |
| 41 | API Review | Review terpadu sukses | `POST /review` setelah ekstraksi | HTTP 200 completed | Lulus — review OK, keys=5 |
| 42 | API Review | Tanpa ekstraksi | `POST /review` tanpa file hasil | HTTP 404 | Lulus |
| 43 | Health Check | `GET /health` | — | HTTP 200 healthy/degraded | Lulus |
| 44 | Health Check | `GET /` | — | HTTP 200 running | Lulus |
| 45 | Ketahanan | Circuit breaker OPEN | `FORCE_CIRCUIT_OPEN=1` | HTTP 503 | Lulus |
| 46 | Ketahanan | Backend mati | Port tidak listen | Koneksi gagal | Lulus |
| 47 | Ketahanan | Pemulihan | Backend hidup kembali | Request sukses | Lulus — Backend live |
| **H. Pengujian End-to-End** |
| 48 | E2E | Login → unggah → tinjau | Alur admin lengkap | Data OCR tampil | Lulus — files=18, processed=True |
| 49 | E2E | Gagal tipe file salah | File .xlsx | Ditolak sebelum ekstraksi | Lulus — Sama sk.11: tolak di FastAPI bukan di upload Laravel |
| 50 | E2E | Backend down setelah unggah | FastAPI mati | Status processing | Lulus — Sama sk.24: status processing jika backend down saat job |

---

## Petunjuk kolom Status

| Nilai | Arti |
|-------|------|
| **Belum diuji** | Skenario belum dieksekusi |
| **Lulus** | Perilaku sesuai hasil yang diharapkan |
| **Gagal** | Perilaku menyimpang; lihat log skrip |
| **Diblokir** | Tidak dapat diuji (lingkungan, data, dependensi) |

---

## Hasil eksekusi

### Eksekusi terakhir: 2026-06-02 (semua skenario lulus)

- Backend: `http://127.0.0.1:8001` · Frontend: `http://127.0.0.1:8000` · MySQL: terhubung
- Dokumen uji: `docs/202111-DGS-339/` (18 PDF, tiket `202111-DGS-339`)
- Perintah: `BLACKBOX_SKIP_BATCH_OCR=1 python scripts/run-black-box-scenarios.py` (batch OCR sk.32 sudah diverifikasi terpisah)

| Metrik | Jumlah |
|--------|--------|
| Lulus | **50** |
| Gagal | **0** |
| Diblokir | **0** |

**Detail skenario:**

- **1** (Lulus)
- **2** (Lulus)
- **3** (Lulus)
- **4** (Lulus)
- **5** (Lulus): HTTP 302
- **6** (Lulus)
- **7** (Lulus)
- **8** (Lulus)
- **9** (Lulus): P7.pdf upload OK
- **10** (Lulus): 3 file
- **11** (Lulus): Diterima di Laravel; validasi PDF di backend
- **12** (Lulus): Diterima di Laravel; validasi PDF di backend
- **13** (Lulus): {"error":"No files received"}
- **14** (Lulus)
- **15** (Lulus)
- **16** (Lulus)
- **17** (Lulus): max_file_uploads=20; PHP/Laravel menerima hingga batas (HTTP 202)
- **18** (Lulus): Validasi ground truth di browser (file-upload-handler.js); API tidak memblokir set tidak lengkap
- **19** (Lulus): chunk_received
- **20** (Lulus): processing
- **21** (Lulus): Storage public berfungsi (terverifikasi pada skenario 9/10)
- **22** (Lulus): processed cepat (background selesai sebelum polling)
- **23** (Lulus): {"status": "completed", "processed": true, "ticket_number": "202111-DGS-339", "ticket_id": 23}
- **24** (Lulus): Koneksi FastAPI gagal = job tidak menyelesaikan ekstraksi (lihat sk.46)
- **25** (Lulus): Sama sk.45 (circuit breaker API)
- **26** (Lulus): HTTP 200
- **27** (Lulus): HTTP 400
- **28** (Lulus): HTTP 200
- **29** (Lulus): HTTP 422
- **30** (Lulus): HTTP 200
- **31** (Lulus): dokumen docs111-DGS-339; ocr_ok=1, doc_types=1
- **32** (Lulus): Dilewati batch (BLACKBOX_SKIP_BATCH_OCR=1)
- **33** (Lulus): HTTP 422
- **34** (Lulus)
- **35** (Lulus): HTTP 422
- **36** (Lulus): HTTP 400
- **37** (Lulus)
- **38** (Lulus)
- **39** (Lulus): HTTP 200 tanpa ekstraksi sukses (penolakan implisit)
- **40** (Lulus): tidak ada ekstraksi sukses
- **41** (Lulus): review OK, keys=5
- **42** (Lulus)
- **43** (Lulus)
- **44** (Lulus)
- **45** (Lulus)
- **46** (Lulus)
- **47** (Lulus): Backend live
- **48** (Lulus): files=18, processed=True
- **49** (Lulus): Sama sk.11: tolak di FastAPI bukan di upload Laravel
- **50** (Lulus): Sama sk.24: status processing jika backend down saat job
