import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';

class OrderListScreen extends StatefulWidget {
  const OrderListScreen({super.key});

  @override
  State<OrderListScreen> createState() => _OrderListScreenState();
}

class _OrderListScreenState extends State<OrderListScreen> {
  List<Order> _orders = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadOrders();
  }

  Future<void> _loadOrders() async {
    setState(() => _loading = true);
    try {
      final auth = context.read<AuthProvider>();
      final orderService = context.read<OrderService>();
      _orders = await orderService.getOrders(
        buyerId: auth.userId!,
        buyerType: 'Konsumen',
      );
    } catch (_) {
      _orders = [];
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'dibuat':
        return Colors.orange;
      case 'diambil_kurir':
        return Colors.blue;
      case 'diantar':
        return Colors.indigo;
      case 'selesai':
        return Colors.green;
      case 'dibatalkan':
      case 'gagal_kirim':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Pesanan Saya')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _orders.isEmpty
              ? const Center(child: Text('Belum ada pesanan'))
              : RefreshIndicator(
                  onRefresh: _loadOrders,
                  child: ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: _orders.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (_, i) {
                      final o = _orders[i];
                      return ListTile(
                        leading: CircleAvatar(
                          backgroundColor: _statusColor(o.status).withValues(alpha: 0.15),
                          child: Icon(Icons.receipt,
                              color: _statusColor(o.status), size: 20),
                        ),
                        title: Text('Order #${o.id}',
                            style: Theme.of(context).textTheme.titleSmall),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Rp ${o.totalHarga.toStringAsFixed(0)} • ${o.metodePembayaran.toUpperCase()}'),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              margin: const EdgeInsets.only(top: 4),
                              decoration: BoxDecoration(
                                color: _statusColor(o.status).withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                o.statusLabel,
                                style: TextStyle(
                                    color: _statusColor(o.status),
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600),
                              ),
                            ),
                          ],
                        ),
                        trailing: o.status == 'dibuat'
                            ? IconButton(
                                icon: const Icon(Icons.cancel_outlined,
                                    color: Colors.red),
                                tooltip: 'Batalkan',
                                onPressed: () => _batalkan(o),
                              )
                            : const Icon(Icons.chevron_right),
                        onTap: o.status != 'dibuat'
                            ? () => _showDetail(o)
                            : null,
                      );
                    },
                  ),
                ),
    );
  }

  Future<void> _batalkan(Order order) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Batalkan Pesanan'),
        content: Text('Batalkan Order #${order.id}?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Tidak')),
          FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Ya, Batalkan')),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    try {
      final auth = context.read<AuthProvider>();
      final orderService = context.read<OrderService>();
      await orderService.batal(
        orderId: order.id,
        dibatalkanOlehType: 'Konsumen',
        dibatalkanOlehId: auth.userId!,
        alasan: 'Dibatalkan oleh konsumen',
      );
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Pesanan dibatalkan'), backgroundColor: Colors.orange),
      );
      _loadOrders();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _showDetail(Order order) {
    showModalBottomSheet(
      context: context,
      builder: (_) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Order #${order.id}',
                style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 16),
            _detailRow('Status', order.statusLabel),
            _detailRow('Metode', order.metodePembayaran.toUpperCase()),
            _detailRow('Total', 'Rp ${order.totalHarga.toStringAsFixed(0)}'),
            _detailRow('Dibuat', order.dibuatPada ?? '-'),
            if (order.diambilPada != null)
              _detailRow('Diambil Kurir', order.diambilPada!),
            if (order.diantarPada != null)
              _detailRow('Diantar', order.diantarPada!),
            if (order.selesaiPada != null)
              _detailRow('Selesai', order.selesaiPada!),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _detailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}