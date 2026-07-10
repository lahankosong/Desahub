/// Desahub Core — shared package untuk semua app Desahub.
///
/// Export semua model, services, dan providers yang dipakai bersama
/// oleh app Outlet, Konsumen, dan Kurir.
library desahub_core;

// API & Auth
export 'api_client.dart';
export 'auth_provider.dart';
export 'config.dart';

// Models
export 'models/user.dart';
export 'models/outlet.dart';
export 'models/produk.dart';
export 'models/order.dart';

// Services
export 'services/auth_service.dart';
export 'services/outlet_service.dart';
export 'services/produk_service.dart';
export 'services/order_service.dart';
export 'services/payment_service.dart';
export 'services/kurir_service.dart';