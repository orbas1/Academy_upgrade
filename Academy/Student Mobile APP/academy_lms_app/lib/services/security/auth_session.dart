import 'package:meta/meta.dart';

@immutable
class AuthSession {
  const AuthSession({
    required this.accessToken,
    this.refreshToken,
    this.accessTokenExpiresAt,
    this.refreshTokenExpiresAt,
    this.tokenType = 'Bearer',
    this.scope,
  });

  final String accessToken;
  final String? refreshToken;
  final DateTime? accessTokenExpiresAt;
  final DateTime? refreshTokenExpiresAt;
  final String tokenType;
  final String? scope;

  bool get hasExpiredAccessToken => accessTokenExpiresAt != null && accessTokenExpiresAt!.isBefore(DateTime.now().toUtc());

  bool get shouldRefreshAccessToken {
    if (accessTokenExpiresAt == null) {
      return false;
    }
    return accessTokenExpiresAt!.isBefore(DateTime.now().toUtc().add(const Duration(minutes: 1)));
  }

  bool get hasRefreshToken => refreshToken != null && refreshToken!.isNotEmpty;

  AuthSession copyWith({
    String? accessToken,
    String? refreshToken,
    DateTime? accessTokenExpiresAt,
    DateTime? refreshTokenExpiresAt,
    String? tokenType,
    String? scope,
  }) {
    return AuthSession(
      accessToken: accessToken ?? this.accessToken,
      refreshToken: refreshToken ?? this.refreshToken,
      accessTokenExpiresAt: accessTokenExpiresAt ?? this.accessTokenExpiresAt,
      refreshTokenExpiresAt: refreshTokenExpiresAt ?? this.refreshTokenExpiresAt,
      tokenType: tokenType ?? this.tokenType,
      scope: scope ?? this.scope,
    );
  }

  static AuthSession fromOAuthJson(Map<String, dynamic> json, {String? fallbackRefreshToken}) {
    final accessToken = json['access_token'] ?? json['token'];
    if (accessToken is! String || accessToken.isEmpty) {
      throw const FormatException('OAuth response is missing an access token.');
    }

    final refreshToken = (json['refresh_token'] ?? fallbackRefreshToken) as String?;
    final expiresIn = json['expires_in'] ?? json['access_token_expires_in'];
    final refreshExpiresIn = json['refresh_expires_in'];

    DateTime? accessExpiry;
    if (expiresIn is int) {
      accessExpiry = DateTime.now().toUtc().add(Duration(seconds: expiresIn));
    } else if (expiresIn is String) {
      accessExpiry = DateTime.tryParse(expiresIn)?.toUtc();
    }

    DateTime? refreshExpiry;
    if (refreshExpiresIn is int) {
      refreshExpiry = DateTime.now().toUtc().add(Duration(seconds: refreshExpiresIn));
    } else if (refreshExpiresIn is String) {
      refreshExpiry = DateTime.tryParse(refreshExpiresIn)?.toUtc();
    }

    final scope = json['scope'] as String?;
    final tokenType = json['token_type'] as String? ?? 'Bearer';

    return AuthSession(
      accessToken: accessToken,
      refreshToken: refreshToken,
      accessTokenExpiresAt: accessExpiry,
      refreshTokenExpiresAt: refreshExpiry,
      tokenType: tokenType,
      scope: scope,
    );
  }
}
