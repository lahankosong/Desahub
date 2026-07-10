import '../api_client.dart';
import '../models/produk.dart';

class ProdukService {
  final ApiClient _client;

  ProdukService(this._client);

  /// List produk per outlet — GET /api/v1/outlets/{outletId}/produk
  Future<List<Produk>> getByOutlet(int outletId, {int page = 1}) async {
    final res = await _client.dio.get('/v1/outlets/$outletId/produk',
        queryParameters: {'page': page});

    final data = res.data;
    final list = (data['data'] ?? data) as List;
    return list.map((json) => Produk.fromJson(json)).toList();
  }

  /// Detail produk — GET /api/v1/produk/{id}
  Future<Produk> getDetail(int id) async {
    final res = await _client.dio.get('/v1/produk/$id');
    final data = res.data is Map ? res.data : res.data['data'];
    return Produk.fromJson(data);
  }

  /// Tambah produk — POST /api/v1/produk
  Future<Produk> create({
    required int outletId,
    required String nama,
    required double harga,
    String satuan = 'pcs',
    String? deskripsi,
    String? barcode,
    int qtyAwal = 0,
  }) async {
    final res = await _client.dio.post('/v1/produk', data: {
      'outlet_id': outletId,
      'nama': nama,
      'harga': harga,
      'satuan': satuan,
      if (deskripsi != null) 'deskripsi': deskripsi,
      if (barcode != null) 'barcode': barcode,
      'qty_awal': qtyAwal,
    });
    return Produk.fromJson(res.data);
  }

  /// Update produk — PUT /api/v1/produk/{id}
  Future<Produk> update(int id, {
    String? nama,
    double? harga,
    String? satuan,
    String? deskripsi,
    String? barcode,
  }) async {
    final Map<String, dynamic> data = {};
    if (nama != null) data['nama'] = nama;
    if (harga != null) data['harga'] = harga;
    if (satuan != null) data['satuan'] = satuan;
    if (deskripsi != null) data['deskripsi'] = deskripsi;
    if (barcode != null) data['barcode'] = barcode;

    final res = await _client.dio.put('/v1/produk/$id', data: data);
    return Produk.fromJson(res.data);
  }

  /// Restock — POST /api/v1/produk/{id}/restock
  Future<void> restock(int id, int qty) async {
    await _client.dio.post('/v1/produk/$id/restock', data: {'qty': qty});
  }
}