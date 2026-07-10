import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:desahub_core/desahub_core.dart';

class ProfilScreen extends StatelessWidget {
  const ProfilScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        CircleAvatar(
          radius: 40,
          backgroundColor: Theme.of(context).colorScheme.primaryContainer,
          child: Icon(Icons.delivery_dining, size: 40,
              color: Theme.of(context).colorScheme.primary),
        ),
        const SizedBox(height: 16),
        _infoRow('Nama', auth.userNama ?? '-'),
        _infoRow('No HP', auth.userHp ?? '-'),
        _infoRow('Peran', 'Kurir'),
        const Divider(height: 32),
        SizedBox(
          width: double.infinity,
          height: 48,
          child: OutlinedButton.icon(
            onPressed: () async {
              await context.read<AuthService>().logout();
            },
            icon: const Icon(Icons.logout, color: Colors.red),
            label: const Text('Logout', style: TextStyle(color: Colors.red)),
          ),
        ),
        const SizedBox(height: 8),
        Text('Desahub Kurir v1.0.0',
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodySmall),
      ],
    );
  }

  Widget _infoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}