import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';

class MyOrdersScreen extends StatefulWidget {
  const MyOrdersScreen({super.key});

  @override
  State<MyOrdersScreen> createState() => _MyOrdersScreenState();
}

class _MyOrdersScreenState extends State<MyOrdersScreen> {
  List<Order> _orders = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      _orders = await context.read<KurirService>().myOrders();
    } catch (_) {
      _orders = [];
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _updateStatus(Order order) async {
    String? nextStatus;
    switch (order.status) {
      case 'diambil_kurir':
        nextStatus = 'diantar';
        break;
      case 'diantar':
        nextStatus = 'selesai';
        break;
      default:
        return;
    }

    try {
      await context.read<KurirService>().updateStatus(order.id, nextStatus);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Status: ${order.statusLabel}'), backgroundColor: Colors.green),
        );
      }
      _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_orders.isEmpty) {
      return const Center(child: Text('Belum ada order'));
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _orders.length,
        separatorBuilder: (_, __) => const Divider(height: 1),
        itemBuilder: (_, i) {
          final o = _orders[i];
          final canAdvance = o.status == 'diambil_kurir' || o.status == 'diantar';

          return Card(
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: (o.status == 'diambil_kurir' || o.status == 'diantar')
                    ? Colors.blue.withValues(alpha: 0.15)
                    : Colors.green.withValues(alpha: 0.15),
                child: Icon(
                  o.status == 'selesai' ? Icons.check_circle : Icons.local_shipping,
                  color: o.status == 'selesai' ? Colors.green : Colors.blue,
                  size: 20,
                ),
              ),
              title: Text(o.outlet?.nama ?? 'Order #${o.id}'),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Rp ${o.totalHarga.toStringAsFixed(0)} • COD'),
                  Text(o.statusLabel,
                      style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                ],
              ),
              trailing: canAdvance
                  ? FilledButton.tonal(
                      onPressed: () => _updateStatus(o),
                      child: Text(o.status == 'diambil_kurir' ? 'Diambil' : 'Selesai'),
                    )
                  : null,
            ),
          );
        },
      ),
    );
  }
}