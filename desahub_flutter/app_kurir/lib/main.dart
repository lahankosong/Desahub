import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const AppKurir());
}

class AppKurir extends StatelessWidget {
  const AppKurir({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = AuthProvider();
    final api = ApiClient(authProvider: auth, baseUrl: resolveBaseUrl());

    return MultiProvider(
      providers: [
        ChangeNotifierProvider.value(value: auth),
        Provider.value(value: api),
        Provider(create: (_) => AuthService(api, auth)),
        Provider(create: (_) => KurirService(api)),
        Provider(create: (_) => OrderService(api)),
      ],
      child: MaterialApp(
        title: 'Desahub Kurir',
        theme: ThemeData(
          colorSchemeSeed: const Color(0xFF2E7D32),
          useMaterial3: true,
        ),
        home: const KurirAuthGate(),
      ),
    );
  }
}

class KurirAuthGate extends StatefulWidget {
  const KurirAuthGate({super.key});

  @override
  State<KurirAuthGate> createState() => _KurirAuthGateState();
}

class _KurirAuthGateState extends State<KurirAuthGate> {
  late final AuthProvider _auth;

  @override
  void initState() {
    super.initState();
    _auth = context.read<AuthProvider>();
    _auth.addListener(() => mounted ? setState(() {}) : null);
    _auth.tryAutoLogin();
  }

  @override
  Widget build(BuildContext context) {
    if (_auth.isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (_auth.isLoggedIn && _auth.peran == 'kurir') {
      return const HomeScreen();
    }
    return const LoginScreen();
  }
}