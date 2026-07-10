import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';
import '../providers/keranjang_provider.dart';

class KeranjangScreen extends StatelessWidget {
  const KeranjangScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final keranjang = context.watch<KeranjangProvider>();
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Keranjang')),
      body: keranjang.isEmpty
          ? const Center(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
              Icon(Icons.shopping_cart_outlined, size: 64, color: Colors.grey),
              SizedBox(height: 16),
              Text('Keranjang kosong', style: TextStyle(color: Colors.grey)),
              Text('Cari outlet terdekat dan tambah produk',
                  style: TextStyle(color: Colors.grey, fontSize: 12)),
            ]))
          : Column(
              children: [
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: keranjang.items.length,
                    itemBuilder: (_, i) {
                      final item = keranjang.items[i];
                      final p = item.produk;
                      return Card(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(p.nama,
                                        style: Theme.of(context).textTheme.titleSmall),
                                    Text(
                                        'Rp ${p.harga.toStringAsFixed(0)} / ${p.satuan}'),
                                    Text('Subtotal: Rp ${item.subtotal.toStringAsFixed(0)}',
                                        style: TextStyle(
                                            color: Theme.of(context).colorScheme.primary,
                                            fontWeight: FontWeight.w600)),
                                  ],
                                ),
                              ),
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  IconButton(
                                    icon: const Icon(Icons.remove_circle_outline),
                                    onPressed: () => keranjang.updateQty(p.id, item.qty - 1),
                                  ),
                                  Text('${item.qty}',
                                      style: Theme.of(context).textTheme.titleMedium),
                                  IconButton(
                                    icon: const Icon(Icons.add_circle_outline),
                                    onPressed: () => keranjang.tambah(p),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ),
                SafeArea(
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surfaceContainerHighest,
                      borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                    ),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Total',
                                style: Theme.of(context).textTheme.titleMedium),
                            Text('Rp ${keranjang.totalHarga.toStringAsFixed(0)}',
                                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                    color: Theme.of(context).colorScheme.primary,
                                    fontWeight: FontWeight.bold)),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text('Pembayaran: COD (Tunai saat terima)',
                            style: Theme.of(context).textTheme.bodySmall),
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          height: 48,
                          child: FilledButton(
                            onPressed: () => _checkout(context, keranjang, auth),
                            child: const Text('Checkout COD'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
    );
  }

  Future<void> _checkout(
      BuildContext ctx, KeranjangProvider keranjang, AuthProvider auth) async {
    final confirmed = await showDialog<bool>(
      context: ctx,
      builder: (_) => AlertDialog(
        title: const Text('Konfirmasi Checkout'),
        content: Text(
            'Total: Rp ${keranjang.totalHarga.toStringAsFixed(0)}\nMetode: COD\n\nLanjutkan?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Ya')),
        ],
      ),
    );

    if (confirmed != true || !ctx.mounted) return;

    try {
      final orderService = ctx.read<OrderService>();
      await orderService.checkout(
        outletId: keranjang.outletId!,
        buyerType: 'Konsumen',
        buyerId: auth.userId!,
        items: keranjang.toCheckoutItems(),
        metodePembayaran: 'cod',
      );

      keranjang.kosongkan();
      if (ctx.mounted) {
        ScaffoldMessenger.of(ctx).showSnackBar(
          const SnackBar(content: Text('Order berhasil dibuat!'), backgroundColor: Colors.green),
        );
      }
    } catch (e) {
      if (ctx.mounted) {
        ScaffoldMessenger.of(ctx).showSnackBar(
          SnackBar(content: Text('Gagal: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}