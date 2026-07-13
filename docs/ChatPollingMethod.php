<?php
// Tambahkan method ini ke ChatWebController yang sudah ada
// Letakkan setelah method kirim()

/**
 * Polling pesan baru — GET /warung/chat/{id}/polling?after={last_id}
 * Dipakai oleh auto-polling JS di chat.blade.php (interval 8 detik)
 * Mengembalikan hanya pesan yang id-nya lebih besar dari 'after'
 */
public function polling(Request $request, $id)
{
    $percakapan = \Modules\Chat\app\Models\Percakapan::findOrFail($id);

    // Pastikan outlet ini yang punya percakapan ini
    $outlet = \Modules\Outlet\app\Models\Outlet::where('owner_user_id', Auth::id())->first();
    if (! $outlet || $percakapan->outlet_id !== $outlet->id) {
        return response()->json(['pesan' => []]);
    }

    $afterId = (int) $request->query('after', 0);

    $pesan = $percakapan->pesan()
        ->where('id', '>', $afterId)
        ->orderBy('id')
        ->get()
        ->map(fn($msg) => [
            'id'            => $msg->id,
            'pengirim_type' => $msg->pengirim_type,
            'isi_pesan'     => $msg->isi_pesan,
            'waktu'         => $msg->dikirim_pada
                ? $msg->dikirim_pada->format('H:i')
                : $msg->created_at->format('H:i'),
        ]);

    // Tandai pesan dari Konsumen sebagai sudah dibaca
    if ($pesan->where('pengirim_type', 'Konsumen')->isNotEmpty()) {
        $percakapan->pesan()
            ->where('pengirim_type', 'Konsumen')
            ->whereNull('dibaca_pada')
            ->update(['dibaca_pada' => now()]);
    }

    return response()->json(['pesan' => $pesan]);
}

// ─── Tambahkan route ini di routes/web.php ──────────────────────
// Route::get('chat/{id}/polling', [ChatWebController::class, 'polling'])->name('warung.chat.polling');
