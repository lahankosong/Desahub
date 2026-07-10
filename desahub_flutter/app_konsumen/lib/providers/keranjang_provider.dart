import 'package:flutter/foundation.dart';
import 'package:desahub_core/desahub_core.dart';

/// Item di keranjang — menyimpan produk + qty.
class KeranjangItem {
  final Produk produk;
  int qty;

  KeranjangItem({required this.produk, this.qty = 1});

  double get subtotal => produk.harga * qty;
}

/// Provider keranjang belanja lokal (belum disinkron ke server).
class KeranjangProvider extends ChangeNotifier {
  final List<KeranjangItem> _items = [];
  int? _outletId; // semua item harus dari 1 outlet yang sama

  List<KeranjangItem> get items => List.unmodifiable(_items);
  int? get outletId => _outletId;
  bool get isEmpty => _items.isEmpty;

  double get totalHarga =>
      _items.fold(0, (sum, item) => sum + item.subtotal);

  int get totalItem => _items.fold(0, (sum, item) => sum + item.qty);

  /// Tambah item ke keranjang.
  /// Kalau outlet berbeda, kosongkan dulu (1 transaksi = 1 outlet).
  void tambah(Produk produk, {int qty = 1}) {
    if (_outletId != null && _outletId != produk.outletId) {
      _items.clear();
    }
    _outletId = produk.outletId;

    final existing = _items.cast<KeranjangItem?>().firstWhere(
          (i) => i!.produk.id == produk.id,
          orElse: () => null,
        );
    if (existing != null) {
      existing.qty += qty;
    } else {
      _items.add(KeranjangItem(produk: produk, qty: qty));
    }
    notifyListeners();
  }

  /// Update qty item.
  void updateQty(int produkId, int qty) {
    final item = _items.firstWhere((i) => i.produk.id == produkId);
    item.qty = qty;
    if (item.qty <= 0) {
      _items.remove(item);
    }
    notifyListeners();
  }

  /// Hapus item.
  void hapus(int produkId) {
    _items.removeWhere((i) => i.produk.id == produkId);
    if (_items.isEmpty) _outletId = null;
    notifyListeners();
  }

  /// Kosongkan seluruh keranjang.
  void kosongkan() {
    _items.clear();
    _outletId = null;
    notifyListeners();
  }

  /// Konversi keranjang ke format payload API checkout.
  List<Map<String, dynamic>> toCheckoutItems() {
    return _items.map((item) {
      return {
        'sellable_type': 'Modules\\Warung\\app\\Models\\Produk',
        'sellable_id': item.produk.id,
        'qty': item.qty,
      };
    }).toList();
  }
}