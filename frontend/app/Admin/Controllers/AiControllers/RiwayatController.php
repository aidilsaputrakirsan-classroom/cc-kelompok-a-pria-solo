<?php

namespace App\Admin\Controllers\AiControllers;

use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use \App\Models\Ticket;
use \App\Models\GroundTruth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RiwayatController extends Controller
{

    public function index(Request $request, Content $content)
    {
        // Load custom CSS files (not OpenAdmin CSS)
        Admin::css(asset('css/navbar.css'));
        
        // Load custom JS files
        Admin::js(asset('js/history-page-handler.js'));
        
        $search = $request->input('search');

        // Query untuk Stage "Validation"
        $validationTickets = Ticket::select(
            'tickets.id',
            'tickets.ticket_number',
            'tickets.project_title',
            'tickets.created_at',
            'companies.name as company_name',
            DB::raw("'Validation' as stage"),
            DB::raw('NULL as main_filename'),
            DB::raw('NULL as total_original_page'),
            DB::raw('NULL as version'),
            DB::raw('MAX(ground_truths.created_at) as version_created_at'),
            DB::raw('COALESCE((
                SELECT 
                    (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(notes, "$.mitra")) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.mitra")) != "" AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.mitra")) != "null" THEN 1 ELSE 0 END) +
                    (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(notes, "$.obl")) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.obl")) != "" AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.obl")) != "null" THEN 1 ELSE 0 END) +
                    (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(notes, "$.internal_telkom")) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.internal_telkom")) != "" AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.internal_telkom")) != "null" THEN 1 ELSE 0 END) +
                    (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(notes, "$.segmen_witel")) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.segmen_witel")) != "" AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.segmen_witel")) != "null" THEN 1 ELSE 0 END) +
                    (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(notes, "$.revisi_precise")) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.revisi_precise")) != "" AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.revisi_precise")) != "null" THEN 1 ELSE 0 END)
                FROM ticket_notes
                WHERE ticket_notes.ticket_id = tickets.id
            ), 0) as total_notes'),
            DB::raw('(
                COALESCE((SELECT COUNT(*) FROM typo_errors WHERE typo_errors.ticket_id = tickets.id), 0) +
                COALESCE((SELECT COUNT(*) FROM price_validations WHERE price_validations.ticket_id = tickets.id), 0) +
                COALESCE((SELECT COUNT(*) FROM date_validations WHERE date_validations.ticket_id = tickets.id AND date_validations.is_valid = 0), 0)
            ) as total_errors'),
            DB::raw('(
                SELECT COUNT(DISTINCT ground_truths.doc_type) 
                FROM ground_truths 
                WHERE ground_truths.ticket_id = tickets.id
            ) as total_files')
        )
            ->join('companies', 'tickets.company_id', '=', 'companies.id')
            ->join('ground_truths', 'tickets.id', '=', 'ground_truths.ticket_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('advance_review_results')
                    ->join('ground_truths as gt', 'advance_review_results.ground_truth_id', '=', 'gt.id')
                    ->whereColumn('gt.ticket_id', 'tickets.id');
            })
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('tickets.ticket_number', 'LIKE', "%{$search}%")
                        ->orWhere('tickets.project_title', 'LIKE', "%{$search}%")
                        ->orWhere('companies.name', 'LIKE', "%{$search}%");
                });
            })
            ->groupBy(
                'tickets.id',
                'tickets.ticket_number',
                'tickets.project_title',
                'tickets.created_at',
                'companies.name'
            );

        // Query untuk Stage "Review"
        $reviewTickets = Ticket::select(
            'tickets.id',
            'tickets.ticket_number',
            'tickets.project_title',
            'tickets.created_at',
            'companies.name as company_name',
            DB::raw("'Review' as stage"),
            DB::raw('NULL as main_filename'),
            DB::raw('NULL as total_original_page'),
            DB::raw('NULL as version'),
            DB::raw('MAX(ground_truths.created_at) as version_created_at'),
            DB::raw('COALESCE((
                SELECT 
                    (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(notes, "$.mitra")) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.mitra")) != "" AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.mitra")) != "null" THEN 1 ELSE 0 END) +
                    (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(notes, "$.obl")) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.obl")) != "" AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.obl")) != "null" THEN 1 ELSE 0 END) +
                    (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(notes, "$.internal_telkom")) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.internal_telkom")) != "" AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.internal_telkom")) != "null" THEN 1 ELSE 0 END) +
                    (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(notes, "$.segmen_witel")) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.segmen_witel")) != "" AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.segmen_witel")) != "null" THEN 1 ELSE 0 END) +
                    (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(notes, "$.revisi_precise")) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.revisi_precise")) != "" AND JSON_UNQUOTE(JSON_EXTRACT(notes, "$.revisi_precise")) != "null" THEN 1 ELSE 0 END)
                FROM ticket_notes
                WHERE ticket_notes.ticket_id = tickets.id
            ), 0) as total_notes'),
            DB::raw('(
                COALESCE((SELECT COUNT(*) FROM typo_errors WHERE typo_errors.ticket_id = tickets.id), 0) +
                COALESCE((SELECT COUNT(*) FROM price_validations WHERE price_validations.ticket_id = tickets.id), 0) +
                COALESCE((SELECT COUNT(*) FROM date_validations WHERE date_validations.ticket_id = tickets.id AND date_validations.is_valid = 0), 0)
            ) as total_errors'),
            DB::raw('(
                SELECT COUNT(DISTINCT advance_review_results.doc_type) 
                FROM advance_review_results 
                JOIN ground_truths AS gt ON advance_review_results.ground_truth_id = gt.id
                WHERE gt.ticket_id = tickets.id
            ) as total_files')
        )
            ->join('companies', 'tickets.company_id', '=', 'companies.id')
            ->join('ground_truths', 'tickets.id', '=', 'ground_truths.ticket_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('advance_review_results')
                    ->join('ground_truths as gt', 'advance_review_results.ground_truth_id', '=', 'gt.id')
                    ->whereColumn('gt.ticket_id', 'tickets.id');
            })
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('tickets.ticket_number', 'LIKE', "%{$search}%")
                        ->orWhere('tickets.project_title', 'LIKE', "%{$search}%")
                        ->orWhere('companies.name', 'LIKE', "%{$search}%");
                });
            })
            ->groupBy(
                'tickets.id',
                'tickets.ticket_number',
                'tickets.project_title',
                'tickets.created_at',
                'companies.name'
            );

        $tickets = $validationTickets
            ->union($reviewTickets)
            ->orderBy('created_at', 'desc')
            ->get();

        // Add logo URLs to tickets
        $tickets = $tickets->map(function ($ticket) {
            $ticket->logo_url = $this->findLogoPath($ticket->company_name);
            return $ticket;
        });

        // Check if this is a data-only AJAX request (not Pjax navigation)
        // Pjax requests have X-PJAX header, regular AJAX data requests don't
        if ($request->ajax() && !$request->header('X-PJAX')) {
            return response()->json([
                'tickets' => $tickets
            ]);
        }

        return $content
            ->title('Riwayat Review')
            ->description('Riwayat review yang pernah dilakukan')
            ->body(view('advance-reviews.history-page', compact('tickets', 'search'), ['isOpenAdmin' => true]));
    }

    /**
     * ✅ Hapus Validation Stage Ticket
     */
    public function destroy($id)
    {
        try {
            $ticket = Ticket::findOrFail($id);
            $ticketNumber = $ticket->ticket_number;

            Log::info('=== START DELETE VALIDATION TICKET ===', [
                'ticket_id' => $id,
                'ticket_number' => $ticketNumber
            ]);

            // ✅ Use Laravel Storage with public disk
            $disk = Storage::disk('public');
            
            // PDFs are stored in "advance-review/{ticketNumber}" directory
            $advanceReviewPath = 'advance-review/' . $ticketNumber;

            Log::info('Attempting to delete advance-review folder', [
                'path' => $advanceReviewPath,
                'exists' => $disk->exists($advanceReviewPath),
                'is_directory' => $disk->exists($advanceReviewPath)
            ]);

            // ✅ Hapus folder jika ada
            $folderDeleted = false;
            if ($disk->exists($advanceReviewPath)) {
                $folderDeleted = $disk->deleteDirectory($advanceReviewPath);

                if ($folderDeleted) {
                    Log::info('✅ Advance-review folder deleted successfully', ['path' => $advanceReviewPath]);
                } else {
                    Log::warning('⚠️ Failed to delete advance-review folder completely', ['path' => $advanceReviewPath]);
                }
            } else {
                Log::info('ℹ️ Advance-review folder not found (already deleted or never created)', [
                    'path' => $advanceReviewPath
                ]);
                $folderDeleted = true; // Consider it "deleted" since it doesn't exist
            }

            // ✅ Delete ticket dari database (cascade akan hapus ground_truths)
            $ticket->delete();

            Log::info('✅ Validation ticket deleted from database', ['ticket_id' => $id]);
            Log::info('=== END DELETE VALIDATION TICKET ===');

            return response()->json([
                'success' => true,
                'message' => 'Tiket Validation berhasil dihapus' . ($folderDeleted ? ' beserta foldernya' : '')
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to delete Validation ticket', [
                'ticket_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus tiket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Hapus Review Stage Ticket
     */
    public function destroyReview($id)
    {
        try {
            $ticket = Ticket::findOrFail($id);
            $ticketNumber = $ticket->ticket_number;

            Log::info('=== START DELETE REVIEW TICKET ===', [
                'ticket_id' => $id,
                'ticket_number' => $ticketNumber
            ]);

            // ✅ Use Laravel Storage with public disk
            $disk = Storage::disk('public');
            
            // PDFs are stored in "advance-review/{ticketNumber}" directory
            $advanceReviewPath = 'advance-review/' . $ticketNumber;

            Log::info('Attempting to delete advance-review folder', [
                'path' => $advanceReviewPath,
                'exists' => $disk->exists($advanceReviewPath),
                'is_directory' => $disk->exists($advanceReviewPath)
            ]);

            // ✅ Hapus folder jika ada
            $folderDeleted = false;
            if ($disk->exists($advanceReviewPath)) {
                $folderDeleted = $disk->deleteDirectory($advanceReviewPath);

                if ($folderDeleted) {
                    Log::info('✅ Advance-review folder deleted successfully', ['path' => $advanceReviewPath]);
                } else {
                    Log::warning('⚠️ Failed to delete advance-review folder completely', ['path' => $advanceReviewPath]);
                }
            } else {
                Log::info('ℹ️ Advance-review folder not found (already deleted or never created)', [
                    'path' => $advanceReviewPath
                ]);
                $folderDeleted = true;
            }

            // ✅ Delete ticket dari database
            $ticket->delete();

            Log::info('✅ Review ticket deleted from database', ['ticket_id' => $id]);
            Log::info('=== END DELETE REVIEW TICKET ===');

            return response()->json([
                'success' => true,
                'message' => 'Tiket Review berhasil dihapus' . ($folderDeleted ? ' beserta foldernya' : '')
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to delete Review ticket', [
                'ticket_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus tiket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find logo path for a company name
     * Checks multiple file extensions in the Logo Mitra folder
     *
     * @param string $companyName
     * @return string
     */
    private function findLogoPath($companyName)
    {
        $clean = trim($companyName);
        $base = public_path('images/Logo Mitra/');
        $urlBase = asset('images/Logo Mitra/');
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        
        foreach ($extensions as $ext) {
            $filePath = $base . $clean . '.' . $ext;
            if (file_exists($filePath)) {
                return $urlBase . '/' . $clean . '.' . $ext;
            }
        }
        
        // Return a data URI placeholder if logo not found to prevent 404 errors
        return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56"><rect width="56" height="56" fill="#f8f9fa"/><text x="50%" y="50%" font-family="Arial" font-size="12" fill="#6c757d" text-anchor="middle" dominant-baseline="middle">No Logo</text></svg>');
    }

}