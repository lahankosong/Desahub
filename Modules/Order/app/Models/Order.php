<?php

namespace Modules\Order\app\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Order\app\Events\OrderDibuat;
use Modules\Order\app\Events\OrderDibatalkan;

class Order extends Model
{
    protected $fillable = [
        'outlet_id', 'buyer_type', 'buyer_id', 'total_harga',
        'metode_pembayaran', 'jenis_transaksi', 'metode_pengiriman',
        'alamat_antar', 'catatan', 'status', 'kurir_id',
        'dibuat_pada', 'diambil_pada', 'diantar_pada',
        'selesai_pada', 'dibatalkan_pada',
    ];

    protected function casts(): array
    {
        return [
            'total_harga' => 'float',
            'dibuat_pada' => 'datetime',
            'diambil_pada' => 'datetime',
            'diantar_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'dibatalkan_pada' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function outlet()
    {
        return $this->belongsTo(\Modules\Outlet\app\Models\Outlet::class);
    }

    /**
     * State machine transisi yang diizinkan.
     */
    public static array $validTransitions = [
        'dibuat'         => ['diambil_kurir', 'selesai', 'dibatalkan'],
        'diambil_kurir'  => ['diantar', 'dibatalkan'],
        'diantar'        => ['selesai', 'gagal_kirim'],
        'gagal_kirim'    => ['dibatalkan'],
        'selesai'        => [],
        'dibatalkan'     => [],
    ];

    /**
     * Cek apakah transisi dari status saat ini ke status baru diizinkan.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::$validTransitions[$this->status] ?? [];
        return in_array($newStatus, $allowed, true);
    }

    /**
     * Update status dengan validasi state machine.
     */
    public function transitionTo(string $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \RuntimeException(
                "Transisi tidak diizinkan: {$this->status} -> {$newStatus}"
            );
        }

        $this->status = $newStatus;

        // Set timestamp sesuai status baru
        match ($newStatus) {
            'diambil_kurir' => $this->diambil_pada = now(),
            'diantar'        => $this->diantar_pada = now(),
            'selesai'        => $this->selesai_pada = now(),
            'dibatalkan'     => $this->dibatalkan_pada = now(),
            default          => null,
        };

        $this->save();
    }

    /**
     * Klaim order oleh kurir — UPDATE atomik, cegah rebutan.
     */
    public static function klaimOlehKurir(int $orderId, int $kurirId): bool
    {
        $affected = static::where('id', $orderId)
            ->where('status', 'dibuat')
            ->whereNull('kurir_id')
            ->where('metode_pengiriman', 'diantar_kurir')
            ->update([
                'kurir_id'    => $kurirId,
                'status'      => 'diambil_kurir',
                'diambil_pada' => now(),
            ]);

        return $affected > 0;
    }

    /**
     * Pancarkan OrderDibuat (dipanggil setelah checkout sukses).
     */
    public function emitOrderDibuat(): void
    {
        $items = $this->items->map(fn($item) => [
            'sellable_type' => $item->sellable_type,
            'sellable_id'   => $item->sellable_id,
            'qty'            => $item->qty,
            'harga_satuan'   => $item->harga_satuan,
        ])->toArray();

        OrderDibuat::dispatch(
            $this->id,
            $this->outlet_id,
            $this->buyer_type,
            $this->buyer_id,
            $items,
            $this->total_harga,
            $this->metode_pembayaran,
            $this->dibuat_pada->toDateTimeString()
        );
    }

    /**
     * Pancarkan OrderDibatalkan.
     */
    public function emitOrderDibatalkan(string $dibatalkanOlehType, int $dibatalkanOlehId, string $alasan): void
    {
        OrderDibatalkan::dispatch(
            $this->id,
            $dibatalkanOlehType,
            $dibatalkanOlehId,
            $alasan,
            now()->toDateTimeString()
        );
    }
}