<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pesan extends Model
{
    protected $table = 'pesan';

    protected $fillable = [
        'percakapan_id', 'pengirim_type', 'pengirim_id', 'isi_pesan',
        'dikirim_pada', 'dibaca_pada',
    ];

    protected function casts(): array
    {
        return [
            'dikirim_pada' => 'datetime',
            'dibaca_pada' => 'datetime',
        ];
    }

    public function percakapan(): BelongsTo
    {
        return $this->belongsTo(Percakapan::class, 'percakapan_id');
    }

    /**
     * Tandai pesan ini sudah dibaca.
     */
    public function tandaiDibaca(): void
    {
        if (is_null($this->dibaca_pada)) {
            $this->dibaca_pada = now();
            $this->save();
        }
    }
}