import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/app_configuration.dart';
import 'realtime/realtime_presence_service.dart';

class CommunityModuleNavigationItem {
  CommunityModuleNavigationItem({
    required this.label,
    required this.route,
    this.icon,
  });

  factory CommunityModuleNavigationItem.fromJson(Map<String, dynamic> json) {
    return CommunityModuleNavigationItem(
      label: json['label'] as String? ?? '',
      route: json['route'] as String? ?? '',
      icon: json['icon'] as String?,
    );
  }

  final String label;
  final String route;
  final String? icon;
}

class CommunityModuleDescriptor {
  CommunityModuleDescriptor({
    required this.key,
    required this.name,
    required this.permissions,
    required this.navigation,
    required this.routes,
  });

  factory CommunityModuleDescriptor.fromJson(Map<String, dynamic> json) {
    final routes = (json['routes'] as List<dynamic>? ?? <dynamic>[])
        .map((dynamic route) => Map<String, dynamic>.from(route as Map))
        .toList(growable: false);

    return CommunityModuleDescriptor(
      key: json['key'] as String? ?? '',
      name: json['name'] as String? ?? '',
      permissions: List<String>.from(json['permissions'] as List? ?? const <String>[]),
      navigation: (json['navigation'] as List<dynamic>? ?? <dynamic>[]) 
          .map((dynamic item) => CommunityModuleNavigationItem.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList(growable: false),
      routes: routes,
    );
  }

  final String key;
  final String name;
  final List<String> permissions;
  final List<CommunityModuleNavigationItem> navigation;
  final List<Map<String, dynamic>> routes;
}

class CommunityModuleManifest {
  CommunityModuleManifest({
    required this.version,
    required this.generatedAt,
    required this.modules,
    this.apiBaseUrl,
    this.realtime,
  });

  factory CommunityModuleManifest.fromJson(Map<String, dynamic> json) {
    final api = json['api'];
    CommunityRealtimeConfig? realtimeConfig;
    final realtime = json['realtime'];
    if (realtime is Map<String, dynamic>) {
      realtimeConfig = CommunityRealtimeConfig.fromJson(realtime);
    }
    return CommunityModuleManifest(
      version: json['version'] as String? ?? '0.0.0',
      generatedAt: json['generated_at'] as String? ?? DateTime.now().toIso8601String(),
      modules: (json['modules'] as List<dynamic>? ?? <dynamic>[])
          .map((dynamic module) => CommunityModuleDescriptor.fromJson(Map<String, dynamic>.from(module as Map)))
          .toList(growable: false),
      apiBaseUrl: api is Map<String, dynamic> ? api['base_url'] as String? : null,
      realtime: realtimeConfig,
    );
  }

  final String version;
  final String generatedAt;
  final List<CommunityModuleDescriptor> modules;
  final String? apiBaseUrl;
  final CommunityRealtimeConfig? realtime;
}

class CommunityRealtimeConfig {
  CommunityRealtimeConfig({
    required this.socketUrl,
    this.authEndpoint,
    this.heartbeatInterval = const Duration(seconds: 25),
    this.typingDebounce = const Duration(milliseconds: 900),
  });

  factory CommunityRealtimeConfig.fromJson(Map<String, dynamic> json) {
    final socket = json['socket_url'] as String?;
    if (socket == null || socket.isEmpty) {
      throw ArgumentError('Realtime config requires `socket_url`.');
    }
    final heartbeatSeconds = json['heartbeat_interval'] as int? ?? 25;
    final typingMs = json['typing_debounce_ms'] as int? ?? 900;
    return CommunityRealtimeConfig(
      socketUrl: Uri.parse(socket),
      authEndpoint: _parseOptionalUri(json['auth_endpoint'] as String?),
      heartbeatInterval: Duration(seconds: heartbeatSeconds),
      typingDebounce: Duration(milliseconds: typingMs),
    );
  }

  final Uri socketUrl;
  final Uri? authEndpoint;
  final Duration heartbeatInterval;
  final Duration typingDebounce;

  RealtimePresenceConfig toRealtimePresenceConfig() {
    return RealtimePresenceConfig(
      socketUrl: socketUrl,
      authEndpoint: authEndpoint,
      heartbeatInterval: heartbeatInterval,
      typingDebounce: typingDebounce,
    );
  }

  static Uri? _parseOptionalUri(String? value) {
    final text = value?.trim();
    if (text == null || text.isEmpty) {
      return null;
    }
    return Uri.parse(text);
  }
}

class CommunityManifestService {
  CommunityManifestService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  Future<CommunityModuleManifest> fetch({String? bearerToken}) async {
    final configuration = AppConfiguration.instance;
    final response = await _client.get(
      configuration.communityManifestUrl,
      headers: <String, String>{
        'Accept': 'application/json',
        if (bearerToken != null && bearerToken.isNotEmpty) 'Authorization': 'Bearer $bearerToken',
      },
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Failed to load community manifest', response.request?.url);
    }

    final Map<String, dynamic> envelope = jsonDecode(response.body) as Map<String, dynamic>;
    final Map<String, dynamic> data = Map<String, dynamic>.from(
      envelope['data'] as Map? ?? const <String, dynamic>{},
    );

    final manifest = CommunityModuleManifest.fromJson(data);

    final dynamic api = data['api'];
    if (api is Map<String, dynamic> && api['manifest_endpoint'] is String) {
      configuration.updateCommunityManifestUrl(Uri.parse(api['manifest_endpoint'] as String));
    }

    return manifest;
  }
}
