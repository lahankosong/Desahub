import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';

class AvailableOrdersScreen extends StatefulWidget {
  const AvailableOrdersScreen({super.key});

  @override
  State<AvailableOrdersScreen> createState() => _AvailableOrdersScreenState();
}

class _AvailableOrdersScreenState extends State<AvailableOrdersScreen> {
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
      _orders = await context.read<KurirService>().availableOrders();
    } catch (_) {
      _orders = [];
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _klaim(int orderId) async {
    final order = await context.read<KurirService>().klaimOrder(orderId);
    if (!mounted) return;
    if (order != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Order #${order.id} berhasil diklaim!'), backgroundColor: Colors.green),
      );
      _load();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Order sudah diklaim kurir lain'), backgroundColor: Colors.orange),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_orders.isEmpty) {
      return const Center(child: Text('Tidak ada order tersedia'));
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _orders.length,
        separatorBuilder: (_, __) => const Divider(height: 1),
        itemBuilder: (_, i) {
          final o = _orders[i];
          return Card(
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: Colors.orange.withValues(alpha: 0.15),
                child: const Icon(Icons.new_releases, color: Colors.orange, size: 20),
              ),
              title: Text(o.outlet?.nama ?? 'Order #${o.id}'),
              subtitle: Text('Rp ${o.totalHarga.toStringAsFixed(0)} • COD'),
              trailing: FilledButton(
                onPressed: () => _klaim(o.id),
                child: const Text('Klaim'),
              ),
            ),
          );
        },
      ),
    );
  }
}