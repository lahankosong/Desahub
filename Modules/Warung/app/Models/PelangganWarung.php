<?php

namespace Modules\Warung\app\Models;

use Illuminate\Database\Eloquent\Model;

class PelangganWarung extends Model
{
    protected $table = 'pelanggan_warung';

    protected $fillable = ['outlet_id', 'nama', 'no_hp', 'catatan'];

    public function outlet()
    {
        return $this->belongsTo(\Modules\Outlet\app\Models\Outlet::class);
    }

    public function piutang()
    {
        return $this->hasMany(Piutang::class);
    }

    /**
     * Total utang yang masih aktif (belum lunas).
     */
    public function totalUtangAktif(): float
    {
        return (float) $this->piutang()->where('status', 'aktif')->sum('sisa');
    }
}