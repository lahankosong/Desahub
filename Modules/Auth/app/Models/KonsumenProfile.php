<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;

class KonsumenProfile extends Model
{
    protected $fillable = ['user_id', 'alamat'];
    public function user() { return $this->belongsTo(User::class); }
}