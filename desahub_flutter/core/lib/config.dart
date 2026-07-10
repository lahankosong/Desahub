import 'package:flutter/foundation.dart' show kIsWeb;
import 'dart:io' show Platform;

/// Base URL API yang otomatis menyesuaikan platform saat development.
///
/// - Web (Chrome dev): backend diakses lewat 127.0.0.1 (localhost host)
/// - Android emulator: backend diakses lewat 10.0.2.2 (alias localhost host
///   dari sudut pandang emulator)
/// - iOS simulator / lainnya: fallback ke localhost
///
/// Port :8000 mengikuti `php artisan serve` (bukan XAMPP port 80).
/// Ganti ke IP LAN (mis. http://192.168.1.10:8000/api) kalau testing di HP fisik.
String resolveBaseUrl() {
  if (kIsWeb) {
    return 'http://127.0.0.1:8000/api';
  }
  try {
    if (Platform.isAndroid) {
      return 'http://10.0.2.2:8000/api';
    }
  } catch (_) {
    // Platform tidak tersedia (mis. saat unit test) -> fallback
  }
  return 'http://127.0.0.1:8000/api';
}
