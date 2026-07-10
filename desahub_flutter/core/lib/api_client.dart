import 'package:dio/dio.dart';
import 'auth_provider.dart';

/// Shared API client untuk semua app Desahub.
///
/// Base URL dikonfigurasi via [baseUrl]. Token Sanctum auto-attach ke
/// setiap request lewat interceptor, diambil dari [AuthProvider].
class ApiClient {
  final Dio dio;
  final AuthProvider authProvider;

  ApiClient({
    required this.authProvider,
    String baseUrl = 'http://localhost/api',
  }) : dio = Dio(BaseOptions(
          baseUrl: baseUrl,
          connectTimeout: const Duration(seconds: 10),
          receiveTimeout: const Duration(seconds: 10),
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        )) {
    _setupInterceptors();
  }

  void _setupInterceptors() {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await authProvider.getToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          if (error.response?.statusCode == 401) {
            authProvider.clearToken();
          }
          handler.next(error);
        },
      ),
    );
  }

  /// PATCH — update base URL saat login dari app berbeda.
  void updateBaseUrl(String baseUrl) {
    dio.options.baseUrl = baseUrl;
  }
}