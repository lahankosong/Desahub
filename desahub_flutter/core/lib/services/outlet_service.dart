import '../api_client.dart';
import '../models/outlet.dart';

class OutletService {
  final ApiClient _client;

  OutletService(this._client);

  /// Cari outlet dalam radius — GET /api/v1/outlets?lat=&lng=&radius_km=
  Future<List<Outlet>> cariOutlet({
    required double lat,
    required double lng,
    double radiusKm = 1.0,
    int page = 1,
  }) async {
    final res = await _client.dio.get('/v1/outlets', queryParameters: {
      'lat': lat,
      'lng': lng,
      'radius_km': radiusKm,
      'page': page,
    });

    final data = res.data;
    final list = (data['data'] ?? data) as List;
    return list.map((json) => Outlet.fromJson(json)).toList();
  }

  /// Detail outlet — GET /api/v1/outlets/{id}
  Future<Outlet> getDetail(int id) async {
    final res = await _client.dio.get('/v1/outlets/$id');
    // Backend returns single object directly (not wrapped)
    return Outlet.fromJson(res.data is Map ? res.data : res.data['data']);
  }
}