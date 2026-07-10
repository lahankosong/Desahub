/// Order model — sesuai response dari /api/v1/orders dan /api/v1/orders/{id}
class Order {
  final int id;
  final int outletId;
  final String buyerType;
  final int buyerId;
  final double totalHarga;
  final String metodePembayaran;
  final String status;
  final int? kurirId;
  final String? dibuatPada;
  final String? diambilPada;
  final String? diantarPada;
  final String? selesaiPada;
  final String? dibatalkanPada;
  final List<OrderItem>? items;
  final OutletData? outlet;

  Order({
    required this.id,
    required this.outletId,
    required this.buyerType,
    required this.buyerId,
    required this.totalHarga,
    required this.metodePembayaran,
    required this.status,
    this.kurirId,
    this.dibuatPada,
    this.diambilPada,
    this.diantarPada,
    this.selesaiPada,
    this.dibatalkanPada,
    this.items,
    this.outlet,
  });

  factory Order.fromJson(Map<String, dynamic> json) {
    return Order(
      id: json['id'],
      outletId: json['outlet_id'],
      buyerType: json['buyer_type'],
      buyerId: json['buyer_id'],
      totalHarga: (json['total_harga'] as num).toDouble(),
      metodePembayaran: json['metode_pembayaran'],
      status: json['status'],
      kurirId: json['kurir_id'],
      dibuatPada: json['dibuat_pada'],
      diambilPada: json['diambil_pada'],
      diantarPada: json['diantar_pada'],
      selesaiPada: json['selesai_pada'],
      dibatalkanPada: json['dibatalkan_pada'],
      items: json['items'] != null
          ? (json['items'] as List).map((i) => OrderItem.fromJson(i)).toList()
          : null,
      outlet: json['outlet'] != null
          ? OutletData.fromJson(json['outlet'])
          : null,
    );
  }

  /// Status label yang bisa ditampilkan di UI.
  String get statusLabel {
    switch (status) {
      case 'dibuat':
        return 'Menunggu Kurir';
      case 'diambil_kurir':
        return 'Diambil Kurir';
      case 'diantar':
        return 'Sedang Diantar';
      case 'selesai':
        return 'Selesai';
      case 'dibatalkan':
        return 'Dibatalkan';
      case 'gagal_kirim':
        return 'Gagal Kirim';
      default:
        return status;
    }
  }
}

class OrderItem {
  final int id;
  final String sellableType;
  final int sellableId;
  final int qty;
  final double hargaSatuan;

  OrderItem({
    required this.id,
    required this.sellableType,
    required this.sellableId,
    required this.qty,
    required this.hargaSatuan,
  });

  factory OrderItem.fromJson(Map<String, dynamic> json) {
    return OrderItem(
      id: json['id'],
      sellableType: json['sellable_type'],
      sellableId: json['sellable_id'],
      qty: json['qty'],
      hargaSatuan: (json['harga_satuan'] as num).toDouble(),
    );
  }

  double get subtotal => hargaSatuan * qty;
}

/// Data outlet ringkas yang disertakan di response order (untuk Kurir).
class OutletData {
  final int id;
  final String nama;
  final String? alamat;

  OutletData({required this.id, required this.nama, this.alamat});

  factory OutletData.fromJson(Map<String, dynamic> json) {
    return OutletData(
      id: json['id'],
      nama: json['nama'],
      alamat: json['alamat'],
    );
  }
}