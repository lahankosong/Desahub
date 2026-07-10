import '../api_client.dart';
import '../models/order.dart';

class KurirService {
  final ApiClient _client;

  KurirService(this._client);

  /// Set online/offline — POST /api/v1/kurir/online
  Future<bool> toggleOnline({
    required bool online,
    double? lat,
    double? lng,
  }) async {
    final res = await _client.dio.post('/v1/kurir/online', data: {
      'online': online,
      if (lat != null) 'lat': lat,
      if (lng != null) 'lng': lng,
    });
    return res.data['is_online'] ?? false;
  }

  /// Order yang bisa diklaim — GET /api/v1/kurir/available-orders
  Future<List<Order>> availableOrders({int page = 1}) async {
    final res = await _client.dio.get('/v1/kurir/available-orders',
        queryParameters: {'page': page});
    final data = res.data;
    final list = (data['data'] ?? data) as List;
    return list.map((json) => Order.fromJson(json)).toList();
  }

  /// Klaim order (atomik) — POST /api/v1/kurir/orders/{id}/klaim
  /// Returns null jika gagal (order sudah diklaim kurir lain).
  Future<Order?> klaimOrder(int orderId) async {
    try {
      final res = await _client.dio.post('/v1/kurir/orders/$orderId/klaim');
      return Order.fromJson(res.data);
    } on Exception {
      return null;
    }
  }

  /// Update status antar — POST /api/v1/kurir/orders/{id}/update-status
  /// status: 'diambil_kurir' | 'diantar' | 'selesai' | 'gagal_kirim'
  Future<Order> updateStatus(int orderId, String status) async {
    final res = await _client.dio
        .post('/v1/kurir/orders/$orderId/update-status', data: {
      'status': status,
    });
    return Order.fromJson(res.data);
  }

  /// Order yang sedang/sudah saya tangani — GET /api/v1/kurir/my-orders
  Future<List<Order>> myOrders({int page = 1}) async {
    final res = await _client.dio.get('/v1/kurir/my-orders',
        queryParameters: {'page': page});
    final data = res.data;
    final list = (data['data'] ?? data) as List;
    return list.map((json) => Order.fromJson(json)).toList();
  }
}