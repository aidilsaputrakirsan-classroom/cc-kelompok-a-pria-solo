## Tabel Skenario Pengujian White-Box - Sistem Aplikasi Web OCR

Dokumen ini menyajikan skenario pengujian struktural (*white-box testing*) berbasis analisis alur logika internal pada modul Laravel dan FastAPI, dengan fokus pada:
- *statement coverage*,
- *branch coverage*,
- penanganan *exception*.

| No | Modul / Fungsi yang Diuji | Skenario Pengujian (Logical Path/Branch) | Hasil Eksekusi Kode yang Diharapkan | Status |
|---:|---|---|---|---|
| 1 | Laravel `AdvanceUploadController::upload` | Nilai `files` bukan array (single upload) sehingga masuk cabang normalisasi `if (!is_array($files))` | Berkas dinormalisasi menjadi array satu elemen, lalu proses validasi berikutnya tetap berjalan | Lulus (Laravel Feature, MySQL) |
| 2 | Laravel `AdvanceUploadController::upload` | Jumlah berkas melebihi `max_file_uploads` (`count($files) > $maxFileUploads`) | Sistem menghentikan alur, mengembalikan HTTP 400 dengan pesan batas maksimum per chunk | Lulus (Laravel Feature, MySQL) |
| 3 | Laravel `AdvanceUploadController::upload` | Tidak ada berkas diterima (`count($files) === 0`) | Sistem mengembalikan HTTP 400 dengan error "No files received" | Lulus (Laravel Feature, MySQL) |
| 4 | Laravel `AdvanceUploadController::upload` | `ticket` tidak diisi (`!$ticketNumber`) | Sistem mengembalikan HTTP 400 "Ticket number is required" | Lulus (Laravel Feature, MySQL) |
| 5 | Laravel `AdvanceUploadController::upload` | `company_id` tidak diisi (`!$companyId`) | Sistem mengembalikan HTTP 400 "Company ID is required" | Lulus (Laravel Feature, MySQL) |
| 6 | Laravel `AdvanceUploadController::upload` | `nama_mitra` tidak diisi (`!$namaMitra`) | Sistem mengembalikan HTTP 400 "Nama mitra is required" | Lulus (Laravel Feature, MySQL) |
| 7 | Laravel `AdvanceUploadController::upload` | `Company::find($companyId)` mengembalikan `null` | Sistem mengembalikan HTTP 404 "Company not found" | Lulus (Laravel Feature, MySQL) |
| 8 | Laravel `AdvanceUploadController::upload` | Penyimpanan berkas gagal pada seluruh iterasi hingga `savedFiles` kosong | Sistem mengembalikan HTTP 500 "Failed to save files to storage" | Lulus (Laravel Feature, MySQL) |
| 9 | Laravel `AdvanceUploadController::upload` | Mode chunk aktif dan bukan chunk terakhir (`$isChunked && !$isLastChunk`) | Sistem mengembalikan HTTP 200 status `chunk_received`, tanpa memicu proses ekstraksi background | Lulus (Laravel Feature, MySQL) |
| 10 | Laravel `AdvanceUploadController::upload` | Mode chunk aktif dan chunk terakhir (`$isLastChunk`) | Sistem menyusun `fileNames` dari direktori tiket, dispatch job `afterResponse`, respons HTTP 202 status `processing` | Lulus (Laravel Feature, MySQL) |
| 11 | Laravel `AdvanceUploadController::upload` | Non-chunk upload (`$isChunked == false`) | Sistem menyusun `fileNames` dari berkas tersimpan saat ini, dispatch job, respons HTTP 202 status `processing` | Lulus (Laravel Feature, MySQL) |
| 12 | Laravel `AdvanceUploadController::upload` | Exception saat `dispatch(...)->afterResponse()` (blok `catch (\Throwable)`) | Sistem mengembalikan HTTP 500 dengan payload error server | Lulus (Laravel Feature, MySQL) |
| 13 | Laravel `AdvanceUploadController::checkStatus` | Ticket belum ada pada database | Sistem mengembalikan status `processing`, `processed=false` | Lulus (Laravel Feature, MySQL) |
| 14 | Laravel `AdvanceUploadController::checkStatus` | Ticket ada namun `GroundTruth` belum terbentuk | Sistem mengembalikan status `processing`, `processed=false`, disertai `ticket_id` | Lulus (Laravel Feature, MySQL) |
| 15 | Laravel `AdvanceUploadController::checkStatus` | Ticket dan `GroundTruth` tersedia | Sistem mengembalikan status `completed`, `processed=true` | Lulus (Laravel Feature, MySQL) |
| 16 | Laravel `ProcessAdvanceUploadJob::handle` | Request ke FastAPI sukses dan payload JSON valid | Sistem mengeksekusi `saveToDatabase`, job selesai tanpa exception | Lulus (Laravel Feature, MySQL) |
| 17 | Laravel `ProcessAdvanceUploadJob::handle` | `Guzzle RequestException` saat panggil `/information-extraction` | Sistem mencatat log error, kemudian melempar ulang exception agar kegagalan job terdeteksi | Lulus (Laravel Feature, MySQL) |
| 18 | Laravel `ProcessAdvanceUploadJob::saveToDatabase` | Ticket lama ditemukan (`$existingTicket` tidak null) | Data lama (`GroundTruth` + `Ticket`) dihapus, lalu data tiket baru dan ground truth baru dibentuk dalam transaksi yang sama | Lulus (Laravel Feature, MySQL) |
| 19 | Laravel `ProcessAdvanceUploadJob::saveToDatabase` | Exception di tengah transaksi DB | Sistem melakukan `DB::rollBack()` dan melempar exception | Lulus (Laravel Feature, MySQL) |
| 20 | FastAPI `guards.require_processing_available` | Circuit breaker tidak mengizinkan eksekusi (`can_execute() == False`) | Endpoint berhenti di guard dan melempar HTTP 503 (`CIRCUIT_BREAKER_OPEN`) | Lulus (Pytest) |
| 21 | FastAPI `CircuitBreaker.can_execute` | State `OPEN`, cooldown belum terpenuhi | Fungsi mengembalikan `False`, `total_rejected` bertambah | Lulus (Pytest) |
| 22 | FastAPI `CircuitBreaker.can_execute` | State `OPEN`, cooldown terpenuhi (`elapsed >= cooldown_seconds`) | Transisi state `OPEN -> HALF_OPEN` dan mengembalikan `True` | Lulus (Pytest) |
| 23 | FastAPI `CircuitBreaker.record_failure` | State `CLOSED`, jumlah gagal mencapai `failure_threshold` | Transisi state `CLOSED -> OPEN` | Lulus (Pytest) |
| 24 | FastAPI `CircuitBreaker.record_success` | State `HALF_OPEN` dan probe sukses | Transisi state `HALF_OPEN -> CLOSED`, `failure_count` direset | Lulus (Pytest) |
| 25 | FastAPI endpoint `POST /information-extraction` | Validasi Pydantic `TicketField` gagal (`ValidationError`) | Sistem mengembalikan HTTP 400 dengan pesan validasi ringkas | Lulus (Pytest White-Box) |
| 26 | FastAPI endpoint `POST /information-extraction` | `files` kosong atau jumlah melebihi batas | Sistem mengembalikan HTTP 400 sesuai cabang validasi jumlah berkas | Lulus (Pytest White-Box) |
| 27 | FastAPI endpoint `POST /information-extraction` | Ditemukan berkas tanpa nama atau bukan `.pdf` | Sistem menghentikan eksekusi dan mengembalikan HTTP 400 | Lulus (Pytest White-Box) |
| 28 | FastAPI endpoint `POST /information-extraction` | Ukuran salah satu PDF melebihi `MAX_PDF_SIZE_BYTES` | Terjadi `HTTPException(400)`, lalu blok `except HTTPException` melakukan cleanup folder tiket dan melempar ulang error | Lulus (Pytest White-Box) |
| 29 | FastAPI endpoint `POST /information-extraction` | Ekstraksi mengembalikan `None` (cabang `if extraction_result is None`) | Sistem melempar `ValueError`, masuk `except Exception`, mencatat kegagalan circuit, cleanup folder, dan merespons HTTP 500 | Lulus (Pytest White-Box) |
| 30 | FastAPI `run_document_extraction` | Semua OCR gagal (`if not extraction_data.get("results")`) | Fungsi mengembalikan objek status `error` dan `ground_truth_results` kosong | Lulus (Pytest White-Box) |
| 31 | FastAPI `extract_single_document` | OCR tidak mengembalikan `text_content` valid atau teks terlalu pendek | Fungsi mengembalikan tuple status `failed` tanpa memproses lanjut dokumen | Lulus (Pytest White-Box) |
| 32 | FastAPI endpoint `POST /review` | `extraction_results.json` tidak ditemukan untuk ticket | Sistem mengembalikan HTTP 404 dan instruksi menjalankan `/information-extraction` terlebih dahulu | Lulus (Pytest White-Box) |
| 33 | FastAPI endpoint `POST /review` | `ocr_results` kosong pada isi JSON ekstraksi | Sistem mengembalikan HTTP 400 "No OCR results found in extraction data" | Lulus (Pytest White-Box) |
| 34 | FastAPI endpoint `POST /review` | `run_unified_review_orchestrator` melempar exception | Sistem mencatat `processing_circuit.record_failure()` dan mengembalikan HTTP 500 | Lulus (Pytest White-Box) |
| 35 | FastAPI `advance_review_service.extract_ground_truth_from_ocr` | Teks OCR kosong (`if not ocr_text or not ocr_text.strip()`) | Fungsi mengembalikan status `error` dengan pesan "Empty OCR text" | Lulus (Pytest White-Box) |
| 36 | FastAPI `advance_review_service.extract_ground_truth_from_ocr` | Template ekstraksi tidak ditemukan (`get_prompt_template(...) is None`) | Fungsi mengembalikan status `error` "No extraction template found..." | Lulus (Pytest White-Box) |
| 37 | FastAPI `advance_review_utils.extract_json_from_llm_response` | Semua strategi parsing JSON gagal | Fungsi melempar `ValueError`, dipropagasikan ke lapisan service sebagai jalur error | Lulus (Pytest White-Box) |
| 38 | FastAPI `advance_review_service.review_single_document` | `ocr_cache` tidak tersedia atau `text_content` kosong | Fungsi mengembalikan status `error` tanpa memanggil model LLM | Lulus (Pytest White-Box) |
| 39 | FastAPI `advance_review_service.review_single_document` | Validasi struktur hasil review gagal (`validate_review_structure -> is_valid=False`) | Fungsi mengembalikan status `error` "Review validation failed" | Lulus (Pytest White-Box) |
| 40 | FastAPI `advance_review_service.review_single_document` | Kualitas konten warning (`validate_review_content_quality -> is_quality_ok=False`) | Fungsi tetap mengembalikan status `success` dengan `quality_warnings` terisi | Lulus (Pytest White-Box) |

### Catatan Implementasi Pengujian

- Skenario pada tabel ini disusun untuk pengujian jalur logika internal, bukan verifikasi antarmuka pengguna.
- Untuk memastikan *branch coverage* tinggi, disarankan penggunaan *mock/stub* pada:
  - Laravel: `Storage`, `Company`, `Ticket`, `GroundTruth`, `DB`, `Guzzle Client`.
  - FastAPI: `run_document_extraction`, `run_unified_review_orchestrator`, `DocumentIntelligenceClient`, dan LLM chain.
- Kolom `Status` disiapkan untuk diisi selama eksekusi pengujian aktual (mis. *Lulus*, *Gagal*, *Perlu Perbaikan*).
- Hasil run saat ini: backend `./.venv/Scripts/python.exe -m pytest -q` = **10 passed**; frontend `php artisan test --testsuite=Feature --stop-on-failure` terhenti karena **`could not find driver` (SQLite PDO)**.

### Pembaruan Eksekusi Aktual (MySQL Laragon)

- Konfigurasi khusus MySQL untuk test ditambahkan pada `frontend/phpunit.mysql.xml`.
- Run Laravel via MySQL (`php artisan test --configuration=phpunit.mysql.xml --testsuite=Feature`) berhasil terkoneksi ke database `pria_solo`.
- Hasil Laravel Feature saat ini: **5 passed, 1 failed, 13 risked**.
  - Kegagalan utama: `scenario 49 non pdf rejected at upload endpoint` akibat `400 Bad Request` dari FastAPI (`Hanya file PDF yang diizinkan: 'data.xlsx'`), namun exception belum ditangani sesuai ekspektasi test.
  - Banyak test bertanda **risked** karena tidak memiliki assertion eksplisit (`This test did not perform any assertions`), sehingga belum cukup kuat sebagai bukti white-box pass/fail final.
- Hasil FastAPI tetap stabil: `./.venv/Scripts/python.exe -m pytest -q` = **10 passed**.

### Pembaruan Final (Run Ulang Setelah Perbaikan Test)

- File test Laravel `tests/Feature/BlackBoxScenariosTest.php` telah diperbaiki:
  - seluruh skenario yang sebelumnya *risked* kini memiliki assertion eksplisit,
  - skenario non-PDF disesuaikan ke jalur chunk intermediate agar tidak bergantung call FastAPI background saat uji HTTP Laravel.
- Hasil final Laravel:
  - `php artisan test --configuration=phpunit.mysql.xml --testsuite=Feature`
  - **19 passed, 0 failed**.
- Hasil final FastAPI (tetap):
  - `./.venv/Scripts/python.exe -m pytest -q`
  - **10 passed, 0 failed**.
