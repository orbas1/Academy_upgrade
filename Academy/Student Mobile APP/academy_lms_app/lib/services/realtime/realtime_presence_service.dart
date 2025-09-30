import 'dart:async';
import 'dart:convert';
import 'dart:math';

import 'package:flutter/foundation.dart';
import 'package:web_socket_channel/web_socket_channel.dart';

import '../telemetry/telemetry_service.dart';

@immutable
class RealtimePresenceConfig {
  const RealtimePresenceConfig({
    required this.socketUrl,
    this.authEndpoint,
    this.heartbeatInterval = const Duration(seconds: 25),
    this.typingDebounce = const Duration(milliseconds: 900),
    this.reconnectBaseDelay = const Duration(seconds: 2),
    this.reconnectMaxDelay = const Duration(seconds: 60),
  });

  final Uri socketUrl;
  final Uri? authEndpoint;
  final Duration heartbeatInterval;
  final Duration typingDebounce;
  final Duration reconnectBaseDelay;
  final Duration reconnectMaxDelay;

  Duration backoffForAttempt(int attempt) {
    final milliseconds = min(
      reconnectBaseDelay.inMilliseconds * pow(2, max(attempt - 1, 0)).toInt(),
      reconnectMaxDelay.inMilliseconds,
    );
    return Duration(milliseconds: milliseconds);
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) {
      return true;
    }
    return other is RealtimePresenceConfig &&
        other.socketUrl == socketUrl &&
        other.authEndpoint == authEndpoint &&
        other.heartbeatInterval == heartbeatInterval &&
        other.typingDebounce == typingDebounce &&
        other.reconnectBaseDelay == reconnectBaseDelay &&
        other.reconnectMaxDelay == reconnectMaxDelay;
  }

  @override
  int get hashCode => Object.hash(
        socketUrl,
        authEndpoint,
        heartbeatInterval,
        typingDebounce,
        reconnectBaseDelay,
        reconnectMaxDelay,
      );
}

@immutable
class PresenceMember {
  const PresenceMember({
    required this.memberId,
    required this.displayName,
    this.userId,
    this.avatarUrl,
    this.lastActiveAt,
  });

  factory PresenceMember.fromJson(Map<String, dynamic> json) {
    return PresenceMember(
      memberId: json['member_id'] as int? ?? json['id'] as int? ?? 0,
      displayName: json['display_name'] as String? ?? json['name'] as String? ?? 'Member',
      userId: json['user_id'] as int?,
      avatarUrl: json['avatar_url'] as String?,
      lastActiveAt: DateTime.tryParse(json['last_active_at'] as String? ?? ''),
    );
  }

  final int memberId;
  final String displayName;
  final int? userId;
  final String? avatarUrl;
  final DateTime? lastActiveAt;
}

@immutable
abstract class RealtimePresenceEvent {
  const RealtimePresenceEvent();
}

class PresenceConnectionStateChanged extends RealtimePresenceEvent {
  const PresenceConnectionStateChanged({
    required this.connected,
    this.error,
    this.retryIn,
  });

  final bool connected;
  final Object? error;
  final Duration? retryIn;
}

class PresenceMembersSynced extends RealtimePresenceEvent {
  const PresenceMembersSynced({required this.communityId, required this.members});

  final int communityId;
  final List<PresenceMember> members;
}

class PresenceMemberStateChanged extends RealtimePresenceEvent {
  const PresenceMemberStateChanged({
    required this.communityId,
    required this.member,
    required this.joined,
  });

  final int communityId;
  final PresenceMember member;
  final bool joined;
}

class PresenceTypingEvent extends RealtimePresenceEvent {
  const PresenceTypingEvent({
    required this.communityId,
    required this.member,
    required this.isTyping,
    this.timeout,
  });

  final int communityId;
  final PresenceMember member;
  final bool isTyping;
  final Duration? timeout;
}

class PresenceNotificationEvent extends RealtimePresenceEvent {
  const PresenceNotificationEvent({required this.payload});

  final Map<String, dynamic> payload;
}

typedef _ChannelFactory = WebSocketChannel Function(Uri uri, {Iterable<String>? protocols});

class RealtimePresenceService {
  RealtimePresenceService({_ChannelFactory? channelFactory})
      : _channelFactory = channelFactory ??
            ((uri, {protocols}) => WebSocketChannel.connect(uri, protocols: protocols));

  final _ChannelFactory _channelFactory;
  final StreamController<RealtimePresenceEvent> _eventsController =
      StreamController<RealtimePresenceEvent>.broadcast();
  final Set<int> _subscriptions = <int>{};
  final Map<int, DateTime> _lastTypingEmitted = <int, DateTime>{};

  RealtimePresenceConfig? _config;
  WebSocketChannel? _channel;
  Timer? _heartbeatTimer;
  Timer? _reconnectTimer;
  int _retryAttempt = 0;
  bool _explicitlyClosed = false;
  String? _authToken;

  Stream<RealtimePresenceEvent> get events => _eventsController.stream;

  void updateConfig(RealtimePresenceConfig? config) {
    if (_config == config) {
      return;
    }
    _config = config;
    _restartConnection();
  }

  void updateAuthToken(String? token) {
    _authToken = token;
    if (_channel != null) {
      _send({
        'action': 'authenticate',
        'token': token,
      });
    }
  }

  void subscribe(int communityId) {
    if (_subscriptions.add(communityId)) {
      if (_channel != null) {
        _send({'action': 'subscribe', 'community_id': communityId});
      }
    }
  }

  void unsubscribe(int communityId) {
    if (_subscriptions.remove(communityId)) {
      if (_channel != null) {
        _send({'action': 'unsubscribe', 'community_id': communityId});
      }
    }
  }

  Future<void> trackTyping(int communityId) async {
    final config = _config;
    if (config == null) {
      return;
    }
    final now = DateTime.now();
    final lastSent = _lastTypingEmitted[communityId];
    if (lastSent != null && now.difference(lastSent) < config.typingDebounce) {
      return;
    }

    _lastTypingEmitted[communityId] = now;
    _send({
      'action': 'typing',
      'community_id': communityId,
      'token': _authToken,
    });
  }

  void dispose() {
    _explicitlyClosed = true;
    _reconnectTimer?.cancel();
    _heartbeatTimer?.cancel();
    _channel?.sink.close();
    _eventsController.close();
  }

  void _restartConnection() {
    _reconnectTimer?.cancel();
    _heartbeatTimer?.cancel();
    _channel?.sink.close();
    _channel = null;
    _retryAttempt = 0;
    _explicitlyClosed = false;

    if (_config != null) {
      _connect();
    }
  }

  void _connect() {
    final config = _config;
    if (config == null || _explicitlyClosed) {
      return;
    }

    try {
      _channel = _channelFactory(config.socketUrl, protocols: const <String>['websocket']);
      _eventsController.add(const PresenceConnectionStateChanged(connected: true));
      _channel!.stream.listen(
        _handleMessage,
        onError: (Object error, StackTrace stack) {
          TelemetryService.instance.recordError(error, stack);
          _scheduleReconnect(error);
        },
        onDone: () {
          _scheduleReconnect('socket closed');
        },
        cancelOnError: true,
      );

      if (_authToken != null && _authToken!.isNotEmpty) {
        _send({'action': 'authenticate', 'token': _authToken});
      }
      for (final id in _subscriptions) {
        _send({'action': 'subscribe', 'community_id': id});
      }

      _heartbeatTimer?.cancel();
      _heartbeatTimer = Timer.periodic(config.heartbeatInterval, (_) {
        _send({'action': 'ping', 'timestamp': DateTime.now().toIso8601String()});
      });
    } catch (error, stack) {
      TelemetryService.instance.recordError(error, stack);
      _scheduleReconnect(error);
    }
  }

  void _handleMessage(dynamic raw) {
    if (raw is! String) {
      return;
    }

    Map<String, dynamic> message;
    try {
      message = jsonDecode(raw) as Map<String, dynamic>;
    } catch (error, stack) {
      TelemetryService.instance.recordError(error, stack);
      return;
    }

    final type = message['type'] as String? ?? message['event'] as String? ?? '';
    switch (type) {
      case 'connected':
      case 'pong':
        _retryAttempt = 0;
        _eventsController.add(const PresenceConnectionStateChanged(connected: true));
        break;
      case 'presence.sync':
        final communityId = message['community_id'] as int? ?? 0;
        final members = (message['members'] as List<dynamic>? ?? const <dynamic>[]) 
            .map((dynamic entry) => PresenceMember.fromJson(Map<String, dynamic>.from(entry as Map)))
            .toList(growable: false);
        _eventsController.add(PresenceMembersSynced(communityId: communityId, members: members));
        break;
      case 'presence.join':
        final communityId = message['community_id'] as int? ?? 0;
        final member = PresenceMember.fromJson(
          Map<String, dynamic>.from(message['member'] as Map? ?? message),
        );
        _eventsController.add(
          PresenceMemberStateChanged(communityId: communityId, member: member, joined: true),
        );
        break;
      case 'presence.leave':
        final communityId = message['community_id'] as int? ?? 0;
        final member = PresenceMember.fromJson(
          Map<String, dynamic>.from(message['member'] as Map? ?? message),
        );
        _eventsController.add(
          PresenceMemberStateChanged(communityId: communityId, member: member, joined: false),
        );
        break;
      case 'typing.start':
      case 'typing':
        _forwardTyping(message, isTyping: true);
        break;
      case 'typing.stop':
        _forwardTyping(message, isTyping: false);
        break;
      case 'notification':
        final payload = Map<String, dynamic>.from(message['payload'] as Map? ?? message);
        _eventsController.add(PresenceNotificationEvent(payload: payload));
        break;
      default:
        break;
    }
  }

  void _forwardTyping(Map<String, dynamic> message, {required bool isTyping}) {
    final communityId = message['community_id'] as int? ?? 0;
    final timeoutMs = message['timeout_ms'] as int?;
    final member = PresenceMember.fromJson(
      Map<String, dynamic>.from(message['member'] as Map? ?? message),
    );
    _eventsController.add(
      PresenceTypingEvent(
        communityId: communityId,
        member: member,
        isTyping: isTyping,
        timeout: timeoutMs != null ? Duration(milliseconds: timeoutMs) : null,
      ),
    );
  }

  void _scheduleReconnect(Object? error) {
    if (_explicitlyClosed) {
      return;
    }

    _heartbeatTimer?.cancel();
    _channel?.sink.close();
    _channel = null;

    final config = _config;
    if (config == null) {
      return;
    }

    _retryAttempt += 1;
    final delay = config.backoffForAttempt(_retryAttempt);
    _eventsController.add(
      PresenceConnectionStateChanged(connected: false, error: error, retryIn: delay),
    );

    _reconnectTimer?.cancel();
    _reconnectTimer = Timer(delay, _connect);
  }

  void _send(Map<String, dynamic> payload) {
    final channel = _channel;
    if (channel == null) {
      return;
    }

    try {
      channel.sink.add(jsonEncode(payload));
    } catch (error, stack) {
      TelemetryService.instance.recordError(error, stack);
    }
  }
}
