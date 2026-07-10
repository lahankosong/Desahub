import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';
import 'providers/keranjang_provider.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const AppKonsumen());
}

class AppKonsumen extends StatelessWidget {
  const AppKonsumen({super.key});

  @override
  Widget build(BuildContext context) {
    final authProvider = AuthProvider();
    final apiClient = ApiClient(
      authProvider: authProvider,
      baseUrl: resolveBaseUrl(),
    );

    return MultiProvider(
      providers: [
        ChangeNotifierProvider.value(value: authProvider),
        Provider.value(value: apiClient),
        ChangeNotifierProvider(create: (_) => KeranjangProvider()),
        Provider(create: (_) => AuthService(apiClient, authProvider)),
        Provider(create: (_) => OutletService(apiClient)),
        Provider(create: (_) => ProdukService(apiClient)),
        Provider(create: (_) => OrderService(apiClient)),
      ],
      child: MaterialApp(
        title: 'Desahub Konsumen',
        theme: ThemeData(
          colorSchemeSeed: const Color(0xFF2E7D32),
          useMaterial3: true,
          fontFamily: 'Roboto',
        ),
        home: const AuthGate(),
      ),
    );
  }
}

/// Cek apakah user sudah login, redirect ke Home atau Login.
class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  late final AuthProvider _auth;

  @override
  void initState() {
    super.initState();
    _auth = context.read<AuthProvider>();
    _auth.addListener(_onAuthChanged);
    _tryAutoLogin();
  }

  void _tryAutoLogin() async {
    await _auth.tryAutoLogin();
  }

  void _onAuthChanged() {
    if (mounted) setState(() {});
  }

  @override
  void dispose() {
    _auth.removeListener(_onAuthChanged);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_auth.isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (_auth.isLoggedIn) {
      return const HomeScreen();
    }
    return const LoginScreen();
  }
}