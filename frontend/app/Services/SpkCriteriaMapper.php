<?php

namespace App\Services;

use App\Models\AdvanceReviewResult;
use App\Models\GroundTruth;
use App\Models\Ticket;

class SpkCriteriaMapper
{
    private const LEGAL_DOC_TYPES = [
        'KL',
        'SP',
        'WO',
        'NOPES',
        'Kontrak Layanan',
        'Surat Pesanan',
        'Work Order',
        'Nota Pesanan',
    ];

    /**
     * @return array{c1: float, c2: float, c3: float, c4: float, c5: float}
     */
    public function map(Ticket $ticket): array
    {
        $ticket->loadMissing(['typoErrors', 'priceValidations', 'dateValidations', 'company', 'groundTruths']);

        return [
            'c1' => $this->financialAccuracy($ticket),
            'c2' => $this->legalityCompleteness($ticket),
            'c3' => $this->slaTimelinessCost($ticket),
            'c4' => $this->attributeConformity($ticket),
            'c5' => $this->partnerHistory($ticket),
        ];
    }

    private function financialAccuracy(Ticket $ticket): float
    {
        $priceIssues = $ticket->priceValidations()->count();
        $typoIssues = $ticket->typoErrors()->count();
        $penalty = min(1.0, ($priceIssues * 0.15) + ($typoIssues * 0.05));

        return max(0.0, 1.0 - $penalty);
    }

    private function legalityCompleteness(Ticket $ticket): float
    {
        $gt = GroundTruth::where('ticket_id', $ticket->id)
            ->where('doc_type', 'Ground Truth')
            ->first();

        if (!$gt) {
            return 0.0;
        }

        $data = $gt->getDataWithoutMetadata();
        $present = 0;
        $required = 0;

        foreach (self::LEGAL_DOC_TYPES as $docType) {
            $block = $data[$docType] ?? $data[$this->shortKey($docType)] ?? null;
            if ($block === null) {
                continue;
            }
            $required++;
            if (!empty($block['nomor_surat_utama']) || !empty($block['nomor_surat'])) {
                $present++;
            }
        }

        $docScore = $required > 0 ? $present / $required : 0.5;

        $advance = AdvanceReviewResult::whereHas('groundTruth', fn ($q) => $q->where('ticket_id', $ticket->id))->get();
        $legalReviews = $advance->filter(fn ($r) => in_array($r->doc_type, self::LEGAL_DOC_TYPES, true));
        $successRate = $legalReviews->isEmpty()
            ? 0.5
            : $legalReviews->where('status', 'success')->count() / $legalReviews->count();

        return max(0.0, min(1.0, (0.6 * $docScore) + (0.4 * $successRate)));
    }

    /**
     * Cost criterion raw score: delay ratio 0 (on time) → 1 (at SLA cap).
     */
    private function slaTimelinessCost(Ticket $ticket): float
    {
        $slaDaysCap = 30;
        $days = $ticket->created_at ? $ticket->created_at->diffInDays(now()) : 0;
        $days = min($slaDaysCap, max(0, $days));

        return $slaDaysCap > 0 ? $days / $slaDaysCap : 0.0;
    }

    private function attributeConformity(Ticket $ticket): float
    {
        $results = AdvanceReviewResult::whereHas('groundTruth', fn ($q) => $q->where('ticket_id', $ticket->id))->get();
        if ($results->isEmpty()) {
            return 0.5;
        }

        $ok = $results->where('status', 'success')->count();

        return max(0.0, min(1.0, $ok / $results->count()));
    }

    private function partnerHistory(Ticket $ticket): float
    {
        return 0.75;
    }

    private function shortKey(string $docType): string
    {
        $map = [
            'Kontrak Layanan' => 'KL',
            'Surat Pesanan' => 'SP',
            'Work Order' => 'WO',
            'Nota Pesanan' => 'NOPES',
        ];

        return $map[$docType] ?? $docType;
    }
}
