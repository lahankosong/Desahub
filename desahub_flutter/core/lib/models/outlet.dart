/// Outlet model — sesuai response dari /api/v1/outlets dan /api/v1/outlets/{id}
class Outlet {
  final int id;
  final String nama;
  final double lat;
  final double lng;
  final String? alamat;
  final String levelVerifikasi;
  final double? jarakKm;
  final List<String>? vertikals;

  Outlet({
    required this.id,
    required this.nama,
    required this.lat,
    required this.lng,
    this.alamat,
    this.levelVerifikasi = 'dasar',
    this.jarakKm,
    this.vertikals,
  });

  factory Outlet.fromJson(Map<String, dynamic> json) {
    return Outlet(
      id: json['id'],
      nama: json['nama'],
      lat: (json['lat'] as num).toDouble(),
      lng: (json['lng'] as num).toDouble(),
      alamat: json['alamat'],
      levelVerifikasi: json['level_verifikasi'] ?? 'dasar',
      jarakKm: json['jarak_km'] != null
          ? (json['jarak_km'] as num).toDouble()
          : null,
      vertikals: json['vertikals'] != null
          ? List<String>.from(json['vertikals'])
          : null,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'nama': nama,
        'lat': lat,
        'lng': lng,
        'alamat': alamat,
        'level_verifikasi': levelVerifikasi,
      };
}