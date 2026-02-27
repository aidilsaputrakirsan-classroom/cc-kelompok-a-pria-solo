<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoDraftObl extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'auto_draft_obls';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'auto_draft_id',
        'obl_index',
        'layanan',
        'terms_of_payment',
        'date_start',
        'date_end',
        'durasi',
        // Mitra 1
        'mitra_1_nama',
        'mitra_1_alamat',
        'mitra_1_nomor_sph',
        'mitra_1_tanggal_sph',
        'mitra_1_harga_bulanan',
        'mitra_1_harga_otc',
        'mitra_1_harga_total',
        // Mitra 2
        'mitra_2_nama',
        'mitra_2_alamat',
        'mitra_2_nomor_sph',
        'mitra_2_tanggal_sph',
        'mitra_2_harga_bulanan',
        'mitra_2_harga_otc',
        'mitra_2_harga_total',
        'is_tender',
        // P2
        'date_p2',
        // P3
        'nomor_p3_mitra_1',
        'date_p3_mitra_1',
        'nomor_p3_mitra_2',
        'date_p3_mitra_2',
        // P4
        'date_p4',
        'target_delivery',
        'skema_bisnis',
        'slg',
        // P5
        'date_p5',
        // P6
        'date_p6',
        'mitra_final',
        'harga_bulanan_final',
        'harga_otc_final',
        'harga_total_final',
        // P7
        'nomor_p7',
        'date_p7',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'layanan' => 'array',
        'date_start' => 'date',
        'date_end' => 'date',
        'mitra_1_tanggal_sph' => 'date',
        'mitra_2_tanggal_sph' => 'date',
        'mitra_1_harga_bulanan' => 'decimal:2',
        'mitra_1_harga_otc' => 'decimal:2',
        'mitra_1_harga_total' => 'decimal:2',
        'mitra_2_harga_bulanan' => 'decimal:2',
        'mitra_2_harga_otc' => 'decimal:2',
        'mitra_2_harga_total' => 'decimal:2',
        'is_tender' => 'boolean',
        'date_p2' => 'date',
        'date_p3_mitra_1' => 'date',
        'date_p3_mitra_2' => 'date',
        'date_p4' => 'date',
        'target_delivery' => 'date',
        'slg' => 'decimal:2',
        'date_p5' => 'date',
        'date_p6' => 'date',
        'harga_bulanan_final' => 'decimal:2',
        'harga_otc_final' => 'decimal:2',
        'harga_total_final' => 'decimal:2',
        'date_p7' => 'date',
    ];

    /**
     * Get the auto draft that owns this OBL.
     *
     * @return BelongsTo
     */
    public function autoDraft(): BelongsTo
    {
        return $this->belongsTo(AutoDraft::class, 'auto_draft_id');
    }

    /**
     * Get the OBL ID string (e.g., "RSO-001_OBL_1").
     *
     * @return string
     */
    public function getOblIdAttribute(): string
    {
        $idRso = $this->autoDraft ? $this->autoDraft->id_rso : 'DRAFT';
        return "{$idRso}_OBL_{$this->obl_index}";
    }

    /**
     * Check if this OBL is in tender mode.
     *
     * @return bool
     */
    public function isTenderMode(): bool
    {
        return $this->is_tender || !empty($this->mitra_2_nama);
    }

    /**
     * Get the winning mitra data.
     *
     * @return array
     */
    public function getWinningMitra(): array
    {
        if ($this->mitra_final === 'mitra_2' && !empty($this->mitra_2_nama)) {
            return [
                'nama' => $this->mitra_2_nama,
                'alamat' => $this->mitra_2_alamat,
                'nomor_sph' => $this->mitra_2_nomor_sph,
                'tanggal_sph' => $this->mitra_2_tanggal_sph,
                'harga_bulanan' => $this->mitra_2_harga_bulanan,
                'harga_otc' => $this->mitra_2_harga_otc,
                'harga_total' => $this->mitra_2_harga_total,
            ];
        }

        return [
            'nama' => $this->mitra_1_nama,
            'alamat' => $this->mitra_1_alamat,
            'nomor_sph' => $this->mitra_1_nomor_sph,
            'tanggal_sph' => $this->mitra_1_tanggal_sph,
            'harga_bulanan' => $this->mitra_1_harga_bulanan,
            'harga_otc' => $this->mitra_1_harga_otc,
            'harga_total' => $this->mitra_1_harga_total,
        ];
    }

    /**
     * Helper to format date or return null.
     *
     * @param mixed $date
     * @param string $format
     * @return string|null
     */
    private function formatDateOrNull($date, $format = 'Y-m-d')
    {
        return $date ? $date->format($format) : null;
    }

    /**
     * Convert to the format expected by AutoDraftingController document generator.
     *
     * @return array
     */
    public function toGeneratorFormat(): array
    {
        $hasMitra2 = !empty($this->mitra_2_nama);

        return [
            'obl_index' => $this->obl_index,
            'layanan' => $this->layanan ?? [],
            'mitra_1' => [
                'nama' => $this->mitra_1_nama,
                'alamat' => $this->mitra_1_alamat,
                'nomor_sph' => $this->mitra_1_nomor_sph,
                'tanggal_sph' => $this->formatDateOrNull($this->mitra_1_tanggal_sph),
                'harga' => (int) $this->mitra_1_harga_total,
                'harga_bulanan' => (int) $this->mitra_1_harga_bulanan,
                'harga_otc' => (int) $this->mitra_1_harga_otc,
            ],
            'mitra_2' => $hasMitra2 ? [
                'nama' => $this->mitra_2_nama,
                'alamat' => $this->mitra_2_alamat,
                'nomor_sph' => $this->mitra_2_nomor_sph,
                'tanggal_sph' => $this->formatDateOrNull($this->mitra_2_tanggal_sph),
                'harga' => (int) $this->mitra_2_harga_total,
                'harga_bulanan' => (int) $this->mitra_2_harga_bulanan,
                'harga_otc' => (int) $this->mitra_2_harga_otc,
            ] : null,
            'p2' => [
                'tanggal' => $this->formatDateOrNull($this->date_p2),
            ],
            'p3' => [
                'mitra_1' => [
                    'nomor' => $this->nomor_p3_mitra_1,
                    'tanggal' => $this->formatDateOrNull($this->date_p3_mitra_1),
                ],
                'mitra_2' => $hasMitra2 ? [
                    'nomor' => $this->nomor_p3_mitra_2,
                    'tanggal' => $this->formatDateOrNull($this->date_p3_mitra_2),
                ] : null,
            ],
            'p4' => [
                'tanggal' => $this->formatDateOrNull($this->date_p4),
                'target' => $this->formatDateOrNull($this->target_delivery),
                'top' => $this->terms_of_payment,
                'start' => $this->formatDateOrNull($this->date_start),
                'end' => $this->formatDateOrNull($this->date_end),
                'durasi' => $this->durasi,
                'skema' => $this->skema_bisnis,
                'slg' => $this->slg,
            ],
            'p5' => [
                'tanggal' => $this->formatDateOrNull($this->date_p5),
                'terbilang' => '',
                'mode' => $hasMitra2 ? 'Tender' : 'Non-Tender',
            ],
            'p6' => [
                'harga_bulanan' => (int) $this->harga_bulanan_final,
                'harga_otc' => (int) $this->harga_otc_final,
                'harga_total' => (int) $this->harga_total_final,
                'skema' => $this->skema_bisnis,
                'delivery' => $this->formatDateOrNull($this->date_p6),
                'slg' => $this->slg,
                'tanggal' => $this->formatDateOrNull($this->date_p6),
                'mitra_final' => $this->mitra_final,
            ],
            'p7' => [
                'nomor' => $this->nomor_p7,
                'tanggal' => $this->formatDateOrNull($this->date_p7),
            ],
        ];
    }

    /**
     * Helper to convert empty strings to null (for ENUM fields).
     *
     * @param mixed $value
     * @return mixed
     */
    private static function nullIfEmpty($value)
    {
        return (is_string($value) && trim($value) === '') ? null : $value;
    }

    /**
     * Helper to convert empty date strings to null.
     *
     * @param mixed $value
     * @return mixed
     */
    private static function nullIfEmptyDate($value)
    {
        return (empty($value) || $value === '') ? null : $value;
    }

    /**
     * Create an OBL from form data.
     *
     * @param int $autoDraftId
     * @param int $oblIndex
     * @param array $data
     * @return self
     */
    public static function createFromFormData(int $autoDraftId, int $oblIndex, array $data): self
    {
        $hasMitra2 = !empty(data_get($data, 'mitra_2.nama'));

        // Validate mitra_final - must be 'mitra_1', 'mitra_2', or null
        $mitraFinal = data_get($data, 'p6.mitra_final');
        if (!in_array($mitraFinal, ['mitra_1', 'mitra_2'], true)) {
            $mitraFinal = null;
        }

        // Validate skema_bisnis - must be valid enum value
        $skemaBisnis = data_get($data, 'p4.skema', 'Sewa Murni');
        if (!in_array($skemaBisnis, ['Sewa Murni', 'Beli Putus'], true)) {
            $skemaBisnis = 'Sewa Murni';
        }

        // Validate terms_of_payment
        $termsOfPayment = data_get($data, 'p4.top', 'Bulanan');
        $validTerms = ['Bulanan', 'OTC', 'Termin', 'Bulanan dan OTC', 'Bulanan dan Termin'];
        if (!in_array($termsOfPayment, $validTerms, true)) {
            $termsOfPayment = 'Bulanan';
        }

        return static::create([
            'auto_draft_id' => $autoDraftId,
            'obl_index' => $oblIndex,
            'layanan' => $data['layanan'] ?? [],
            'terms_of_payment' => $termsOfPayment,
            'date_start' => self::nullIfEmptyDate(data_get($data, 'p4.start')),
            'date_end' => self::nullIfEmptyDate(data_get($data, 'p4.end')),
            'durasi' => self::nullIfEmpty(data_get($data, 'p4.durasi')),
            // Mitra 1
            'mitra_1_nama' => self::nullIfEmpty(data_get($data, 'mitra_1.nama')),
            'mitra_1_alamat' => self::nullIfEmpty(data_get($data, 'mitra_1.alamat')),
            'mitra_1_nomor_sph' => self::nullIfEmpty(data_get($data, 'mitra_1.nomor_sph')),
            'mitra_1_tanggal_sph' => self::nullIfEmptyDate(data_get($data, 'mitra_1.tanggal_sph')),
            'mitra_1_harga_bulanan' => data_get($data, 'mitra_1.harga_bulanan', 0) ?: 0,
            'mitra_1_harga_otc' => data_get($data, 'mitra_1.harga_otc', 0) ?: 0,
            'mitra_1_harga_total' => data_get($data, 'mitra_1.harga', 0) ?: 0,
            // Mitra 2
            'mitra_2_nama' => self::nullIfEmpty(data_get($data, 'mitra_2.nama')),
            'mitra_2_alamat' => self::nullIfEmpty(data_get($data, 'mitra_2.alamat')),
            'mitra_2_nomor_sph' => self::nullIfEmpty(data_get($data, 'mitra_2.nomor_sph')),
            'mitra_2_tanggal_sph' => self::nullIfEmptyDate(data_get($data, 'mitra_2.tanggal_sph')),
            'mitra_2_harga_bulanan' => data_get($data, 'mitra_2.harga_bulanan', 0) ?: 0,
            'mitra_2_harga_otc' => data_get($data, 'mitra_2.harga_otc', 0) ?: 0,
            'mitra_2_harga_total' => data_get($data, 'mitra_2.harga', 0) ?: 0,
            'is_tender' => $hasMitra2,
            // P2
            'date_p2' => self::nullIfEmptyDate(data_get($data, 'p2.tanggal')),
            // P3
            'nomor_p3_mitra_1' => self::nullIfEmpty(data_get($data, 'p3.mitra_1.nomor')),
            'date_p3_mitra_1' => self::nullIfEmptyDate(data_get($data, 'p3.mitra_1.tanggal')),
            'nomor_p3_mitra_2' => self::nullIfEmpty(data_get($data, 'p3.mitra_2.nomor')),
            'date_p3_mitra_2' => self::nullIfEmptyDate(data_get($data, 'p3.mitra_2.tanggal')),
            // P4
            'date_p4' => self::nullIfEmptyDate(data_get($data, 'p4.tanggal')),
            'target_delivery' => self::nullIfEmptyDate(data_get($data, 'p4.target')),
            'skema_bisnis' => $skemaBisnis,
            'slg' => self::nullIfEmpty(data_get($data, 'p4.slg')),
            // P5
            'date_p5' => self::nullIfEmptyDate(data_get($data, 'p5.tanggal')),
            // P6
            'date_p6' => self::nullIfEmptyDate(data_get($data, 'p6.tanggal')),
            'mitra_final' => $mitraFinal,
            'harga_bulanan_final' => data_get($data, 'p6.harga_bulanan', 0) ?: 0,
            'harga_otc_final' => data_get($data, 'p6.harga_otc', 0) ?: 0,
            'harga_total_final' => data_get($data, 'p6.harga_total', 0) ?: 0,
            // P7
            'nomor_p7' => self::nullIfEmpty(data_get($data, 'p7.nomor')),
            'date_p7' => self::nullIfEmptyDate(data_get($data, 'p7.tanggal')),
        ]);
    }
}
