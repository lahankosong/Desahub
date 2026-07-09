<?php

namespace Modules\Outlet\app\Models;

use Illuminate\Database\Eloquent\Model;

class OutletVertikal extends Model
{
    protected $table = 'outlet_vertikal';

    protected $fillable = ['outlet_id', 'vertikal', 'status', 'aktif_sejak'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}