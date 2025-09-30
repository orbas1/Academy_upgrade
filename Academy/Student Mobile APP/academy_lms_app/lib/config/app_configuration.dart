class AppConfiguration {
  AppConfiguration._internal()
      : apiBaseUrl = Uri.parse(
          const String.fromEnvironment(
            'ACADEMY_API_BASE_URL',
            defaultValue: 'https://academy.local/api',
          ),
        ),
        realtimeGatewayUrl = Uri.parse(
          const String.fromEnvironment(
            'ACADEMY_REALTIME_GATEWAY_URL',
            defaultValue: 'wss://academy.local/ws',
          ),
        ),
        communityManifestUrl = Uri.parse(
          const String.fromEnvironment(
            'ACADEMY_COMMUNITY_MANIFEST_URL',
            defaultValue: 'https://academy.local/api/v1/admin/communities/modules',
          ),
        ),
        oauthTokenEndpoint = Uri.parse(
          const String.fromEnvironment(
            'ACADEMY_OAUTH_TOKEN_ENDPOINT',
            defaultValue: 'https://academy.local/oauth/token',
          ),
        ),
        oauthClientId = const String.fromEnvironment('ACADEMY_OAUTH_CLIENT_ID', defaultValue: ''),
        oauthClientSecret = const String.fromEnvironment('ACADEMY_OAUTH_CLIENT_SECRET', defaultValue: ''),
        oauthScopes = const String.fromEnvironment('ACADEMY_OAUTH_SCOPES', defaultValue: ''),
        sentryDsn = _emptyToNull(
          const String.fromEnvironment('ACADEMY_SENTRY_DSN', defaultValue: ''),
        ),
        realtimeSocketUrl = _parseOptionalUri(
          const String.fromEnvironment('ACADEMY_REALTIME_SOCKET_URL', defaultValue: ''),
        ),
        realtimeAuthEndpoint = _parseOptionalUri(
          const String.fromEnvironment('ACADEMY_REALTIME_AUTH_URL', defaultValue: ''),
        ),
        analyticsEnabled = const bool.fromEnvironment(
          'ACADEMY_ENABLE_FIREBASE_ANALYTICS',
          defaultValue: false,
        ),
        environment = const String.fromEnvironment('ACADEMY_APP_ENV', defaultValue: 'development');

  static final AppConfiguration instance = AppConfiguration._internal();

  final Uri apiBaseUrl;
  final Uri realtimeGatewayUrl;
  Uri communityManifestUrl;
  final Uri oauthTokenEndpoint;
  final String? oauthClientId;
  final String? oauthClientSecret;
  final String? oauthScopes;
  final String? sentryDsn;
  final Uri? realtimeSocketUrl;
  final Uri? realtimeAuthEndpoint;
  final bool analyticsEnabled;
  final String environment;

  Uri resolveApiPath(String path) {
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return Uri.parse(path);
    }

    final normalized = path.startsWith('/') ? path.substring(1) : path;
    final basePath = apiBaseUrl.path.endsWith('/') ? apiBaseUrl.path.substring(0, apiBaseUrl.path.length - 1) : apiBaseUrl.path;
    return apiBaseUrl.replace(path: '$basePath/$normalized');
  }

  void updateCommunityManifestUrl(Uri endpoint) {
    communityManifestUrl = endpoint;
  }


  static String? _emptyToNull(String value) {
    final trimmed = value.trim();
    return trimmed.isEmpty ? null : trimmed;
  }

  static Uri? _parseOptionalUri(String value) {
    final trimmed = value.trim();
    if (trimmed.isEmpty) {
      return null;
    }
    return Uri.parse(trimmed);
  Uri buildRealtimePresenceUri({required String communityId, String? token}) {
    final queryParameters = <String, String>{
      'community_id': communityId,
      if (token != null && token.isNotEmpty) 'token': token,
    };

    return realtimeGatewayUrl.replace(
      queryParameters: {
        ...realtimeGatewayUrl.queryParameters,
        ...queryParameters,
      },
    );
  }
}
