class AppConfiguration {
  AppConfiguration._internal()
      : apiBaseUrl = Uri.parse(
          const String.fromEnvironment(
            'ACADEMY_API_BASE_URL',
            defaultValue: 'https://academy.local/api',
          ),
        ),
        communityManifestUrl = Uri.parse(
          const String.fromEnvironment(
            'ACADEMY_COMMUNITY_MANIFEST_URL',
            defaultValue: 'https://academy.local/api/v1/admin/communities/modules',
          ),
        );

  static final AppConfiguration instance = AppConfiguration._internal();

  final Uri apiBaseUrl;
  Uri communityManifestUrl;

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
}
