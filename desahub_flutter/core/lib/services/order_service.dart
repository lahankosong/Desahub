import '../api_client.dart';
import '../models/order.dart';

class OrderService {
  final ApiClient _client;

  OrderService(this._client);

  /// List order — GET /api/v1/orders?outlet_id= atau ?buyer_id=&buyer_type=
  Future<List<Order>> getOrders({
    int? outletId,
    int? buyerId,
    String? buyerType,
    int page = 1,
  }) async {
    final params = <String, dynamic>{'page': page};
    if (outletId != null) params['outlet_id'] = outletId;
    if (buyerId != null && buyerType != null) {
      params['buyer_id'] = buyerId;
      params['buyer_type'] = buyerType;
    }

    final res = await _client.dio.get('/v1/orders', queryParameters: params);
    final data = res.data;
    final list = (data['data'] ?? data) as List;
    return list.map((json) => Order.fromJson(json)).toList();
  }

  /// Detail order — GET /api/v1/orders/{id}
  Future<Order> getDetail(int id) async {
    final res = await _client.dio.get('/v1/orders/$id');
    return Order.fromJson(res.data);
  }

  /// Checkout — POST /api/v1/orders
  /// items: [{sellable_type, sellable_id, qty}]
  Future<Order> checkout({
    required int outletId,
    required String buyerType,
    required int buyerId,
    required List<Map<String, dynamic>> items,
    String metodePembayaran = 'cod',
  }) async {
    final res = await _client.dio.post('/v1/orders', data: {
      'outlet_id': outletId,
      'buyer_type': buyerType,
      'buyer_id': buyerId,
      'items': items,
      'metode_pembayaran': metodePembayaran,
    });
    return Order.fromJson(res.data);
  }

  /// Batalkan order — POST /api/v1/orders/{id}/batal
  Future<Order> batal({
    required int orderId,
    required String dibatalkanOlehType,
    required int dibatalkanOlehId,
    required String alasan,
  }) async {
    final res = await _client.dio.post('/v1/orders/$orderId/batal', data: {
      'dibatalkan_oleh_type': dibatalkanOlehType,
      'dibatalkan_oleh_id': dibatalkanOlehId,
      'alasan': alasan,
    });
    return Order.fromJson(res.data);
  }
}