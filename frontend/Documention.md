# Dokumentasi AI Document Reviewer

## Daftar Isi
1. [Pengenalan](#pengenalan)
2. [Arsitektur MVC](#arsitektur-mvc)
3. [Route dan Flow](#route-dan-flow)
4. [Cara Menggunakan](#cara-menggunakan)
5. [Struktur Database](#struktur-database)
6. [Troubleshooting & Perbaikan](#troubleshooting--perbaikan)

---

## Pengenalan

**AI Document Reviewer** adalah sistem otomatis untuk melakukan validasi dan review dokumen menggunakan teknologi AI (Artificial Intelligence). Fitur ini memungkinkan pengguna untuk:

- Mengunggah dokumen untuk diperiksa
- Membandingkan dokumen dengan template standard
- Mengidentifikasi kesalahan, typo, dan anomali dalam dokumen
- Menyimpan hasil validasi sebagai "Ground Truth" (data referensi)
- Menampilkan laporan detail tentang hasil review

Sistem ini terintegrasi dengan **Laravel OpenAdmin**, menyediakan antarmuka admin yang user-friendly untuk manajemen dokumen dan review.

---

## Arsitektur MVC

### M (Model)

Terdapat beberapa model utama yang bekerja sama dalam sistem AI Document Reviewer:

#### 1. **Ticket Model** (`app/Models/Ticket.php`)

Model ini merepresentasikan setiap dokumen/ticket yang akan di-review.

```php
namespace App\Models;

class Ticket extends Model
{
    const TYPE_PERPANJANGAN = 'Perpanjangan';
    const TYPE_NON_PERPANJANGAN = 'Non-Perpanjangan';

    protected $fillable = [
        'ticket_number',      // Format: 000000-AAA-000
        'company_id',
        'project_title',
        'type',
    ];

    // Relationship ke Ground Truth
    public function groundTruths(): HasMany
    {
        return $this->hasMany(GroundTruth::class);
    }

    // Relationship ke Company
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Relationship ke Typo Errors
    public function typoErrors(): HasMany
    {
        return $this->hasMany(TypoError::class);
    }
}
```

**Penting:** `ticket_number` harus mengikuti format regex: `^\d{6}-[A-Z]{3}-\d{3}$` (contoh: `123456-ABC-789`)

#### 2. **GroundTruth Model** (`app/Models/GroundTruth.php`)

Model ini menyimpan data referensi/validasi manual dari dokumen. Setiap Ground Truth dapat berisi data yang diekstrak dari berbagai tipe dokumen.

```php
namespace App\Models;

class GroundTruth extends Model
{
    protected $table = 'ground_truths';

    protected $fillable = [
        'ticket_id',
        'doc_type',           // Tipe dokumen (contoh: "Kontrak", "NPK", dll)
        'extracted_data',     // JSON: data yang diekstrak dari dokumen
    ];

    protected $casts = [
        'extracted_data' => 'array',  // Otomatis cast ke array
    ];

    // Relationship ke Ticket
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    // Relationship ke Advance Review Results
    public function advanceReviewResults(): HasMany
    {
        return $this->hasMany(AdvanceReviewResult::class);
    }

    // Helper: Dapatkan review result untuk doc_type tertentu
    public function getAdvanceReviewForDocType(string $docType): ?AdvanceReviewResult
    {
        return $this->advanceReviewResults()
            ->where('doc_type', $docType)
            ->first();
    }
}
```

**Struktur `extracted_data` contoh:**
```json
{
    "_metadata": {
        "extraction_date": "2026-01-29",
        "document_type": "Kontrak"
    },
    "Kontrak": {
        "nomor_kontrak": "K-2026-001",
        "tanggal_kontrak": "2026-01-15",
        "nilai_kontrak": 1000000000
    },
    "NPK": {
        "nomor_npk": "NPK-2026-001",
        "nama_pihak": "PT Mitra ABC"
    }
}
```

#### 3. **AdvanceReviewResult Model** (`app/Models/AdvanceReviewResult.php`)

Model ini menyimpan hasil review/validasi yang dilakukan oleh AI terhadap Ground Truth.

```php
namespace App\Models;

class AdvanceReviewResult extends Model
{
    protected $table = 'advance_review_results';

    protected $fillable = [
        'ground_truth_id',
        'doc_type',           // Tipe dokumen yang di-review
        'status',             // 'pending', 'success', 'error'
        'error_message',      // Pesan error jika status = 'error'
        'review_data',        // JSON: hasil review dari AI
    ];

    protected $casts = [
        'review_data' => 'array',
    ];

    // Relationship ke Ground Truth
    public function groundTruth(): BelongsTo
    {
        return $this->belongsTo(GroundTruth::class);
    }

    // Helper methods untuk status checking
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
```

**Struktur `review_data` contoh (hasil review untuk doc_type "Kontrak"):**
```json
{
    "issue_1": {
        "label": "Nomor Kontrak Invalid",
        "description": "Format nomor kontrak tidak sesuai standard",
        "is_valid": true,
        "severity": "warning"
    },
    "issue_2": {
        "label": "Tanggal Kontrak Tidak Valid",
        "description": "Tanggal kontrak berada di masa depan",
        "is_valid": true,
        "severity": "error"
    },
    "errors_count": 2
}
```

#### 4. **TicketNote Model** (`app/Models/TicketNote.php`)

Model untuk menyimpan catatan/komentar terhadap sebuah ticket.

#### 5. **TypoError Model** (`app/Models/TypoError.php`)

Model untuk menyimpan data kesalahan typo yang ditemukan dalam dokumen.

---

### V (View)

View-view untuk AI Document Reviewer tersimpan di `resources/views/advance-reviews/`:

#### File-file Utama:

1. **`document-review-main-page.blade.php`** - Halaman utama validasi dokumen
2. **`history-page.blade.php`** - Halaman riwayat review
3. **`templates/validate-ground-truth.blade.php`** - Form untuk input Ground Truth
4. **`partials/`** - Komponen-komponen reusable UI

---

### C (Controller)

Controller-controller dalam sistem AI Document Reviewer:

#### 1. **AiAdvanceReviewController** (`app/Admin/Controllers/AiAdvanceReviewController.php`)

Controller utama yang menampilkan halaman validasi dokumen.

```php
namespace App\Admin\Controllers;

class AiAdvanceReviewController extends Controller
{
    /**
     * Route: GET /validasi-dokumen
     * Menampilkan halaman utama validasi dokumen
     */
    public function index()
    {
        return view('advance-reviews.document-review-main-page');
    }
}
```

#### 2. **AdvanceUploadController** (`app/Admin/Controllers/AiControllers/AdvanceUploadController.php`)

Menangani upload file dokumen untuk diproses AI.

```php
namespace App\Admin\Controllers\AiControllers;

class AdvanceUploadController extends Controller
{
    /**
     * Route: POST /api/advance-upload
     * Upload file dan dispatch ke FastAPI untuk processing
     */
    public function upload(Request $request)
    {
        $files = $request->file('files', []);
        $ticketNumber = $request->input('ticket');
        $companyId = $request->input('company_id');
        $namaMitra = $request->input('nama_mitra');

        // Validasi
        if (!$ticketNumber || !$companyId) {
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        // STEP 1: Simpan file ke storage
        $disk = Storage::disk('public');
        foreach ($files as $file) {
            $storagePath = "advance-review/{$ticketNumber}/{$file->getClientOriginalName()}";
            $disk->putFileAs(
                "advance-review/{$ticketNumber}",
                $file,
                $file->getClientOriginalName()
            );
        }

        // STEP 2: Dispatch ke FastAPI (asynchronous)
        ProcessAdvanceUploadJob::dispatch($ticketNumber, $files, $companyId);

        return response()->json(['success' => true]);
    }
}
```

#### 3. **GroundTruthController** (`app/Admin/Controllers/AiControllers/GroundTruthController.php`)

Menangani validasi Ground Truth dan penyimpanan data referensi.

```php
namespace App\Admin\Controllers\AiControllers;

class GroundTruthController extends Controller
{
    /**
     * Route: GET /validate-ground-truth/{ticket_number}
     * Menampilkan form untuk input/validasi Ground Truth
     */
    public function show($ticket_number)
    {
        $ticket = Ticket::where('ticket_number', $ticket_number)
            ->with('groundTruths')
            ->firstOrFail();

        $groundTruthData = $this->getGroundTruthData($ticket);
        $availableDocuments = $this->getAvailableDocuments($ticket_number);

        return view('advance-reviews.templates.validate-ground-truth', [
            'ticketNumber' => $ticket_number,
            'groundTruthData' => $groundTruthData,
            'availableDocuments' => $availableDocuments
        ]);
    }

    /**
     * Route: POST /validate-ground-truth/{ticket_number}/save
     * Menyimpan data Ground Truth
     */
    public function save(Request $request, $ticket_number)
    {
        $validated = $request->validate([
            'doc_type' => 'required|string',
            'extracted_data' => 'required|json'
        ]);

        $ticket = Ticket::where('ticket_number', $ticket_number)->firstOrFail();
        
        GroundTruth::updateOrCreate(
            ['ticket_id' => $ticket->id, 'doc_type' => $validated['doc_type']],
            ['extracted_data' => json_decode($validated['extracted_data'], true)]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Route: POST /validate-ground-truth/{ticket_number}/complete
     * Menyelesaikan validasi dan trigger review
     */
    public function complete(Request $request, $ticket_number)
    {
        // Implement logic untuk menyelesaikan dan trigger AI review
    }
}
```

#### 4. **ReviewSubmissionController** (`app/Admin/Controllers/AiControllers/ReviewSubmissionController.php`)

Menangani penyimpanan hasil review dan submission ke sistem.

```php
namespace App\Admin\Controllers\AiControllers;

class ReviewSubmissionController extends Controller
{
    /**
     * Route: POST /api/review/submit
     * Submit Ground Truth untuk di-review oleh AI
     * 
     * Kriteria:
     * - Trigger AI review terhadap uploaded documents
     * - Simpan hasil ke AdvanceReviewResult
     * - Status: pending → success/error
     */
    public function submit(Request $request)
    {
        set_time_limit(0);  // Unlimited execution time
        
        $validated = $request->validate([
            'ticket' => 'required|string|regex:/^\d{6}-[A-Z]{3}-\d{3}$/',
            'ground_truth' => 'required|string'
        ]);

        $ticketNumber = $validated['ticket'];
        $groundTruthJson = $validated['ground_truth'];
        $groundTruthData = json_decode($groundTruthJson, true);

        // Cari ticket dan ground truth
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();
        $groundTruth = GroundTruth::where('ticket_id', $ticket->id)
            ->where('doc_type', 'Ground Truth')
            ->firstOrFail();

        // Trigger review via FastAPI
        $response = Http::post(env('FASTAPI_URL') . '/review', [
            'ticket_number' => $ticketNumber,
            'ground_truth' => $groundTruthData
        ]);

        // Simpan hasil ke database
        foreach ($response['results'] as $docType => $result) {
            AdvanceReviewResult::create([
                'ground_truth_id' => $groundTruth->id,
                'doc_type' => $docType,
                'status' => $result['status'],
                'review_data' => $result['data']
            ]);
        }

        return response()->json(['success' => true]);
    }
}
```

#### 5. **AdvanceResultController** (`app/Admin/Controllers/AiControllers/AdvanceResultController.php`)

Menampilkan hasil review dengan analisis mendalam.

```php
namespace App\Admin\Controllers\AiControllers;

class AdvanceResultController extends Controller
{
    /**
     * Route: GET /advance-result/{ticketNumber}/{docType}
     * Menampilkan hasil advance review untuk doc_type tertentu
     */
    public function show($ticketNumber, $docType)
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();
        $groundTruth = $ticket->groundTruth;
        
        $result = AdvanceReviewResult::where('ground_truth_id', $groundTruth->id)
            ->where('doc_type', $docType)
            ->firstOrFail();

        $issues = $this->extractValidIssues($result->review_data);

        return view('advance-reviews.show-result', [
            'ticketNumber' => $ticketNumber,
            'docType' => $docType,
            'result' => $result,
            'issues' => $issues
        ]);
    }

    /**
     * Route: GET /api/advance-result/{ticketNumber}/{docType}/data
     * API endpoint untuk fetch data advance result
     */
    public function getAdvanceResultData($ticketNumber, $docType)
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();
        $result = AdvanceReviewResult::where('ground_truth_id', $ticket->groundTruth->id)
            ->where('doc_type', $docType)
            ->firstOrFail();

        return response()->json($result);
    }
}
```

#### 6. **AdvanceReviewOverviewController** (`app/Admin/Controllers/AiControllers/AdvanceReviewOverviewController.php`)

Menampilkan overview dari semua review untuk sebuah ticket.

```php
namespace App\Admin\Controllers\AiControllers;

class AdvanceReviewOverviewController extends Controller
{
    /**
     * Route: GET /tickets/{ticketNumber}/advance-reviews
     * Menampilkan semua review results untuk sebuah ticket
     */
    public function showReviews($ticketNumber)
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();
        $reviews = $ticket->groundTruth->advanceReviewResults;

        return view('advance-reviews.overview', [
            'ticket' => $ticket,
            'reviews' => $reviews
        ]);
    }
}
```

#### 7. **RiwayatController** (`app/Admin/Controllers/AiControllers/RiwayatController.php`)

Menampilkan riwayat/history semua review yang pernah dilakukan.

```php
namespace App\Admin\Controllers\AiControllers;

class RiwayatController extends Controller
{
    /**
     * Route: GET /riwayat-review
     * Menampilkan history semua reviews
     */
    public function index()
    {
        $reviews = AdvanceReviewResult::with('groundTruth.ticket')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('advance-reviews.history-page', [
            'reviews' => $reviews
        ]);
    }

    /**
     * Route: DELETE /riwayat-review/{id}
     * Hapus history review
     */
    public function destroy($id)
    {
        AdvanceReviewResult::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
```

---

## Route dan Flow

### Route Structure

```
PREFIX: /admin (dari config('admin.route.prefix'))
NAMESPACE: App\Admin\Controllers

BASE ROUTES:
/admin/validasi-dokumen                     → AiAdvanceReviewController@index
/admin/riwayat-review                       → RiwayatController@index
```

### Flow Diagram: Proses Review Dokumen

```
┌─────────────────────────────────────────────────────────────────┐
│                    USER UPLOAD DOKUMEN                          │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
        ┌─────────────────────────────────────────┐
        │  POST /api/advance-upload               │
        │  AdvanceUploadController@upload         │
        └────────────┬────────────────────────────┘
                     │
        ┌────────────▼──────────────────────────┐
        │ 1. Validasi file & ticket number      │
        │ 2. Simpan file ke storage             │
        │ 3. Dispatch ProcessAdvanceUploadJob   │
        └────────────┬──────────────────────────┘
                     │
                     ▼
        ┌────────────────────────────────────────┐
        │  FastAPI Processing (Async)            │
        │  - Extract data dari dokumen           │
        │  - Simpan ke GroundTruth.extracted_data
        └────────────┬───────────────────────────┘
                     │
                     ▼
        ┌────────────────────────────────────────────┐
        │  USER VIEW EXTRACTED DATA & VALIDATE       │
        │  GET /validate-ground-truth/{ticket}       │
        │  GroundTruthController@show                │
        └────────────┬───────────────────────────────┘
                     │
        ┌────────────▼──────────────────────────┐
        │ Display form dengan extracted data    │
        │ User bisa edit/validate manual        │
        └────────────┬──────────────────────────┘
                     │
                     ▼
        ┌────────────────────────────────────────┐
        │  POST /api/review/submit               │
        │  ReviewSubmissionController@submit     │
        └────────────┬───────────────────────────┘
                     │
        ┌────────────▼──────────────────────────────────┐
        │ 1. Validate Ground Truth JSON format         │
        │ 2. Trigger AI Review via FastAPI             │
        │ 3. Save results ke AdvanceReviewResult       │
        │ 4. Generate PDF reports                      │
        └────────────┬───────────────────────────────────┘
                     │
                     ▼
        ┌────────────────────────────────────────────┐
        │  VIEW REVIEW RESULTS                       │
        │  GET /advance-result/{ticket}/{docType}    │
        │  AdvanceResultController@show              │
        └────────────┬───────────────────────────────┘
                     │
        ┌────────────▼──────────────────────────┐
        │ Display issues & validation results   │
        │ Generate PDF dari review data         │
        └────────────┬──────────────────────────┘
                     │
                     ▼
            ┌──────────────────────┐
            │  REVIEW COMPLETED    │
            └──────────────────────┘
```

### Daftar Semua Route

| Method | Route | Controller | Fungsi |
|--------|-------|-----------|--------|
| GET | `/validasi-dokumen` | AiAdvanceReviewController@index | Halaman utama validasi dokumen |
| GET | `/riwayat-review` | RiwayatController@index | Lihat history review |
| DELETE | `/riwayat-review/{id}` | RiwayatController@destroy | Hapus history review |
| DELETE | `/riwayat-review/review/{id}` | RiwayatController@destroyReview | Hapus review tertentu |
| GET | `/tickets/{ticketNumber}/advance-reviews` | AdvanceReviewOverviewController@showReviews | Overview semua review untuk ticket |
| GET | `/api/tickets/{ticketNumber}/advance-reviews/data` | AdvanceReviewOverviewController@getOverviewData | API data overview |
| GET | `/validate-ground-truth/{ticket_number}` | GroundTruthController@show | Form validasi Ground Truth |
| POST | `/validate-ground-truth/{ticket_number}/save` | GroundTruthController@save | Simpan Ground Truth |
| POST | `/validate-ground-truth/{ticket_number}/complete` | GroundTruthController@complete | Selesaikan validasi |
| GET | `/pdf/ground-truth/{ticket_number}/{doc_type}/{filename}` | GroundTruthController@servePDF | Download PDF Ground Truth |
| GET | `/advance-result/{ticketNumber}/{docType}` | AdvanceResultController@show | Lihat hasil advance review |
| GET | `/api/advance-result/{ticketNumber}/{docType}/data` | AdvanceResultController@getAdvanceResultData | API data hasil review |
| GET | `/pdf/advance/{ticketNumber}/{docType}/{filename}` | AdvanceResultController@servePDF | Download PDF hasil review |
| POST | `/api/advance-upload` | AdvanceUploadController@upload | Upload file dokumen |
| POST | `/api/review/submit` | ReviewSubmissionController@submit | Submit review ke AI |
| GET | `/api/tickets/{ticketNumber}/notes` | TicketNoteController@getNotes | Dapatkan notes ticket |
| POST | `/api/tickets/{ticketNumber}/notes` | TicketNoteController@saveNotes | Simpan notes ticket |
| GET | `/api/companies` | ApiGatewayController@getAllCompanyNames | Dapatkan daftar perusahaan |
| GET | `/api/tickets/{ticketNumber}/pairing-documents/available` | PairingDocumentsController@getAvailableDocuments | Dapatkan dokumen untuk dibandingkan |
| GET | `/tickets/{ticketNumber}/pairing-documents/compare` | PairingDocumentsController@showComparison | Halaman perbandingan dokumen |
| GET | `/pdf/pairing/{ticketNumber}/{documentId}` | PairingDocumentsController@servePDF | Download PDF dokumen perbandingan |

---

## Cara Menggunakan

### 1. Upload Dokumen

**Endpoint:** `POST /api/advance-upload`

**Request Body:**
```json
{
    "files": [File, File, ...],
    "ticket": "123456-ABC-789",
    "company_id": 1,
    "nama_mitra": "PT Mitra ABC"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Files uploaded successfully"
}
```

**Proses di Backend:**
1. Validasi format ticket number (regex: `^\d{6}-[A-Z]{3}-\d{3}$`)
2. Simpan file ke folder: `storage/app/public/advance-review/{ticket_number}/`
3. Dispatch async job `ProcessAdvanceUploadJob` ke queue
4. FastAPI akan mengekstrak data dan simpan ke `GroundTruth.extracted_data`

### 2. View & Validate Ground Truth

**Endpoint:** `GET /validate-ground-truth/{ticket_number}`

**Browser:** Akses `/admin/validate-ground-truth/123456-ABC-789`

**Halaman akan menampilkan:**
- Form dengan field-field yang sudah diekstrak dari dokumen
- Tombol untuk edit/validasi setiap field
- Preview PDF dokumen original
- Tombol submit untuk trigger AI review

**Struktur Form:**
```html
<form id="ground-truth-form">
    <div class="doc-type-section">
        <h3>Kontrak</h3>
        <input name="Kontrak[nomor_kontrak]" value="K-2026-001" />
        <input name="Kontrak[tanggal_kontrak]" value="2026-01-15" />
        <!-- ... field lainnya ... -->
    </div>
    <div class="doc-type-section">
        <h3>NPK</h3>
        <input name="NPK[nomor_npk]" value="NPK-2026-001" />
        <!-- ... field lainnya ... -->
    </div>
    <button type="submit">Submit untuk Review</button>
</form>
```

### 3. Submit Ground Truth untuk Review

**Endpoint:** `POST /api/review/submit`

**Request Body:**
```json
{
    "ticket": "123456-ABC-789",
    "ground_truth": "{\"Kontrak\": {...}, \"NPK\": {...}}"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Review submitted successfully",
    "review_id": 123
}
```

**Proses di Backend:**
1. Validasi JSON format
2. Trigger FastAPI endpoint `/review` dengan Ground Truth data
3. FastAPI melakukan analisis & mendeteksi issues
4. Simpan hasil ke table `advance_review_results`
5. Set status menjadi `success` atau `error`

### 4. View Review Results

**Endpoint:** `GET /advance-result/{ticketNumber}/{docType}`

**Browser:** Akses `/admin/advance-result/123456-ABC-789/Kontrak`

**Halaman akan menampilkan:**
- List semua issues yang ditemukan
- Severity level (error, warning, info)
- Deskripsi detail setiap issue
- Rekomendasi perbaikan
- Tombol untuk download PDF laporan

**Struktur Issues:**
```json
{
    "issue_1": {
        "label": "Nomor Kontrak Invalid",
        "description": "Format nomor kontrak tidak sesuai pattern K-YYYY-XXX",
        "is_valid": true,
        "severity": "error"
    },
    "issue_2": {
        "label": "Tanggal Kontrak Futuristik",
        "description": "Tanggal kontrak berada lebih dari 6 bulan di masa depan",
        "is_valid": true,
        "severity": "warning"
    }
}
```

### 5. View Review History

**Endpoint:** `GET /riwayat-review`

**Browser:** Akses `/admin/riwayat-review`

**Halaman akan menampilkan:**
- Tabel daftar semua reviews
- Filter berdasarkan ticket number, doc_type, status
- Sorting berdasarkan tanggal
- Tombol untuk lihat detail atau hapus

---

## Struktur Database

### Table: `tickets`
```sql
CREATE TABLE tickets (
    id BIGINT UNSIGNED PRIMARY KEY,
    ticket_number VARCHAR(255) UNIQUE NOT NULL,  -- Format: 123456-ABC-789
    company_id BIGINT UNSIGNED,
    project_title VARCHAR(255),
    type ENUM('Perpanjangan', 'Non-Perpanjangan'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
```

### Table: `ground_truths`
```sql
CREATE TABLE ground_truths (
    id BIGINT UNSIGNED PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    doc_type VARCHAR(255),           -- e.g., "Kontrak", "NPK", "Ground Truth"
    extracted_data LONGTEXT,         -- JSON dengan struktur extracted data
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);

-- Index untuk query cepat
CREATE INDEX idx_ground_truths_ticket_id ON ground_truths(ticket_id);
CREATE INDEX idx_ground_truths_doc_type ON ground_truths(doc_type);
CREATE UNIQUE INDEX idx_ground_truths_ticket_doc_type ON ground_truths(ticket_id, doc_type);
```

### Table: `advance_review_results`
```sql
CREATE TABLE advance_review_results (
    id BIGINT UNSIGNED PRIMARY KEY,
    ground_truth_id BIGINT UNSIGNED NOT NULL,
    doc_type VARCHAR(255),           -- Dokumen tipe apa yang di-review
    status ENUM('pending', 'success', 'error') DEFAULT 'pending',
    error_message TEXT,              -- Pesan error jika ada
    review_data LONGTEXT,            -- JSON hasil review dari AI
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (ground_truth_id) REFERENCES ground_truths(id) ON DELETE CASCADE
);

-- Index untuk query cepat
CREATE INDEX idx_advance_review_results_gt_id ON advance_review_results(ground_truth_id);
CREATE INDEX idx_advance_review_results_doc_type ON advance_review_results(doc_type);
CREATE INDEX idx_advance_review_results_status ON advance_review_results(status);
```

### Table: `ticket_notes`
```sql
CREATE TABLE ticket_notes (
    id BIGINT UNSIGNED PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL UNIQUE,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);
```

### Table: `typo_errors`
```sql
CREATE TABLE typo_errors (
    id BIGINT UNSIGNED PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    error_type VARCHAR(255),         -- e.g., "spelling", "grammar"
    error_text VARCHAR(255),
    suggested_text VARCHAR(255),
    context TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);
```

---

## Troubleshooting & Perbaikan

### Issue 1: "Ticket tidak ditemukan"

**Gejala:**
```
Error 404: Ticket tidak ditemukan
```

**Penyebab:**
- Ticket number tidak ada di database
- Format ticket number salah

**Solusi:**
```php
// Check di database
$ticket = Ticket::where('ticket_number', '123456-ABC-789')->first();
if (!$ticket) {
    // Insert ticket baru
    Ticket::create([
        'ticket_number' => '123456-ABC-789',
        'company_id' => 1,
        'project_title' => 'Project ABC',
        'type' => 'Perpanjangan'
    ]);
}
```

**Validasi format ticket:**
```php
$ticketNumber = '123456-ABC-789';

// Validasi dengan regex
if (!preg_match('/^\d{6}-[A-Z]{3}-\d{3}$/', $ticketNumber)) {
    throw new \Exception('Format ticket number tidak valid');
}
```

---

### Issue 2: "Invalid JSON format"

**Gejala:**
```json
{
    "success": false,
    "message": "Invalid JSON format: Syntax error"
}
```

**Penyebab:**
- JSON dari Ground Truth tidak valid
- String quote tidak tepat
- Field dengan special character tidak di-escape

**Solusi:**
```php
// Sebelum submit, validasi JSON
$groundTruthJson = $request->input('ground_truth');
$decoded = json_decode($groundTruthJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Error: " . json_last_error_msg();
    // Gunakan JSON validator online: jsonlint.com
}

// Cara yang benar saat convert dari form
$formData = [
    'Kontrak' => [
        'nomor_kontrak' => 'K-2026-001',
        'tanggal_kontrak' => '2026-01-15'
    ]
];

$jsonString = json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
// JSON_UNESCAPED_UNICODE untuk support karakter non-ASCII
```

---

### Issue 3: "Ground truth not found for this ticket"

**Gejala:**
```json
{
    "success": false,
    "message": "Ground truth not found for this ticket"
}
```

**Penyebab:**
- Ground Truth belum dibuat untuk ticket ini
- `doc_type` tidak sesuai ekspektasi

**Solusi:**
```php
// Cek existing ground truths
$ticket = Ticket::find(1);
foreach ($ticket->groundTruths as $gt) {
    echo $gt->doc_type . " => " . json_encode($gt->extracted_data);
}

// Jika kosong, buat Ground Truth baru dulu
GroundTruth::create([
    'ticket_id' => $ticket->id,
    'doc_type' => 'Ground Truth',
    'extracted_data' => [
        'Kontrak' => [...],
        'NPK' => [...]
    ]
]);

// Atau update yang existing
$groundTruth->update([
    'extracted_data' => [
        'Kontrak' => [...],
        'NPK' => [...]
    ]
]);
```

---

### Issue 4: "FastAPI Connection Error"

**Gejala:**
```
Error: Could not connect to FastAPI server at http://fastapi-url:8000
```

**Penyebab:**
- FastAPI service tidak running
- URL FastAPI salah di `.env`
- Firewall/Network issue

**Solusi:**
```bash
# Check FastAPI is running
curl -X GET http://localhost:8000/docs

# Check Laravel config
php artisan tinker
>>> env('FASTAPI_URL')
// Output: http://fastapi-service:8000

# Update .env jika perlu
# FASTAPI_URL=http://localhost:8000
```

```php
// Di ReviewSubmissionController.php, debug log
Log::info('Calling FastAPI', [
    'url' => env('FASTAPI_URL') . '/review',
    'ticket' => $ticketNumber
]);

try {
    $response = Http::timeout(300)->post(
        env('FASTAPI_URL') . '/review',
        ['ticket_number' => $ticketNumber]
    );
} catch (\Illuminate\Http\Client\ConnectionException $e) {
    Log::error('FastAPI Connection Error', [
        'message' => $e->getMessage(),
        'url' => env('FASTAPI_URL')
    ]);
    return response()->json(['error' => 'FastAPI unavailable'], 503);
}
```

---

### Issue 5: "File upload failed"

**Gejala:**
```json
{
    "error": "No files received"
}
```

**Penyebab:**
- Request multipart/form-data tidak tepat
- File size terlalu besar
- Extension file tidak diizinkan

**Solusi:**
```php
// Di AdvanceUploadController@upload
// Tambahkan validation
$request->validate([
    'files' => 'required|array',
    'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240', // Max 10MB
    'ticket' => 'required|string',
    'company_id' => 'required|integer'
]);

// Debug
Log::info('Files in request', [
    'has_files' => $request->hasFile('files'),
    'files_count' => count($request->file('files', [])),
    'all_input' => $request->input()
]);

// Client-side (JavaScript)
// Pastikan FormData setup dengan benar
const formData = new FormData();
formData.append('ticket', ticketNumber);
formData.append('company_id', companyId);

files.forEach((file, index) => {
    formData.append(`files[${index}]`, file);
});

fetch('/admin/api/advance-upload', {
    method: 'POST',
    body: formData
    // JANGAN set Content-Type, browser akan set otomatis
});
```

---

### Issue 6: "Timeout ketika submit review"

**Gejala:**
```
Error 504: Gateway Timeout
```

**Penyebab:**
- Proses review terlalu lama
- FastAPI processing lambat
- Script timeout di FastAPI

**Solusi:**
```php
// Di ReviewSubmissionController@submit
// Set unlimited execution time
set_time_limit(0);
ini_set('max_execution_time', '0');

// Gunakan HTTP timeout yang lebih panjang
$response = Http::timeout(300)  // 5 menit timeout
    ->post(env('FASTAPI_URL') . '/review', $data);

// Atau gunakan job queue untuk async processing
SubmitReviewJob::dispatch($ticket, $groundTruthData)
    ->onConnection('database')
    ->delay(now());  // Jangan delay, execute immediately

// Monitor queue
// php artisan queue:work database --tries=3
```

---

### Issue 7: "Ambiguous column error pada groundTruth relationship"

**Gejala:**
```
QueryException: SQLSTATE[23000]: Column 'id' in where clause is ambiguous
```

**Penyebab:**
- Penggunaan `oldestOfMany()` yang ambiguous
- Join query tanpa prefix tabel

**Solusi (SUDAH FIXED):**
```php
// SEBELUMNYA (SALAH):
public function groundTruth(): HasOne
{
    return $this->hasOne(GroundTruth::class)->oldestOfMany();
}

// SESUDAHNYA (BENAR):
public function groundTruth(): HasOne
{
    return $this->hasOne(GroundTruth::class)->oldest('ground_truths.id');
}
```

---

### Issue 8: "PDF tidak bisa di-download"

**Gejala:**
```
Error 404: File not found
```

**Penyebab:**
- PDF belum di-generate
- Path file salah
- Storage permission issue

**Solusi:**
```php
// Pastikan PDF di-generate ketika review selesai
// Di ReviewSubmissionController@submit

// Setelah simpan AdvanceReviewResult
$pdf = $this->generateReviewPDF($result);
$pdfPath = "pdfs/advance-review/{$ticketNumber}/{$docType}.pdf";
Storage::disk('public')->put($pdfPath, $pdf);

// Check file exists
if (Storage::disk('public')->exists($pdfPath)) {
    Log::info('PDF saved successfully', ['path' => $pdfPath]);
}

// Di AdvanceResultController@servePDF
public function servePDF($ticketNumber, $docType, $filename)
{
    $pdfPath = "pdfs/advance-review/{$ticketNumber}/{$filename}";
    
    if (!Storage::disk('public')->exists($pdfPath)) {
        abort(404, 'PDF file not found');
    }
    
    return response()->file(
        Storage::disk('public')->path($pdfPath),
        ['Content-Disposition' => 'attachment; filename="' . $filename . '"']
    );
}
```

---

### Issue 9: "Extracted data tidak muncul di form"

**Gejala:**
- Form validasi Ground Truth kosong
- Tidak ada field yang pre-filled

**Penyebab:**
- FastAPI belum selesai extract data
- File upload belum selesai diproses
- `extracted_data` di database kosong

**Solusi:**
```php
// Di GroundTruthController@show
// Debug extracted data
$groundTruth = $ticket->groundTruth;
Log::debug('Ground truth data', [
    'id' => $groundTruth->id,
    'doc_type' => $groundTruth->doc_type,
    'extracted_data' => $groundTruth->extracted_data,
    'data_keys' => array_keys($groundTruth->extracted_data ?? [])
]);

// Jika kosong, tunggu proses FastAPI
// Atau trigger ulang ProcessAdvanceUploadJob
ProcessAdvanceUploadJob::dispatch($ticketNumber, $files, $companyId);

// Check job queue status
// php artisan queue:failed
// php artisan queue:retry {id}
```

---

### Issue 10: "Relations tidak loading di view"

**Gejala:**
```
PropertyNotFoundException: Call to undefined property on App\Models\Ticket
```

**Penyebab:**
- Relationship belum di-load (missing `with()`)
- Relationship method tidak ada di Model

**Solusi:**
```php
// Gunakan eager loading untuk menghindari N+1 query
$ticket = Ticket::where('ticket_number', $ticketNumber)
    ->with([
        'company',
        'groundTruths',
        'groundTruth.advanceReviewResults',  // Jika perlu
        'typoErrors',
        'notes'
    ])
    ->firstOrFail();

// Di view, akses dengan aman
@isset($ticket->groundTruth)
    <p>{{ $ticket->groundTruth->doc_type }}</p>
@else
    <p>Ground Truth belum tersedia</p>
@endisset

// Atau gunakan optional()
{{ optional($ticket->groundTruth)->doc_type }}
```

---

## Best Practices

### 1. Validasi Input Selalu
```php
$validated = $request->validate([
    'ticket' => 'required|string|regex:/^\d{6}-[A-Z]{3}-\d{3}$/',
    'company_id' => 'required|exists:companies,id',
    'files' => 'required|array',
    'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240'
]);
```

### 2. Log Semua Operasi Penting
```php
Log::info('Review submitted', [
    'ticket' => $ticketNumber,
    'doc_types' => array_keys($groundTruthData),
    'timestamp' => now()
]);

Log::error('Review failed', [
    'ticket' => $ticketNumber,
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString()
]);
```

### 3. Gunakan Database Transactions
```php
DB::transaction(function () {
    $groundTruth = GroundTruth::create([...]);
    
    foreach ($docTypes as $docType => $data) {
        AdvanceReviewResult::create([
            'ground_truth_id' => $groundTruth->id,
            'doc_type' => $docType,
            'review_data' => $data
        ]);
    }
});
```

### 4. Handle Exception Gracefully
```php
try {
    $response = Http::timeout(300)->post(env('FASTAPI_URL') . '/review', $data);
} catch (\Illuminate\Http\Client\ConnectionException $e) {
    Log::error('FastAPI unavailable', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Service temporarily unavailable'], 503);
} catch (\Exception $e) {
    Log::error('Unexpected error', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'An error occurred'], 500);
}
```

### 5. Gunakan Eager Loading untuk Performance
```php
// BAIK
$reviews = AdvanceReviewResult::with('groundTruth.ticket')
    ->paginate(20);

// BURUK - N+1 Query
$reviews = AdvanceReviewResult::paginate(20);
foreach ($reviews as $review) {
    $ticket = $review->groundTruth->ticket;  // Query lagi untuk setiap row!
}
```

---

## Kesimpulan

Sistem **AI Document Reviewer** adalah solusi komprehensif untuk validasi dokumen otomatis dengan teknologi AI. Dengan memahami struktur MVC, flow proses, dan common issues, Anda dapat dengan mudah:

- ✅ Implement fitur baru
- ✅ Debug dan fix issues
- ✅ Optimize performance
- ✅ Maintain codebase

Untuk pertanyaan lebih lanjut, refer ke file-file source code dan dokumentasi Laravel OpenAdmin.

