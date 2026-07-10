import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';

class ProdukKelolaScreen extends StatefulWidget {
  const ProdukKelolaScreen({super.key});

  @override
  State<ProdukKelolaScreen> createState() => _ProdukKelolaScreenState();
}

class _ProdukKelolaScreenState extends State<ProdukKelolaScreen> {
  List<Produk> _produkList = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      // MVP: pakai profil user untuk dapatkan outlet_id.
      // Asumsi: outlet_id = auth.userId (pemilik outlet)
      final profil = await context.read<AuthService>().getProfil();
      final outletProfileId = profil['outlet_profile'] as int?;
      if (outletProfileId == null) return;

      final produkService = context.read<ProdukService>();
      _produkList = await produkService.getByOutlet(outletProfileId);
    } catch (_) {
      _produkList = [];
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _tambahProduk() async {
    final profil = await context.read<AuthService>().getProfil();
    final outletId = profil['outlet_profile'] as int?;
    if (outletId == null || !mounted) return;

    final namaCtrl = TextEditingController();
    final hargaCtrl = TextEditingController();
    final satuanCtrl = TextEditingController(text: 'pcs');
    final qtyCtrl = TextEditingController(text: '0');

    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Tambah Produk'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(controller: namaCtrl, decoration: const InputDecoration(labelText: 'Nama Produk')),
              TextField(controller: hargaCtrl, decoration: const InputDecoration(labelText: 'Harga'), keyboardType: TextInputType.number),
              TextField(controller: satuanCtrl, decoration: const InputDecoration(labelText: 'Satuan (pcs/kg)')),
              TextField(controller: qtyCtrl, decoration: const InputDecoration(labelText: 'Stok Awal'), keyboardType: TextInputType.number),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Simpan')),
        ],
      ),
    );

    if (ok != true || !mounted) return;

    try {
      final produkService = context.read<ProdukService>();
      await produkService.create(
        outletId: outletId,
        nama: namaCtrl.text.trim(),
        harga: double.parse(hargaCtrl.text),
        satuan: satuanCtrl.text.trim(),
        qtyAwal: int.tryParse(qtyCtrl.text) ?? 0,
      );
      _load();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Produk ditambahkan'), backgroundColor: Colors.green),
        );
      }
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
    return Scaffold(
      appBar: AppBar(title: const Text('Produk Saya')),
      floatingActionButton: FloatingActionButton(
        onPressed: _tambahProduk,
        child: const Icon(Icons.add),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _produkList.isEmpty
              ? const Center(child: Text('Belum ada produk. Tap + untuk tambah.'))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _produkList.length,
                    itemBuilder: (_, i) {
                      final p = _produkList[i];
                      return Card(
                        child: ListTile(
                          leading: CircleAvatar(
                            backgroundColor: Theme.of(context).colorScheme.primaryContainer,
                            child: Icon(Icons.shopping_basket, color: Theme.of(context).colorScheme.primary, size: 20),
                          ),
                          title: Text(p.nama),
                          subtitle: Text('Rp ${p.harga.toStringAsFixed(0)} / ${p.satuan}'),
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}