<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Element\TextRun;
use Carbon\Carbon;
use ZipArchive;
use App\Models\AutoDraft;
use App\Models\AutoDraftObl;
use App\Models\projects;

class AutoDraftingController extends AdminController
{
    // ==========================================
    // PAGES
    // ==========================================

    public function listDrafts(Content $content)
    {
        return $content->title('List Draft OBL')->body(view('auto-drafting.list'));
    }

    public function formDrafting(Content $content, $id_rso)
    {
        return $content->title('Auto Drafting OBL')->body(view('auto-drafting.form', ['id_rso' => $id_rso]));
    }

    /**
     * Create or get existing AutoDraft record and redirect to form.
     * Pre-populates draft data from the projects table if creating new.
     */
    public function initDraft($id_rso)
    {
        // Check if draft already exists
        $draft = AutoDraft::where('id_rso', $id_rso)->first();

        if (!$draft) {
            // Get project data to pre-populate the draft
            $project = projects::find($id_rso);

            // Create new draft with pre-filled data from project
            $draft = AutoDraft::create([
                'id_rso' => $id_rso,
                'judul_p1' => $project->p1_namaKontrak ?? null,
                'nomor_p1' => $project->p1_nomor ?? null,
                'tanggal_p1' => $project->p1_tanggal ?? null,
                'pelanggan' => $project->Customer ?? null,
                'status' => 'draft',
            ]);
        }

        // Redirect to the form page
        $adminPrefix = config('admin.route.prefix');
        return redirect("/{$adminPrefix}/rso/{$id_rso}/autodraft");
    }

    // ==========================================
    // AUTO DRAFT CRUD API
    // ==========================================

    /**
     * API: List all drafts
     */
    public function apiListDrafts()
    {
        try {
            $drafts = AutoDraft::with('obls')
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($draft) {
                    return [
                        'id' => $draft->id,
                        'id_rso' => $draft->id_rso,
                        'judul_p1' => $draft->judul_p1,
                        'pelanggan' => $draft->pelanggan,
                        'obl_count' => $draft->obls->count(),
                        'status' => $draft->status,
                        'updated_at' => $draft->updated_at->toIso8601String(),
                        'created_at' => $draft->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $drafts,
                'count' => $drafts->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error listing drafts: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar draft.',
            ], 500);
        }
    }

    /**
     * API: Get single draft by id_rso
     */
    public function apiGetDraft($id_rso)
    {
        try {
            $draft = AutoDraft::with('obls')->where('id_rso', $id_rso)->first();

            if (!$draft) {
                return response()->json([
                    'success' => true,
                    'exists' => false,
                    'data' => null,
                ]);
            }

            // Return in the format expected by the form (same as localStorage format)
            return response()->json([
                'success' => true,
                'exists' => true,
                'data' => $draft->toGeneratorFormat(),
                'timestamp' => $draft->updated_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting draft {$id_rso}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data draft.',
            ], 500);
        }
    }

    /**
     * API: Save/Update draft
     */
    public function apiSaveDraft(Request $request)
    {
        try {
            $data = $request->all();

            if (empty($data['general']['id_rso'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID RSO tidak boleh kosong.',
                ], 400);
            }

            $draft = AutoDraft::createFromFormData($data);

            Log::info("Draft saved successfully: " . $draft->id_rso);

            return response()->json([
                'success' => true,
                'message' => 'Draft berhasil disimpan.',
                'data' => [
                    'id' => $draft->id,
                    'id_rso' => $draft->id_rso,
                    'obl_count' => $draft->obls->count(),
                    'updated_at' => $draft->updated_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error saving draft: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan draft: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Delete draft by id_rso
     */
    public function apiDeleteDraft($id_rso)
    {
        try {
            $draft = AutoDraft::where('id_rso', $id_rso)->first();

            if (!$draft) {
                return response()->json([
                    'success' => false,
                    'message' => 'Draft tidak ditemukan.',
                ], 404);
            }

            $draft->delete();

            Log::info("Draft deleted successfully: " . $id_rso);

            return response()->json([
                'success' => true,
                'message' => 'Draft berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting draft {$id_rso}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus draft.',
            ], 500);
        }
    }

    // ==========================================
    // API HELPER
    // ==========================================

    public function getCompanyAddress(Request $request)
    {
        $name = $request->query('name');
        if (!$name) {
            return response()->json(['address' => '']);
        }

        try {
            $col = Schema::hasColumn('companies', 'nama_perusahaan') ? 'nama_perusahaan' : 'name';
            $company = DB::table('companies')->where($col, $name)->first();
            $addr = $company->address ?? $company->alamat ?? $company->location ?? '';
            return response()->json(['address' => $addr]);
        } catch (\Exception $e) {
            Log::error("Error fetching company address: " . $e->getMessage());
            return response()->json(['address' => '']);
        }
    }

    // ==========================================
    // MAIN DOCUMENT GENERATOR
    // ==========================================

    public function generateDocument(Request $request)
    {
        Log::info("=== DOCUMENT GENERATION START ===");

        try {
            $type = $request->input('doc_type');
            $data = $request->input('data');

            if (!$type || !$data) {
                throw new \Exception("Data input kosong atau tidak lengkap.");
            }

            Log::info("Generating document type: {$type}");
            Log::info("Data received: " . json_encode($data));

            $hasMitra2 = !empty(data_get($data, 'obl.mitra_2.nama'));
            $this->ensureDirectoryExists();

            // Special handling untuk P3 dengan 2 mitra (generate ZIP)
            if ($type === 'P3' && $hasMitra2) {
                return $this->generateP3Zip($data);
            }

            return $this->generateSingleDoc($type, $data);

        } catch (\Throwable $e) {
            Log::error("FATAL ERROR in generateDocument: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // DOCUMENT GENERATION METHODS
    // ==========================================

    /**
     * Generate P3 ZIP containing both Mitra 1 and Mitra 2 documents
     */
    private function generateP3Zip($data)
    {
        Log::info("Generating P3 ZIP bundle for 2 mitra");

        // 1. Generate Mitra 1 (Utama)
        $path1 = $this->createDocFile('P3', $data, 1);
        $name1 = $this->getStandardFilename('P3', $data, 1);

        // 2. Generate Mitra 2 (Pembanding)
        $path2 = $this->createDocFile('P3', $data, 2);
        $name2 = $this->getStandardFilename('P3', $data, 2);

        // 3. Nama ZIP
        $idRso = preg_replace('/[^A-Za-z0-9]/', '', data_get($data, 'general.id_rso', 'ID'));
        $pelanggan = preg_replace('/[^A-Za-z0-9\s-]/', '', data_get($data, 'general.pelanggan', 'Pelanggan'));
        $pelanggan = trim($pelanggan);

        $zipDownloadName = "{$idRso} [P3] {$pelanggan}.zip";

        // 4. Proses ZIP
        $tempZipName = 'Bundle_' . uniqid() . '.zip';
        $zipPath = public_path('storage/generated/' . $tempZipName);
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($path1, $name1);
            $zip->addFile($path2, $name2);
            $zip->close();

            @unlink($path1);
            @unlink($path2);

            return response()->download($zipPath, $zipDownloadName)->deleteFileAfterSend(true);
        }

        throw new \Exception("Gagal membuat ZIP file.");
    }

    /**
     * Generate single document
     */
    private function generateSingleDoc($type, $data)
    {
        Log::info("Generating single document: {$type}");

        $path = $this->createDocFile($type, $data, 1);
        $downloadName = $this->getStandardFilename($type, $data, 1);

        return response()->download($path, $downloadName)->deleteFileAfterSend(true);
    }

    /**
     * Helper: Format Nama File Standar
     */
    private function getStandardFilename($type, $data, $mitraIndex)
    {
        $idRso = preg_replace('/[^A-Za-z0-9-]/', '', data_get($data, 'general.id_rso', 'ID'));
        $pelanggan = preg_replace('/[^A-Za-z0-9\s-]/', '', data_get($data, 'general.pelanggan', 'Pelanggan'));
        $pelanggan = trim($pelanggan);

        // Deteksi mode tender
        $hasMitra2 = !empty(data_get($data, 'obl.mitra_2.nama'));

        // Untuk P2, P4, P5: jika tender, nama file pakai "Tender"
        // Untuk P3: pakai nama mitra berdasarkan $mitraIndex
        // Untuk P6, P7: pakai nama mitra pemenang (mitra_final)
        $namaMitraPart = '';

        if (in_array($type, ['P2', 'P4', 'P5']) && $hasMitra2) {
            // Mode Tender: nama file pakai "TENDER"
            $namaMitraPart = 'TENDER';
            Log::info("Filename for {$type}: Using 'TENDER' (Tender Mode Active)");
        } elseif (in_array($type, ['P6-Nego', 'P6-BestPrice', 'P7'])) {
            // P6 dan P7: gunakan mitra_final yang dipilih (jika tender) atau mitra_1 (jika non-tender)
            $selectedMitra = data_get($data, 'obl.p6.mitra_final', 'mitra_1');
            
            // Jika mitra_final kosong atau tidak ada, default ke mitra_1
            if (empty($selectedMitra)) {
                $selectedMitra = 'mitra_1';
            }
            
            $mitraKey = ($selectedMitra === 'mitra_2') ? 'mitra_2' : 'mitra_1';
            
            // Get mitra nama from selected mitra
            $rawNamaMitra = data_get($data, "obl.$mitraKey.nama", '');
            
            // Fallback: jika mitra yang dipilih tidak punya nama, coba mitra lainnya
            if (empty($rawNamaMitra)) {
                $fallbackKey = ($mitraKey === 'mitra_2') ? 'mitra_1' : 'mitra_2';
                $rawNamaMitra = data_get($data, "obl.$fallbackKey.nama", '');
                Log::info("Filename for {$type}: Primary mitra ({$mitraKey}) empty, falling back to {$fallbackKey}");
            }
            
            $namaMitraPart = preg_replace('/[^A-Za-z0-9\s-]/', '', $rawNamaMitra ?? '');
            $namaMitraPart = trim($namaMitraPart);
            
            // If still empty, use default 'Mitra'
            if (empty($namaMitraPart)) {
                $namaMitraPart = 'Mitra';
            }
            
            Log::info("Filename for {$type}: mitra_final={$selectedMitra}, mitraKey={$mitraKey}, namaMitra={$namaMitraPart}");
        } else {
            // Non-Tender atau dokumen lain (P3): pakai nama mitra berdasarkan index
            $mitraKey = ($mitraIndex == 2) ? 'mitra_2' : 'mitra_1';
            $namaMitraPart = preg_replace('/[^A-Za-z0-9\s-]/', '', data_get($data, "obl.$mitraKey.nama", 'Mitra'));
            $namaMitraPart = trim($namaMitraPart);
            Log::info("Filename for {$type}: Using mitra name '{$namaMitraPart}'");
        }

        $finalFilename = "{$idRso} [{$type}] {$pelanggan} - {$namaMitraPart}.docx";
        Log::info("=== FINAL FILENAME: {$finalFilename} ===");
        return $finalFilename;
    }

    /**
     * Create document file from template
     */
    private function createDocFile($type, $data, $mitraIndex = 1)
    {
        Log::info("Creating doc file - Type: {$type}, Mitra Index: {$mitraIndex}");

        // Determine template filename
        $tpl = $type . '.docx';

        if ($type === 'P5') {
            $mode = data_get($data, 'obl.p5.mode', 'Non-Tender');
            $tpl = ($mode === 'Tender') ? 'P5 (Tender).docx' : 'P5 (Non-Tender).docx';
        }

        if ($type === 'P6-Nego')
            $tpl = 'P6 (Nego).docx';
        if ($type === 'P6-BestPrice')
            $tpl = 'P6 (SPH BestPrice).docx';

        $path = public_path('storage/templates/' . $tpl);
        if (!file_exists($path)) {
            $path = public_path('templates/' . $tpl);
            if (!file_exists($path)) {
                throw new \Exception("Template tidak ditemukan: {$tpl}");
            }
        }

        $proc = new TemplateProcessor($path);

        // Map variables
        $this->mapVariables($proc, $data, $mitraIndex, $type);

        // Process P4 Table Dinamis
        if ($type === 'P4') {
            $this->processP4Table($proc, $data);
        }

        // Process P2 Table Dinamis (Mitra List)
        if ($type === 'P2') {
            $this->processP2Table($proc, $data);
        }

        // Save temporary file
        $id = preg_replace('/[^A-Za-z0-9]/', '', data_get($data, 'general.id_rso', 'ID'));
        $savePath = public_path("storage/generated/Temp_{$id}_" . uniqid() . ".docx");

        $proc->saveAs($savePath);

        return $savePath;
    }

    // ==========================================
    // VARIABLE MAPPING
    // ==========================================

    private function mapVariables($proc, $data, $idx, $type = '')
    {
        $gen = $data['general'] ?? [];
        $obl = $data['obl'] ?? [];

        // 1. GENERAL INFO
        $this->safeSetValue($proc, 'judul_p1', $gen['judul_p1'] ?? '');
        $this->safeSetValue($proc, 'nomor_p1', $gen['nomor_p1'] ?? '');
        $this->safeSetValue($proc, 'pelanggan', $gen['pelanggan'] ?? '');
        
        // Build id_rso_obl with format: {id_rso}_OBL_{index}
        $idRso = $gen['id_rso'] ?? '';
        $oblIndex = $obl['obl_index'] ?? 1;
        $idRsoObl = $idRso . '_OBL_' . $oblIndex;
        $this->safeSetValue($proc, 'id_rso_obl', $idRsoObl);
        $this->safeSetValue($proc, 'id_rso', $idRso); // Also set id_rso separately
        
        $this->setDocVariables($proc, 'p1', $gen['tanggal_p1'] ?? null);

        // 2. LAYANAN
        $layananRaw = $obl['layanan'] ?? [];
        if (!is_array($layananRaw)) {
            $layananRaw = [$layananRaw];
        }
        $cleanLayanan = array_filter($layananRaw, function ($v) {
            return !empty($v);
        });

        $layananStr = '';
        $count = count($cleanLayanan);
        if ($count === 0)
            $layananStr = '';
        elseif ($count === 1)
            $layananStr = reset($cleanLayanan);
        else {
            $lastItem = array_pop($cleanLayanan);
            $layananStr = implode(', ', $cleanLayanan) . ' dan ' . $lastItem;
        }
        $this->safeSetValue($proc, 'layanan', $layananStr);

        // 3. MITRA INFO
        $m1 = data_get($obl, 'mitra_1', []);
        $m2 = data_get($obl, 'mitra_2', []);
        
        // Deteksi Mode Tender
        $hasMitra2 = !empty($m2['nama']);
        
        // Tentukan mitra aktif berdasarkan tipe dokumen
        // Untuk P6, P7: gunakan mitra_final yang dipilih user
        // Untuk P3: gunakan $idx (mitra 1 atau 2)
        // Untuk dokumen lain: gunakan mitra_1 atau gabungan
        if (in_array($type, ['P6-Nego', 'P6-BestPrice', 'P7'])) {
            $selectedMitra = data_get($obl, 'p6.mitra_final', 'mitra_1');
            if (empty($selectedMitra)) {
                $selectedMitra = 'mitra_1';
            }
            
            // Pilih mitra berdasarkan selection
            $active = ($selectedMitra === 'mitra_2' && !empty($m2['nama'])) ? $m2 : $m1;
            
            // Jika mitra yang dipilih kosong, fallback ke mitra lain
            if (empty($active['nama'])) {
                $active = (!empty($m2['nama'])) ? $m2 : $m1;
            }
            
            Log::info("Document {$type} - mitra_final: {$selectedMitra}, active nama: " . ($active['nama'] ?? 'empty'));
        } else {
            $active = ($idx == 2 && !empty($m2['nama'])) ? $m2 : $m1;
        }

        // Logic Nama Mitra berdasarkan dokumen
        $namaMitraFinal = '';

        if ($type === 'P4' && $hasMitra2) {
            // P4 dengan 2 mitra: tampilkan "Mitra 1 dan Mitra 2"
            $namaMitraFinal = ($m1['nama'] ?? 'Mitra 1') . ' dan ' . ($m2['nama'] ?? 'Mitra 2');
        } else {
            // Selain P4 atau hanya 1 mitra: tampilkan nama mitra aktif
            $namaMitraFinal = $active['nama'] ?? '-';
        }

        $this->safeSetValue($proc, 'mitra', $namaMitraFinal);

        Log::info("Type: {$type}, Has Mitra 2: " . ($hasMitra2 ? 'YES' : 'NO') . ", Mitra Final: {$namaMitraFinal}");

        // Alamat Multi-line
        $alamatRaw = $active['alamat'] ?? '-';
        $this->safeSetValue($proc, 'alamat_mitra', $alamatRaw);
        $this->safeSetValue($proc, 'mitra_block', ($active['nama'] ?? '-') . "\n" . ($active['alamat'] ?? '-'));

        // Mitra Details
        $this->safeSetValue($proc, 'mitra_1', $m1['nama'] ?? '-');
        $this->safeSetValue($proc, 'nomor_sph_mitra_1', $m1['nomor_sph'] ?? '-');
        $this->safeSetValue($proc, 'harga_sph_mitra_1', $this->fmtMoney($m1['harga'] ?? 0));

        $val1 = str_replace(['.', ','], '', $m1['harga'] ?? '0');
        $terbilang1 = ucwords($this->terbilang($val1)) . " Rupiah";
        $this->safeSetValue($proc, 'terbilang_harga_sph_mitra_1', $terbilang1);
        $this->setDocVariables($proc, 'sph_mitra_1', $m1['tanggal_sph'] ?? null);

        $this->safeSetValue($proc, 'mitra_2', $m2['nama'] ?? '-');
        $this->safeSetValue($proc, 'nomor_sph_mitra_2', $m2['nomor_sph'] ?? '-');
        $this->safeSetValue($proc, 'harga_sph_mitra_2', $this->fmtMoney($m2['harga'] ?? 0));

        $val2 = str_replace(['.', ','], '', $m2['harga'] ?? '0');
        $terbilang2 = ucwords($this->terbilang($val2)) . " Rupiah";
        $this->safeSetValue($proc, 'terbilang_harga_sph_mitra_2', $terbilang2);
        $this->setDocVariables($proc, 'sph_mitra_2', $m2['tanggal_sph'] ?? null);

        // 4. DOCUMENT SPECIFIC VARIABLES
        if (isset($obl['p2']))
            $this->setDocVariables($proc, 'p2', $obl['p2']['tanggal'] ?? null);

        if (isset($obl['p3'])) {
            $key = ($idx == 2) ? 'mitra_2' : 'mitra_1';
            $this->safeSetValue($proc, 'nomor_p3', data_get($obl, "p3.$key.nomor", '-'));
            $this->setDocVariables($proc, 'p3', data_get($obl, "p3.$key.tanggal"));
            $this->setDocVariables($proc, 'sph', $active['tanggal_sph'] ?? null);
            $this->setDocVariables($proc, 'p4', data_get($obl, 'p4.tanggal'));
        }

        if (isset($obl['p4'])) {
            $this->setDocVariables($proc, 'p4', $obl['p4']['tanggal'] ?? null);
            $tgt = $obl['p4']['target'] ?? null;
            $this->safeSetValue($proc, 'est_target_delivery', $this->fmtDate($tgt));
            $this->safeSetValue($proc, 'durasi', $obl['p4']['durasi'] ?? '-');
            $this->safeSetValue($proc, 'terms_of_payment', $obl['p4']['top'] ?? '-');
            $this->safeSetValue($proc, 'skema_bisnis', $obl['p4']['skema'] ?? '-');
            $this->safeSetValue($proc, 'slg', $obl['p4']['slg'] ?? '-');

            // Set jangka waktu dengan prefix (tanggal_jangka_waktu_awal, dll)
            $this->setDocVariables($proc, 'jangka_waktu_awal', data_get($obl, 'p4.start'));
            $this->setDocVariables($proc, 'jangka_waktu_akhir', data_get($obl, 'p4.end'));

            // TAMBAHAN: Set jangka waktu tanpa prefix untuk backward compatibility
            $startDate = data_get($obl, 'p4.start');
            $endDate = data_get($obl, 'p4.end');
            $this->safeSetValue($proc, 'jangka_waktu_awal', $startDate ? $this->fmtDate($startDate) : '-');
            $this->safeSetValue($proc, 'jangka_waktu_akhir', $endDate ? $this->fmtDate($endDate) : '-');
        }

        if (isset($obl['p5'])) {
            $this->setDocVariables($proc, 'p5', $obl['p5']['tanggal'] ?? null);
            $terbilangLegacy = $obl['p5']['terbilang'] ?? '';
            if (empty($terbilangLegacy))
                $terbilangLegacy = $terbilang1;
            $this->safeSetValue($proc, 'terbilang_nilai_sph_mitra', $terbilangLegacy);
            $this->safeSetValue($proc, 'nilai_sph_mitra', $this->fmtMoney($m1['harga'] ?? 0));
        }

        if (isset($obl['p6'])) {
            // Tentukan mitra yang akan dipakai di P6
            $selectedMitra = data_get($obl, 'p6.mitra_final', 'mitra_1');
            if (empty($selectedMitra)) {
                $selectedMitra = 'mitra_1';
            }
            $mitraFinalKey = ($selectedMitra === 'mitra_2') ? 'mitra_2' : 'mitra_1';

            // Ambil data mitra final
            $mitraFinal = data_get($obl, $mitraFinalKey, []);
            
            // Get nama mitra dengan fallback ke mitra lain jika kosong
            $namaMitraFinal = $this->getMitraNamaWithFallback($obl, $mitraFinalKey);

            Log::info("P6 - Using {$mitraFinalKey} as final mitra: " . $namaMitraFinal);

            // Set variable mitra untuk P6 (HANYA SATU MITRA, BUKAN GABUNGAN)
            $this->safeSetValue($proc, 'mitra', $namaMitraFinal);
            $this->safeSetValue($proc, 'mitra_pemenang', $namaMitraFinal); // Nama mitra pemenang tender
            $this->setAddressValue($proc, 'alamat_mitra', $mitraFinal['alamat'] ?? '-');

            // Set data SPH dari mitra final
            $this->safeSetValue($proc, 'nomor_sph', $mitraFinal['nomor_sph'] ?? '-');
            $this->safeSetValue($proc, 'no_sph', $mitraFinal['nomor_sph'] ?? '-'); // Alias for ${no_sph}
            $this->setDocVariables($proc, 'sph', $mitraFinal['tanggal_sph'] ?? null);
            $this->safeSetValue($proc, 'harga_sph_mitra_final', $this->fmtMoney($mitraFinal['harga'] ?? 0));
            $this->safeSetValue($proc, 'harga_sph', $this->fmtMoney($mitraFinal['harga'] ?? 0)); // Alias for ${harga_sph}

            // Set harga P6 (hasil nego)
            $this->safeSetValue($proc, 'harga_bulanan_sph', $this->fmtMoney(data_get($obl, 'p6.harga_bulanan')));
            $this->safeSetValue($proc, 'harga_otc_sph', $this->fmtMoney(data_get($obl, 'p6.harga_otc')));
            $this->safeSetValue($proc, 'harga_total', $this->fmtMoney(data_get($obl, 'p6.harga_total')));
            $this->safeSetValue($proc, 'total_harga_sph', $this->fmtMoney(data_get($obl, 'p6.harga_total')));
            $this->safeSetValue($proc, 'harga_total_bulanan', $this->fmtMoney(data_get($obl, 'p6.harga_bulanan')));
            $this->safeSetValue($proc, 'harga_total_otc', $this->fmtMoney(data_get($obl, 'p6.harga_otc')));
            $this->setDocVariables($proc, 'p6', $obl['p6']['tanggal'] ?? null);
            $this->setDocVariables($proc, 'delivery', $obl['p6']['delivery'] ?? null);

            $this->safeSetValue($proc, 'durasi', data_get($obl, 'p4.durasi', '-'));
            
            // Format Terms of Payment dengan harga sesuai opsi yang dipilih
            $topOption = data_get($obl, 'p4.top', '-');
            $topLower = strtolower(trim($topOption));
            
            // Ambil harga dari P6 terlebih dahulu
            $rawHargaBulanan = data_get($obl, 'p6.harga_bulanan', 0);
            $rawHargaOtc = data_get($obl, 'p6.harga_otc', 0);
            $rawHargaTotal = data_get($obl, 'p6.harga_total', 0);
            
            // Fallback logic: jika harga spesifik kosong, gunakan harga total atau harga dari mitra
            // Untuk OTC/Termin saja: jika harga_otc kosong, gunakan harga_total
            if (($topLower === 'otc' || $topLower === 'termin') && empty($rawHargaOtc)) {
                $rawHargaOtc = $rawHargaTotal ?: data_get($obl, "{$mitraFinalKey}.harga", 0);
            }
            
            // Untuk Bulanan saja: jika harga_bulanan kosong, gunakan harga_total
            if ($topLower === 'bulanan' && empty($rawHargaBulanan)) {
                $rawHargaBulanan = $rawHargaTotal ?: data_get($obl, "{$mitraFinalKey}.harga", 0);
            }
            
            // Debug logging
            Log::info("P6 Terms of Payment Debug - TOP: {$topOption}, Bulanan: {$rawHargaBulanan}, OTC: {$rawHargaOtc}, Total: {$rawHargaTotal}");
            
            $hargaBulanan = $this->fmtMoney($rawHargaBulanan);
            $hargaOtc = $this->fmtMoney($rawHargaOtc);
            $formattedTop = $this->formatTermsOfPayment($topOption, $hargaBulanan, $hargaOtc);
            $descTop = $this->formatDescTermsOfPayment($topOption, $hargaBulanan, $hargaOtc);
            
            Log::info("P6 Formatted Terms of Payment: {$formattedTop}");
            Log::info("P6 Desc Terms of Payment: {$descTop}");
            
            $this->safeSetValue($proc, 'terms_of_payment', $formattedTop);
            $this->safeSetValue($proc, 'desc_terms_of_payment', $descTop);
            
            $this->safeSetValue($proc, 'skema_bisnis', data_get($obl, 'p4.skema', '-'));
            $this->safeSetValue($proc, 'slg', data_get($obl, 'p4.slg', '-'));

            // Set jangka waktu dengan prefix (tanggal_jangka_waktu_awal, dll)
            $this->setDocVariables($proc, 'jangka_waktu_awal', data_get($obl, 'p4.start'));
            $this->setDocVariables($proc, 'jangka_waktu_akhir', data_get($obl, 'p4.end'));

            // TAMBAHAN: Set jangka waktu tanpa prefix untuk backward compatibility
            $startDate = data_get($obl, 'p4.start');
            $endDate = data_get($obl, 'p4.end');
            $this->safeSetValue($proc, 'jangka_waktu_awal', $startDate ? $this->fmtDate($startDate) : '-');
            $this->safeSetValue($proc, 'jangka_waktu_akhir', $endDate ? $this->fmtDate($endDate) : '-');
        }

        if (isset($obl['p7'])) {
            // P7 juga menggunakan mitra final yang sama dengan P6
            // Tentukan mitra yang akan dipakai di P7 (sama dengan P6)
            $selectedMitra = data_get($obl, 'p6.mitra_final', 'mitra_1');
            if (empty($selectedMitra)) {
                $selectedMitra = 'mitra_1';
            }
            $mitraFinalKey = ($selectedMitra === 'mitra_2') ? 'mitra_2' : 'mitra_1';

            // Ambil data mitra final
            $mitraFinal = data_get($obl, $mitraFinalKey, []);
            
            // Get nama mitra dengan fallback ke mitra lain jika kosong
            $namaMitraFinal = $this->getMitraNamaWithFallback($obl, $mitraFinalKey);

            Log::info("P7 - Using {$mitraFinalKey} as final mitra: " . $namaMitraFinal);

            // Set variable mitra untuk P7 (HANYA SATU MITRA)
            $this->safeSetValue($proc, 'mitra', $namaMitraFinal);
            $this->safeSetValue($proc, 'mitra_pemenang', $namaMitraFinal); // Nama mitra pemenang tender
            $this->setAddressValue($proc, 'alamat_mitra', $mitraFinal['alamat'] ?? '-');

            // Set data P7
            $this->safeSetValue($proc, 'nomor_p7', $obl['p7']['nomor'] ?? '-');
            $this->setDocVariables($proc, 'p7', $obl['p7']['tanggal'] ?? null);

            // Set harga dari P6 (final nego)
            $this->safeSetValue($proc, 'harga_total', $this->fmtMoney(data_get($obl, 'p6.harga_total', 0)));
            $this->safeSetValue($proc, 'harga_total_bulanan', $this->fmtMoney(data_get($obl, 'p6.harga_bulanan', 0)));
            $this->safeSetValue($proc, 'harga_total_otc', $this->fmtMoney(data_get($obl, 'p6.harga_otc', 0)));

            // Format Terms of Payment dengan harga sesuai opsi yang dipilih
            $topOption = data_get($obl, 'p4.top', '-');
            $topLower = strtolower(trim($topOption));
            
            // Ambil harga dari P6 terlebih dahulu
            $rawHargaBulanan = data_get($obl, 'p6.harga_bulanan', 0);
            $rawHargaOtc = data_get($obl, 'p6.harga_otc', 0);
            $rawHargaTotal = data_get($obl, 'p6.harga_total', 0);
            
            // Fallback logic: jika harga spesifik kosong, gunakan harga total atau harga dari mitra
            if (($topLower === 'otc' || $topLower === 'termin') && empty($rawHargaOtc)) {
                $rawHargaOtc = $rawHargaTotal ?: data_get($obl, "{$mitraFinalKey}.harga", 0);
            }
            if ($topLower === 'bulanan' && empty($rawHargaBulanan)) {
                $rawHargaBulanan = $rawHargaTotal ?: data_get($obl, "{$mitraFinalKey}.harga", 0);
            }
            
            $hargaBulanan = $this->fmtMoney($rawHargaBulanan);
            $hargaOtc = $this->fmtMoney($rawHargaOtc);
            $formattedTop = $this->formatTermsOfPayment($topOption, $hargaBulanan, $hargaOtc);
            $descTop = $this->formatDescTermsOfPayment($topOption, $hargaBulanan, $hargaOtc);
            $this->safeSetValue($proc, 'terms_of_payment', $formattedTop);
            $this->safeSetValue($proc, 'desc_terms_of_payment', $descTop);
            
            // Set durasi dan skema bisnis dari P4
            $this->safeSetValue($proc, 'durasi', data_get($obl, 'p4.durasi', '-'));
            $this->safeSetValue($proc, 'skema_bisnis', data_get($obl, 'p4.skema', '-'));
            $this->safeSetValue($proc, 'slg', data_get($obl, 'p4.slg', '-'));
        }
    }

    // ==========================================
    // TABLE PROCESSORS
    // ==========================================

    private function processP4Table($proc, $data)
    {
        $layananRaw = data_get($data, 'obl.layanan', []);
        if (!is_array($layananRaw))
            $layananRaw = [$layananRaw];
        $items = array_filter($layananRaw);

        if (empty($items))
            return;

        $rows = [];
        $num = 1;
        $durasiStr = data_get($data, 'obl.p4.durasi', '-');
        $durasiVal = (int) filter_var($durasiStr, FILTER_SANITIZE_NUMBER_INT);
        if ($durasiVal == 0)
            $durasiVal = '-';

        foreach ($items as $item) {
            $rows[] = [
                'no_item' => $num++,
                'item_layanan' => $item,
                'durasi_bulan' => $durasiVal
            ];
        }

        try {
            $proc->cloneRowAndSetValues('item_layanan', $rows);
            Log::info("P4 Table cloned successfully with " . count($rows) . " rows");
        } catch (\Exception $e) {
            Log::warning("Gagal clone row P4: " . $e->getMessage());
        }
    }

    /**
     * Process P2 Table (Dynamic Mitra Rows)
     */
    private function processP2Table($proc, $data)
    {
        $rows = [];

        // Add Mitra 1
        if ($m1 = data_get($data, 'obl.mitra_1.nama')) {
            $rows[] = ['poin' => '-', 'mitra_row' => $m1];
        }

        // Add Mitra 2
        if ($m2 = data_get($data, 'obl.mitra_2.nama')) {
            $rows[] = ['poin' => '-', 'mitra_row' => $m2];
        }

        if (!empty($rows)) {
            try {
                // Clone berdasarkan variabel 'mitra_row' di tabel
                $proc->cloneRowAndSetValues('mitra_row', $rows);
                Log::info("P2 Table cloned successfully with " . count($rows) . " rows");
            } catch (\Exception $e) {
                Log::error("Gagal clone row P2: " . $e->getMessage());
                // Fallback: set manual jika clone gagal
                $mitraList = implode(', ', array_column($rows, 'mitra_row'));
                $this->safeSetValue($proc, 'mitra_row', $mitraList);
            }
        }
    }

    // ==========================================
    // UTILITIES
    // ==========================================

    private function setAddressValue($proc, $key, $value)
    {
        if (strpos($value, "\n") !== false) {
            $textRun = new TextRun();
            $lines = preg_split('/\r\n|\r|\n/', $value);
            foreach ($lines as $k => $line) {
                $textRun->addText(trim($line));
                if ($k < count($lines) - 1)
                    $textRun->addTextBreak();
            }
            try {
                $proc->setComplexValue($key, $textRun);
            } catch (\Exception $e) {
                $this->safeSetValue($proc, $key, $value);
            }
        } else {
            $this->safeSetValue($proc, $key, $value);
        }
    }

    private function safeSetValue($proc, $key, $value)
    {
        $value = (string) ($value ?? '');
        try {
            $proc->setValue($key, $value);
        } catch (\Exception $e) {
        }
        try {
            $proc->setValue(strtoupper($key), $value);
            $proc->setValue(ucfirst($key), $value);
        } catch (\Exception $e) {
        }

        try {
            $xmlContent = $proc->temporaryDocumentMainPart;
            $patterns = [
                '/\$\{' . preg_quote($key, '/') . '\}/i',
                '/\$\{' . preg_quote(strtoupper($key), '/') . '\}/i',
                '/\$\{' . preg_quote(ucfirst($key), '/') . '\}/i',
            ];
            $xmlValue = htmlspecialchars($value, ENT_XML1);
            foreach ($patterns as $pattern) {
                $xmlContent = preg_replace($pattern, $xmlValue, $xmlContent);
            }
            $proc->temporaryDocumentMainPart = $xmlContent;
        } catch (\Exception $e) {
        }
    }

    private function setDocVariables($proc, $suffix, $dateStr)
    {
        if (!$dateStr) {
            foreach (['tanggal', 'hari', 'tanggal_teks', 'bulan_teks', 'tahun_teks'] as $p) {
                $this->safeSetValue($proc, "{$p}_{$suffix}", '-');
            }
            return;
        }
        try {
            $dt = Carbon::parse($dateStr)->locale('id');
            $this->safeSetValue($proc, "tanggal_{$suffix}", $dt->isoFormat('D MMMM Y'));
            $this->safeSetValue($proc, "hari_{$suffix}", $dt->isoFormat('dddd'));
            $this->safeSetValue($proc, "tanggal_teks_{$suffix}", ucwords($this->terbilang($dt->day)));
            $this->safeSetValue($proc, "bulan_teks_{$suffix}", $dt->isoFormat('MMMM'));
            $this->safeSetValue($proc, "tahun_teks_{$suffix}", ucwords($this->terbilang($dt->year)));
        } catch (\Exception $e) {
        }
    }

    /**
     * Format Terms of Payment berdasarkan opsi yang dipilih (format singkat)
     * 
     * @param string $option - Opsi TOP: Bulanan, OTC, Termin, Bulanan dan OTC, Bulanan dan Termin
     * @param string $hargaBulanan - Harga bulanan yang sudah diformat
     * @param string $hargaOtc - Harga OTC/Termin yang sudah diformat
     * @return string
     */
    private function formatTermsOfPayment($option, $hargaBulanan, $hargaOtc)
    {
        $option = strtolower(trim($option));
        
        if ($option === 'bulanan') {
            return "Bulanan sebesar {$hargaBulanan}";
        }
        
        if ($option === 'otc') {
            return "OTC sebesar {$hargaOtc}";
        }
        
        if ($option === 'termin') {
            return "Termin sebesar {$hargaOtc}";
        }
        
        if ($option === 'bulanan dan otc') {
            return "Bulanan sebesar {$hargaBulanan} dan OTC sebesar {$hargaOtc}";
        }
        
        if ($option === 'bulanan dan termin') {
            return "Bulanan sebesar {$hargaBulanan} dan Termin sebesar {$hargaOtc}";
        }
        
        // Fallback: return original option if not matched
        return $option ?: '-';
    }

    /**
     * Get nama mitra dengan fallback ke mitra lain jika kosong
     * 
     * @param array $obl - Data OBL
     * @param string $primaryKey - Key mitra utama (mitra_1 atau mitra_2)
     * @return string
     */
    private function getMitraNamaWithFallback($obl, $primaryKey)
    {
        // Try primary mitra first
        $nama = data_get($obl, "{$primaryKey}.nama", '');
        
        if (!empty($nama)) {
            return $nama;
        }
        
        // Fallback to the other mitra
        $fallbackKey = ($primaryKey === 'mitra_2') ? 'mitra_1' : 'mitra_2';
        $nama = data_get($obl, "{$fallbackKey}.nama", '');
        
        if (!empty($nama)) {
            Log::info("getMitraNamaWithFallback: Primary {$primaryKey} empty, using {$fallbackKey}: {$nama}");
            return $nama;
        }
        
        // If both are empty, return default
        return '-';
    }

    /**
     * Format Deskripsi Terms of Payment (format lengkap dengan rincian)
     * 
     * @param string $option - Opsi TOP: Bulanan, OTC, Termin, Bulanan dan OTC, Bulanan dan Termin
     * @param string $hargaBulanan - Harga bulanan yang sudah diformat
     * @param string $hargaOtc - Harga OTC/Termin yang sudah diformat
     * @return string
     */
    private function formatDescTermsOfPayment($option, $hargaBulanan, $hargaOtc)
    {
        $option = strtolower(trim($option));
        
        if ($option === 'bulanan') {
            return "Bulanan sebesar {$hargaBulanan}.";
        }
        
        if ($option === 'otc') {
            return "OTC sebesar {$hargaOtc}.";
        }
        
        if ($option === 'termin') {
            return "Termin sebesar {$hargaOtc}.";
        }
        
        if ($option === 'bulanan dan otc') {
            return "Bulanan sebesar {$hargaBulanan} dan rincian OTC sebesar {$hargaOtc}.";
        }
        
        if ($option === 'bulanan dan termin') {
            return "Bulanan sebesar {$hargaBulanan} dan rincian Termin sebesar {$hargaOtc}.";
        }
        
        // Fallback: return original option if not matched
        return $option ?: '-';
    }

    private function fmtMoney($val)
    {
        if (!$val)
            return "Rp 0";
        if (strpos((string) $val, '.') !== false)
            return "Rp " . $val;
        if (is_numeric($val))
            return "Rp " . number_format((float) $val, 0, ',', '.');
        return $val;
    }

    private function fmtDate($date)
    {
        try {
            return $date ? Carbon::parse($date)->locale('id')->isoFormat('D MMMM Y') : '-';
        } catch (\Exception $e) {
            return '-';
        }
    }

    private function terbilang($x)
    {
        $x = abs((int) filter_var($x, FILTER_SANITIZE_NUMBER_INT));
        $angka = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        if ($x < 12)
            return " " . $angka[$x];
        elseif ($x < 20)
            return $this->terbilang($x - 10) . " belas";
        elseif ($x < 100)
            return $this->terbilang((int) ($x / 10)) . " puluh" . $this->terbilang($x % 10);
        elseif ($x < 200)
            return " seratus" . $this->terbilang($x - 100);
        elseif ($x < 1000)
            return $this->terbilang((int) ($x / 100)) . " ratus" . $this->terbilang($x % 100);
        elseif ($x < 2000)
            return " seribu" . $this->terbilang($x - 1000);
        elseif ($x < 1000000)
            return $this->terbilang((int) ($x / 1000)) . " ribu" . $this->terbilang($x % 1000);
        elseif ($x < 1000000000)
            return $this->terbilang((int) ($x / 1000000)) . " juta" . $this->terbilang($x % 1000000);
        return "";
    }

    private function ensureDirectoryExists()
    {
        $dir = public_path('storage/generated');
        if (!file_exists($dir))
            mkdir($dir, 0777, true);
    }
}