import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const AppOutlet());
}

class AppOutlet extends StatelessWidget {
  const AppOutlet({super.key});

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
        Provider(create: (_) => AuthService(apiClient, authProvider)),
        Provider(create: (_) => ProdukService(apiClient)),
        Provider(create: (_) => OrderService(apiClient)),
      ],
      child: MaterialApp(
        title: 'Desahub Outlet',
        theme: ThemeData(
          colorSchemeSeed: const Color(0xFF2E7D32),
          useMaterial3: true,
        ),
        home: const OutletAuthGate(),
      ),
    );
  }
}

class OutletAuthGate extends StatefulWidget {
  const OutletAuthGate({super.key});

  @override
  State<OutletAuthGate> createState() => _OutletAuthGateState();
}

class _OutletAuthGateState extends State<OutletAuthGate> {
  late final AuthProvider _auth;

  @override
  void initState() {
    super.initState();
    _auth = context.read<AuthProvider>();
    _auth.addListener(_refresh);
    _auth.tryAutoLogin();
  }

  void _refresh() => mounted ? setState(() {}) : null;

  @override
  void dispose() {
    _auth.removeListener(_refresh);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_auth.isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (_auth.isLoggedIn && _auth.peran == 'outlet') {
      return const HomeScreen();
    }
    return const LoginScreen();
  }
}