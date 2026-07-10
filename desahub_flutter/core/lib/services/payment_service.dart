import '../api_client.dart';

class PaymentService {
  final ApiClient _client;

  PaymentService(this._client);

  /// Konfirmasi pembayaran — POST /api/v1/payments/konfirmasi
  /// Untuk COD: sertakan kurirId supaya tercatat di cod_settlements.
  Future<void> konfirmasi({
    required int orderId,
    required String metode,    // 'cod', 'transfer', 'dp', 'qris'
    required double jumlah,
    String status = 'lunas',   // 'lunas' | 'sebagian' (untuk DP)
    int? kurirId,
  }) async {
    await _client.dio.post('/v1/payments/konfirmasi', data: {
      'order_id': orderId,
      'metode': metode,
      'jumlah': jumlah,
      'status': status,
      if (kurirId != null) 'kurir_id': kurirId,
    });
  }
}