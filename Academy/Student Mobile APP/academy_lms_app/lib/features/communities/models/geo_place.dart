import 'package:meta/meta.dart';

@immutable
class GeoPlace {
  const GeoPlace({
    required this.id,
    required this.name,
    required this.description,
    required this.latitude,
    required this.longitude,
    required this.address,
    required this.city,
    required this.country,
    required this.isPrivate,
    required this.tags,
  });

  factory GeoPlace.fromJson(Map<String, dynamic> json) {
    return GeoPlace(
      id: json['id'] as int? ?? 0,
      name: json['name'] as String? ?? '',
      description: json['description'] as String? ?? '',
      latitude: (json['latitude'] as num?)?.toDouble() ?? 0,
      longitude: (json['longitude'] as num?)?.toDouble() ?? 0,
      address: json['address'] as String? ?? '',
      city: json['city'] as String? ?? '',
      country: json['country'] as String? ?? '',
      isPrivate: json['is_private'] as bool? ?? false,
      tags: List<String>.from(json['tags'] as List<dynamic>? ?? const <String>[]),
    );
  }

  final int id;
  final String name;
  final String description;
  final double latitude;
  final double longitude;
  final String address;
  final String city;
  final String country;
  final bool isPrivate;
  final List<String> tags;
}
