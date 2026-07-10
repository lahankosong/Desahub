import '../api_client.dart';
import '../auth_provider.dart';

class AuthService {
  final ApiClient _client;
  final AuthProvider _auth;

  AuthService(this._client, this._auth);

  /// Registrasi user baru.
  /// Backend: POST /v1/auth/register
  /// Field: nama, no_hp, password
  Future<Map<String, dynamic>> register({
    required String nama,
    required String noHp,
    required String password,
    String? email,
  }) async {
    final res = await _client.dio.post('/v1/auth/register', data: {
      'nama': nama,
      'no_hp': noHp,
      'password': password,
      if (email != null) 'email': email,
    });

    final data = res.data;
    // Backend register tidak langsung kasih token — login dulu setelah verifikasi OTP.
    return data;
  }

  /// Login.
  /// Backend: POST /v1/auth/login
  /// Field: no_hp, password
  Future<Map<String, dynamic>> login({
    required String noHp,
    required String password,
    required String peran,
  }) async {
    final res = await _client.dio.post('/v1/auth/login', data: {
      'no_hp': noHp,
      'password': password,
    });

    final data = res.data;
    await _auth.saveSession(
      token: data['token'],
      userId: data['user']['id'],
      nama: data['user']['nama'],
      hp: data['user']['no_hp'],
      peran: peran,
    );
    return data;
  }

  /// Verifikasi OTP.
  /// Backend: POST /v1/auth/verify-otp
  Future<Map<String, dynamic>> verifyOtp({
    required int userId,
    required String otpCode,
  }) async {
    final res = await _client.dio.post('/v1/auth/verify-otp', data: {
      'user_id': userId,
      'otp_code': otpCode,
    });
    return res.data;
  }

  /// Logout.
  Future<void> logout() async {
    try {
      await _client.dio.post('/v1/auth/logout');
    } catch (_) {}
    await _auth.clearToken();
  }

  /// Ambil profil user (untuk cek multi-peran).
  Future<Map<String, dynamic>> getProfil() async {
    final res = await _client.dio.get('/v1/auth/me');
    return res.data;
  }
}