@if ($spkResult)
    @php
        $isLayak = $spkResult->recommendation === 'LAYAK DIBAYAR';
        $criteria = [
            ['key' => 'c1', 'label' => 'C1 — Financial Accuracy (Benefit, 30%)'],
            ['key' => 'c2', 'label' => 'C2 — Legality Completeness (Benefit, 25%)'],
            ['key' => 'c3', 'label' => 'C3 — SLA Timeliness (Cost, 20%)'],
            ['key' => 'c4', 'label' => 'C4 — Attribute Conformity (Benefit, 15%)'],
            ['key' => 'c5', 'label' => 'C5 — Partner History (Benefit, 10%)'],
        ];
    @endphp
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 24px;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Keputusan SPK — Metode SAW</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-3">
                    <thead class="table-light">
                        <tr>
                            <th>Kriteria</th>
                            <th class="text-end">Skor (X)</th>
                            <th class="text-end">Normalisasi (R)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($criteria as $c)
                            <tr>
                                <td>{{ $c['label'] }}</td>
                                <td class="text-end">{{ number_format($spkResult->{$c['key'] . '_score'} ?? 0, 4) }}</td>
                                <td class="text-end">{{ number_format($spkResult->{$c['key'] . '_normalized'} ?? 0, 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap gap-4 align-items-center">
                <div>
                    <span class="text-muted d-block small">Nilai Preferensi (V<sub>i</sub>)</span>
                    <span class="fs-4 fw-bold">{{ number_format($spkResult->preference_value ?? 0, 4) }}</span>
                </div>
                <div>
                    <span class="text-muted d-block small">Rekomendasi</span>
                    <span class="badge fs-6 {{ $isLayak ? 'bg-success' : 'bg-danger' }}">
                        {{ $spkResult->recommendation }}
                    </span>
                </div>
                @if ($spkResult->calculated_at)
                    <div class="text-muted small ms-auto">
                        Dihitung: {{ $spkResult->calculated_at->format('d M Y H:i') }}
                    </div>
                @endif
            </div>
            <p class="text-muted small mb-0 mt-2">
                Ambang batas: V<sub>i</sub> ≥ 0,85 → LAYAK DIBAYAR; di bawah 0,85 → KEMBALIKAN KE MITRA.
            </p>
        </div>
    </div>
@endif
