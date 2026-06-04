<?php

namespace App\Services;

use App\Models\SpkSawResult;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class SpkSawService
{
    public const THRESHOLD_LAYAK = 0.85;

    public const RECOMMENDATION_LAYAK = 'LAYAK DIBAYAR';

    public const RECOMMENDATION_RETURN = 'KEMBALIKAN KE MITRA';

    /** @var array<string, array{type: string, weight: float, label: string}> */
    private const CRITERIA = [
        'c1' => ['type' => 'benefit', 'weight' => 0.30, 'label' => 'Financial Accuracy'],
        'c2' => ['type' => 'benefit', 'weight' => 0.25, 'label' => 'Legality Completeness'],
        'c3' => ['type' => 'cost', 'weight' => 0.20, 'label' => 'SLA Timeliness'],
        'c4' => ['type' => 'benefit', 'weight' => 0.15, 'label' => 'Attribute Conformity'],
        'c5' => ['type' => 'benefit', 'weight' => 0.10, 'label' => 'Partner History'],
    ];

    /** @var SpkCriteriaMapper */
    private $criteriaMapper;

    public function __construct(SpkCriteriaMapper $criteriaMapper)
    {
        $this->criteriaMapper = $criteriaMapper;
    }

    /**
     * @return array{
     *   scores: array<string, float>,
     *   normalized: array<string, float>,
     *   preference_value: float,
     *   recommendation: string,
     *   criteria_meta: array<string, array{type: string, weight: float, label: string}>
     * }
     */
    public function calculate(Ticket $ticket): array
    {
        $scores = $this->criteriaMapper->map($ticket);
        $normalized = [];
        $preference = 0.0;

        foreach (self::CRITERIA as $key => $meta) {
            $x = max(0.0, min(1.0, (float) ($scores[$key] ?? 0.0)));
            $scores[$key] = $x;

            $r = $this->normalize($x, $meta['type']);
            $normalized[$key] = $r;
            $preference += $meta['weight'] * $r;
        }

        $preference = round($preference, 4);
        $recommendation = $preference >= self::THRESHOLD_LAYAK
            ? self::RECOMMENDATION_LAYAK
            : self::RECOMMENDATION_RETURN;

        return [
            'scores' => $scores,
            'normalized' => $normalized,
            'preference_value' => $preference,
            'recommendation' => $recommendation,
            'criteria_meta' => self::CRITERIA,
        ];
    }

    public function evaluateAndStore(Ticket $ticket): SpkSawResult
    {
        $result = $this->calculate($ticket);

        $record = SpkSawResult::updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'c1_score' => $result['scores']['c1'],
                'c2_score' => $result['scores']['c2'],
                'c3_score' => $result['scores']['c3'],
                'c4_score' => $result['scores']['c4'],
                'c5_score' => $result['scores']['c5'],
                'c1_normalized' => $result['normalized']['c1'],
                'c2_normalized' => $result['normalized']['c2'],
                'c3_normalized' => $result['normalized']['c3'],
                'c4_normalized' => $result['normalized']['c4'],
                'c5_normalized' => $result['normalized']['c5'],
                'preference_value' => $result['preference_value'],
                'recommendation' => $result['recommendation'],
                'calculated_at' => now(),
            ]
        );

        Log::info('[SPK-SAW] Evaluation stored', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'preference_value' => $result['preference_value'],
            'recommendation' => $result['recommendation'],
        ]);

        return $record;
    }

    /**
     * Benefit: R_ij = X_ij / Max(X_j), reference max = 1 for 0–1 scores.
     * Cost: lower raw (delay ratio) is better → R_ij = 1 - X_ij when X in [0,1].
     */
    private function normalize(float $x, string $type): float
    {
        if ($type === 'benefit') {
            return round($x, 4);
        }

        return round(1.0 - $x, 4);
    }
}
