/// Produk model — sesuai response dari /api/v1/outlets/{outlet_id}/produk
class Produk {
  final int id;
  final int outletId;
  final String nama;
  final double harga;
  final String satuan;
  final String? deskripsi;
  final String? barcode;

  Produk({
    required this.id,
    required this.outletId,
    required this.nama,
    required this.harga,
    this.satuan = 'pcs',
    this.deskripsi,
    this.barcode,
  });

  factory Produk.fromJson(Map<String, dynamic> json) {
    return Produk(
      id: json['id'],
      outletId: json['outlet_id'],
      nama: json['nama'],
      harga: (json['harga'] as num).toDouble(),
      satuan: json['satuan'] ?? 'pcs',
      deskripsi: json['deskripsi'],
      barcode: json['barcode'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'outlet_id': outletId,
        'nama': nama,
        'harga': harga,
        'satuan': satuan,
        'deskripsi': deskripsi,
        'barcode': barcode,
      };
}