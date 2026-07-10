import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';
import 'package:geolocator/geolocator.dart';
import 'available_orders_screen.dart';
import 'my_orders_screen.dart';
import 'profil_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _index = 0;
  bool _isOnline = false;
  bool _toggling = false;

  final _screens = const [
    AvailableOrdersScreen(),
    MyOrdersScreen(),
    ProfilScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Desahub Kurir'),
        actions: [
          Switch(
            value: _isOnline,
            onChanged: _toggling ? null : (_) => _toggleOnline(),
          ),
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: Center(
              child: Text(
                _isOnline ? 'ONLINE' : 'OFFLINE',
                style: TextStyle(
                  color: _isOnline ? Colors.green : Colors.red,
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                ),
              ),
            ),
          ),
        ],
      ),
      body: _screens[_index],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.list_alt), label: 'Tersedia'),
          NavigationDestination(icon: Icon(Icons.assignment), label: 'Order Saya'),
          NavigationDestination(icon: Icon(Icons.person), label: 'Profil'),
        ],
      ),
    );
  }

  Future<void> _toggleOnline() async {
    setState(() => _toggling = true);
    try {
      final newState = !_isOnline;
      double? lat, lng;

      if (newState) {
        final perm = await Geolocator.checkPermission();
        if (perm == LocationPermission.denied) {
          await Geolocator.requestPermission();
        }
        try {
          final pos = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.high,
          );
          lat = pos.latitude;
          lng = pos.longitude;
        } catch (_) {}
      }

      final kurirService = context.read<KurirService>();
      await kurirService.toggleOnline(online: newState, lat: lat, lng: lng);
      setState(() => _isOnline = newState);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _toggling = false);
    }
  }
}