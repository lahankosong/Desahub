import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';
import '../providers/keranjang_provider.dart';
import 'outlet_list_screen.dart';
import 'keranjang_screen.dart';
import 'order_list_screen.dart';
import 'profil_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _currentIndex = 0;

  final _screens = const [
    OutletListScreen(),
    KeranjangScreen(),
    OrderListScreen(),
    ProfilScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    final keranjang = context.watch<KeranjangProvider>();
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      body: _screens[_currentIndex],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (i) => setState(() => _currentIndex = i),
        destinations: [
          const NavigationDestination(
              icon: Icon(Icons.store), label: 'Outlet'),
          NavigationDestination(
            icon: Badge(
              label: Text('${keranjang.totalItem}'),
              isLabelVisible: keranjang.totalItem > 0,
              child: const Icon(Icons.shopping_cart),
            ),
            label: 'Keranjang',
          ),
          const NavigationDestination(
              icon: Icon(Icons.receipt_long), label: 'Pesanan'),
          NavigationDestination(
            icon: Icon(Icons.person, color: auth.isLoggedIn ? null : Colors.grey),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}