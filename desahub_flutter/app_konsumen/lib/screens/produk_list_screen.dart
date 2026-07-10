import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';
import '../providers/keranjang_provider.dart';

class ProdukListScreen extends StatefulWidget {
  final Outlet outlet;

  const ProdukListScreen({super.key, required this.outlet});

  @override
  State<ProdukListScreen> createState() => _ProdukListScreenState();
}

class _ProdukListScreenState extends State<ProdukListScreen> {
  List<Produk> _produkList = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadProduk();
  }

  Future<void> _loadProduk() async {
    setState(() => _loading = true);
    try {
      final produkService = context.read<ProdukService>();
      _produkList = await produkService.getByOutlet(widget.outlet.id);
    } catch (_) {
      _produkList = [];
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final keranjang = context.watch<KeranjangProvider>();

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.outlet.nama),
        actions: [
          if (keranjang.totalItem > 0)
            Center(
              child: Padding(
                padding: const EdgeInsets.only(right: 8),
                child: Text('${keranjang.totalItem} item',
                    style: Theme.of(context).textTheme.labelLarge),
              ),
            ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _produkList.isEmpty
              ? const Center(child: Text('Belum ada produk'))
              : RefreshIndicator(
                  onRefresh: _loadProduk,
                  child: ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: _produkList.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (_, i) {
                      final p = _produkList[i];
                      final inKeranjang = keranjang.items
                          .where((item) => item.produk.id == p.id)
                          .fold<int>(0, (sum, item) => sum + item.qty);

                      return ListTile(
                        leading: CircleAvatar(
                          backgroundColor:
                              Theme.of(context).colorScheme.primaryContainer,
                          child: Icon(Icons.shopping_basket,
                              color: Theme.of(context).colorScheme.primary),
                        ),
                        title: Text(p.nama),
                        subtitle: Text(
                            'Rp ${p.harga.toStringAsFixed(0)} / ${p.satuan}'),
                        trailing: inKeranjang > 0
                            ? Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  IconButton(
                                    icon: const Icon(Icons.remove_circle_outline),
                                    onPressed: () {
                                      keranjang.updateQty(p.id, inKeranjang - 1);
                                    },
                                  ),
                                  Text('$inKeranjang',
                                      style: Theme.of(context)
                                          .textTheme
                                          .titleMedium),
                                  IconButton(
                                    icon: const Icon(Icons.add_circle_outline),
                                    onPressed: () {
                                      keranjang.tambah(p);
                                    },
                                  ),
                                ],
                              )
                            : FilledButton.tonal(
                                onPressed: () => keranjang.tambah(p),
                                child: const Text('Tambah'),
                              ),
                        isThreeLine: false,
                      );
                    },
                  ),
                ),
    );
  }
}