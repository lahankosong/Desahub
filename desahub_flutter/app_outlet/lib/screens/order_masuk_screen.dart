import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';

class OrderMasukScreen extends StatefulWidget {
  const OrderMasukScreen({super.key});

  @override
  State<OrderMasukScreen> createState() => _OrderMasukScreenState();
}

class _OrderMasukScreenState extends State<OrderMasukScreen> {
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
      final profil = await context.read<AuthService>().getProfil();
      final outletId = profil['outlet_profile'] as int?;
      if (outletId == null) return;

      final orderService = context.read<OrderService>();
      _orders = await orderService.getOrders(outletId: outletId);
    } catch (_) {
      _orders = [];
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Color _statusColor(String s) {
    switch (s) {
      case 'dibuat':
      case 'diambil_kurir':
        return Colors.orange;
      case 'diantar':
        return Colors.blue;
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
      appBar: AppBar(title: const Text('Order Masuk')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _orders.isEmpty
              ? const Center(child: Text('Belum ada order'))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: _orders.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (_, i) {
                      final o = _orders[i];
                      return ListTile(
                        leading: CircleAvatar(
                          backgroundColor: _statusColor(o.status).withValues(alpha: 0.15),
                          child: Icon(Icons.receipt, color: _statusColor(o.status), size: 20),
                        ),
                        title: Text('Order #${o.id}'),
                        subtitle: Text('Rp ${o.totalHarga.toStringAsFixed(0)} • ${o.metodePembayaran.toUpperCase()}'),
                        trailing: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: _statusColor(o.status).withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            o.statusLabel,
                            style: TextStyle(color: _statusColor(o.status), fontSize: 11, fontWeight: FontWeight.w600),
                          ),
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}