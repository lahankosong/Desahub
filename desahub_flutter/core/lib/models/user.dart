/// User model — sesuai response dari /api/v1/profil
class User {
  final int id;
  final String nama;
  final String hp;
  final int? outletProfileId;
  final int? konsumenProfileId;
  final KurirProfile? kurirProfile;

  User({
    required this.id,
    required this.nama,
    required this.hp,
    this.outletProfileId,
    this.konsumenProfileId,
    this.kurirProfile,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      nama: json['nama'],
      hp: json['hp'],
      outletProfileId: json['outlet_profile'],
      konsumenProfileId: json['konsumen_profile'],
      kurirProfile: json['kurir_profile'] != null
          ? KurirProfile.fromJson(json['kurir_profile'])
          : null,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'nama': nama,
        'hp': hp,
      };
}

class KurirProfile {
  final int id;
  final bool isOnline;

  KurirProfile({required this.id, required this.isOnline});

  factory KurirProfile.fromJson(Map<String, dynamic> json) {
    return KurirProfile(
      id: json['id'],
      isOnline: json['is_online'] ?? false,
    );
  }
}