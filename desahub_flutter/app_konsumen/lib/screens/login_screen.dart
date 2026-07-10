import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:dio/dio.dart';
import 'package:desahub_core/desahub_core.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _hpCtrl = TextEditingController();
  final _pwCtrl = TextEditingController();
  final _namaCtrl = TextEditingController();
  bool _isRegister = false;
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _hpCtrl.dispose();
    _pwCtrl.dispose();
    _namaCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final authService = context.read<AuthService>();
      if (_isRegister) {
        if (_namaCtrl.text.trim().isEmpty) {
          setState(() => _error = 'Nama tidak boleh kosong');
          setState(() => _loading = false);
          return;
        }
        await authService.register(
          nama: _namaCtrl.text.trim(),
          noHp: _hpCtrl.text.trim(),
          password: _pwCtrl.text,
        );
      } else {
        await authService.login(
          noHp: _hpCtrl.text.trim(),
          password: _pwCtrl.text,
          peran: 'konsumen',
        );
      }
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data['message'] ?? 'Gagal ${_isRegister ? 'registrasi' : 'login'}')
          : 'Gagal ${_isRegister ? 'registrasi' : 'login'}';
      setState(() => _error = msg);
    } catch (e) {
      setState(() => _error = 'Gagal ${_isRegister ? 'registrasi' : 'login'}: $e');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.shopping_cart, size: 64,
                    color: Theme.of(context).colorScheme.primary),
                const SizedBox(height: 8),
                Text('Desahub', style: Theme.of(context)
                    .textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.bold)),
                Text('Konsumen', style: Theme.of(context)
                    .textTheme.bodyLarge?.copyWith(color: Colors.grey)),
                const SizedBox(height: 32),

                if (_error != null)
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(12),
                    margin: const EdgeInsets.only(bottom: 16),
                    decoration: BoxDecoration(
                        color: Colors.red.shade50,
                        borderRadius: BorderRadius.circular(8)),
                    child: Text(_error!, style: TextStyle(color: Colors.red.shade700)),
                  ),

                if (_isRegister)
                  TextField(
                    controller: _namaCtrl,
                    decoration: const InputDecoration(
                        labelText: 'Nama', prefixIcon: Icon(Icons.person)),
                  ),
                TextField(
                  controller: _hpCtrl,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                      labelText: 'No HP', prefixIcon: Icon(Icons.phone_android)),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _pwCtrl,
                  obscureText: true,
                  decoration: const InputDecoration(
                      labelText: 'Password', prefixIcon: Icon(Icons.lock)),
                ),
                const SizedBox(height: 24),

                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: FilledButton(
                    onPressed: _loading ? null : _submit,
                    child: _loading
                        ? const SizedBox(width: 20, height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : Text(_isRegister ? 'Daftar' : 'Login'),
                  ),
                ),
                TextButton(
                  onPressed: () => setState(() {
                    _isRegister = !_isRegister;
                    _error = null;
                  }),
                  child: Text(_isRegister
                      ? 'Sudah punya akun? Login'
                      : 'Belum punya akun? Daftar'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}