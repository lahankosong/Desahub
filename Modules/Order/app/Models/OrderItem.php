<?php

namespace Modules\Order\app\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'sellable_type', 'sellable_id', 'qty', 'harga_satuan',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_satuan' => 'float',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}