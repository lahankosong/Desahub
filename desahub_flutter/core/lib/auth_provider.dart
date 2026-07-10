import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Provider yang mengelola token Sanctum dan data user login.
///
/// Dipakai bersama oleh semua app (Outlet, Konsumen, Kurir).
/// Token disimpan di secure storage, bukan shared preferences biasa.
class AuthProvider extends ChangeNotifier {
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  static const _tokenKey = 'sanctum_token';
  static const _userIdKey = 'user_id';
  static const _userNamaKey = 'user_nama';
  static const _userHpKey = 'user_hp';
  static const _peranKey = 'peran_aktif';

  String? _token;
  int? _userId;
  String? _userNama;
  String? _userHp;
  String? _peran;
  bool _isLoading = false;

  bool get isLoggedIn => _token != null;
  bool get isLoading => _isLoading;
  String? get token => _token;
  int? get userId => _userId;
  String? get userNama => _userNama;
  String? get userHp => _userHp;
  String? get peran => _peran;

  /// Coba restore session dari secure storage (dipanggil saat app start).
  Future<void> tryAutoLogin() async {
    _token = await _storage.read(key: _tokenKey);
    if (_token == null) return;

    _userId = int.tryParse(await _storage.read(key: _userIdKey) ?? '');
    _userNama = await _storage.read(key: _userNamaKey);
    _userHp = await _storage.read(key: _userHpKey);
    _peran = await _storage.read(key: _peranKey);
    notifyListeners();
  }

  /// Simpan session setelah register/login sukses.
  Future<void> saveSession({
    required String token,
    required int userId,
    required String nama,
    required String hp,
    required String peran,
  }) async {
    _token = token;
    _userId = userId;
    _userNama = nama;
    _userHp = hp;
    _peran = peran;

    await Future.wait([
      _storage.write(key: _tokenKey, value: token),
      _storage.write(key: _userIdKey, value: userId.toString()),
      _storage.write(key: _userNamaKey, value: nama),
      _storage.write(key: _userHpKey, value: hp),
      _storage.write(key: _peranKey, value: peran),
    ]);

    notifyListeners();
  }

  /// Ambil token dari storage — dipakai ApiClient interceptor.
  Future<String?> getToken() async {
    if (_token != null) return _token;
    _token = await _storage.read(key: _tokenKey);
    return _token;
  }

  /// Hapus session (logout).
  Future<void> clearToken() async {
    _token = null;
    _userId = null;
    _userNama = null;
    _userHp = null;
    _peran = null;

    await _storage.deleteAll();
    notifyListeners();
  }

  void setLoading(bool value) {
    _isLoading = value;
    notifyListeners();
  }
}