<?php

namespace App\Admin\Controllers\AiControllers;

use \App\Models\Ticket;
use \App\Models\TicketNote;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketNoteController extends Controller
{
    /**
     * Get notes for a specific ticket
     */
    public function getNotes(string $ticketNumber): JsonResponse
    {
        try {
            // Find ticket by ticket_number
            $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();
            
            // Get or create notes for this ticket
            $ticketNote = TicketNote::firstOrCreate(
                ['ticket_id' => $ticket->id],
                ['notes' => []]
            );
            
            return response()->json([
                'success' => true,
                'notes' => $ticketNote->notes ?? []
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }
    }
    
    /**
     * Save notes for a specific ticket
     */
    public function saveNotes(Request $request, string $ticketNumber): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'notes' => 'required|array',
                'notes.mitra' => 'nullable|string',
                'notes.obl' => 'nullable|string',
                'notes.internal_telkom' => 'nullable|string',
                'notes.segmen_witel' => 'nullable|string',
                'notes.revisi_precise' => 'nullable|string',
            ]);
            
            // Find ticket
            $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();
            
            // Update or create notes
            $ticketNote = TicketNote::updateOrCreate(
                ['ticket_id' => $ticket->id],
                ['notes' => $validated['notes']]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Notes saved successfully',
                'notes' => $ticketNote->notes
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save notes: ' . $e->getMessage()
            ], 500);
        }
    }
}