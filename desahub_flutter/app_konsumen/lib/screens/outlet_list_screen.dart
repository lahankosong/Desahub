import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:geolocator/geolocator.dart';
import 'package:desahub_core/desahub_core.dart';
import 'produk_list_screen.dart';

class OutletListScreen extends StatefulWidget {
  const OutletListScreen({super.key});

  @override
  State<OutletListScreen> createState() => _OutletListScreenState();
}

class _OutletListScreenState extends State<OutletListScreen> {
  List<Outlet> _outlets = [];
  bool _loading = true;
  String? _error;
  double? _myLat, _myLng;

  @override
  void initState() {
    super.initState();
    _getLocationAndSearch();
  }

  Future<void> _getLocationAndSearch() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      // Minta permission GPS
      final permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.deniedForever) {
        setState(() => _error = 'Izin GPS diperlukan untuk mencari outlet terdekat');
        setState(() => _loading = false);
        return;
      }

      final pos = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      _myLat = pos.latitude;
      _myLng = pos.longitude;

      final outletService = context.read<OutletService>();
      _outlets = await outletService.cariOutlet(
        lat: _myLat!,
        lng: _myLng!,
        radiusKm: 1.0,
      );
    } catch (e) {
      setState(() => _error = 'Gagal mencari outlet: $e');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Outlet Terdekat')),
      body: _buildBody(),
      floatingActionButton: FloatingActionButton.small(
        onPressed: _getLocationAndSearch,
        child: const Icon(Icons.refresh),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(_error!, textAlign: TextAlign.center,
                style: TextStyle(color: Colors.red.shade700)),
            const SizedBox(height: 16),
            FilledButton.icon(
              onPressed: _getLocationAndSearch,
              icon: const Icon(Icons.refresh),
              label: const Text('Coba Lagi'),
            ),
          ],
        ),
      );
    }
    if (_outlets.isEmpty) {
      return const Center(
        child: Text('Tidak ada outlet dalam radius 1 km'),
      );
    }

    return RefreshIndicator(
      onRefresh: _getLocationAndSearch,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _outlets.length,
        separatorBuilder: (_, __) => const Divider(height: 1),
        itemBuilder: (_, i) {
          final o = _outlets[i];
          return ListTile(
            leading: CircleAvatar(
              backgroundColor: Theme.of(context).colorScheme.primaryContainer,
              child: Icon(Icons.store, color: Theme.of(context).colorScheme.primary),
            ),
            title: Text(o.nama),
            subtitle: Text(o.jarakKm != null
                ? '${o.jarakKm!.toStringAsFixed(2)} km • ${o.alamat ?? ""}'
                : o.alamat ?? ''),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              Navigator.push(context, MaterialPageRoute(
                builder: (_) => ProdukListScreen(outlet: o),
              ));
            },
          );
        },
      ),
    );
  }
}