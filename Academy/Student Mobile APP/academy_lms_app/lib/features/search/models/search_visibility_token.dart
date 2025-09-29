import 'package:meta/meta.dart';

@immutable
class SearchVisibilityToken {
  const SearchVisibilityToken({
    required this.token,
    required this.filters,
    required this.issuedAt,
    required this.expiresAt,
  });

  final String token;
  final List<String> filters;
  final DateTime issuedAt;
  final DateTime expiresAt;

  factory SearchVisibilityToken.fromJson(Map<String, dynamic> json) {
    return SearchVisibilityToken(
      token: json['token'] as String? ?? '',
      filters: (json['filters'] as List<dynamic>? ?? <dynamic>[])
          .map((dynamic entry) => entry.toString())
          .toList(growable: false),
      issuedAt: DateTime.tryParse(json['issued_at'] as String? ?? '') ??
          DateTime.fromMillisecondsSinceEpoch(0, isUtc: true),
      expiresAt: DateTime.tryParse(json['expires_at'] as String? ?? '') ??
          DateTime.fromMillisecondsSinceEpoch(0, isUtc: true),
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'token': token,
      'filters': filters,
      'issued_at': issuedAt.toUtc().toIso8601String(),
      'expires_at': expiresAt.toUtc().toIso8601String(),
    };
  }

  bool get isExpired => DateTime.now().toUtc().isAfter(expiresAt.toUtc());
}
