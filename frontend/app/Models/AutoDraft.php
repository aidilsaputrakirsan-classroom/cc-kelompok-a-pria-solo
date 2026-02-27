<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutoDraft extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'auto_drafts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_rso',
        'judul_p1',
        'nomor_p1',
        'tanggal_p1',
        'pelanggan',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_p1' => 'date',
    ];

    /**
     * Get all OBLs associated with this auto draft.
     *
     * @return HasMany
     */
    public function obls(): HasMany
    {
        return $this->hasMany(AutoDraftObl::class, 'auto_draft_id')->orderBy('obl_index');
    }

    /**
     * Get the full data structure for document generation.
     * This returns the data in the format expected by the AutoDraftingController.
     *
     * @return array
     */
    public function toGeneratorFormat(): array
    {
        return [
            'general' => [
                'id_rso' => $this->id_rso,
                'judul_p1' => $this->judul_p1,
                'nomor_p1' => $this->nomor_p1,
                'tanggal_p1' => $this->tanggal_p1 ? $this->tanggal_p1->format('Y-m-d') : null,
                'pelanggan' => $this->pelanggan,
            ],
            'obl' => $this->obls->map(function ($obl) {
                return $obl->toGeneratorFormat();
            })->toArray(),
        ];
    }

    /**
     * Create or update draft from the localStorage format.
     *
     * @param array $data
     * @return self
     */
    public static function createFromFormData(array $data): self
    {
        $general = $data['general'] ?? [];
        $idRso = $general['id_rso'] ?? null;

        if (!$idRso) {
            throw new \InvalidArgumentException('id_rso is required');
        }

        $draft = static::updateOrCreate(
            ['id_rso' => $idRso],
            [
                'judul_p1' => $general['judul_p1'] ?? null,
                'nomor_p1' => $general['nomor_p1'] ?? null,
                'tanggal_p1' => $general['tanggal_p1'] ?? null,
                'pelanggan' => $general['pelanggan'] ?? null,
            ]
        );

        // Sync OBLs
        if (isset($data['obl']) && is_array($data['obl'])) {
            // Delete existing OBLs and recreate
            $draft->obls()->delete();

            foreach ($data['obl'] as $index => $oblData) {
                AutoDraftObl::createFromFormData($draft->id, $index + 1, $oblData);
            }
        }

        return $draft->fresh(['obls']);
    }
}
