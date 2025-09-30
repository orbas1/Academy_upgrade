import 'dart:async';
import 'dart:convert';

import 'package:web_socket_channel/web_socket_channel.dart';

import '../../config/app_configuration.dart';
import 'presence_channel.dart';

typedef WebSocketConnector = WebSocketChannel Function(Uri uri);

class WebSocketPresenceChannel implements PresenceChannel {
  WebSocketPresenceChannel({
    AppConfiguration? configuration,
    WebSocketConnector? connector,
    Duration? heartbeatInterval,
    Duration? reconnectDelay,
    this.maxReconnectAttempts = 6,
  })  : _configuration = configuration ?? AppConfiguration.instance,
        _connector = connector ?? WebSocketChannel.connect,
        _heartbeatInterval = heartbeatInterval ?? const Duration(seconds: 30),
        _reconnectDelay = reconnectDelay ?? const Duration(seconds: 2);

  final AppConfiguration _configuration;
  final WebSocketConnector _connector;
  final Duration _heartbeatInterval;
  final Duration _reconnectDelay;
  final int maxReconnectAttempts;

  final StreamController<PresenceMessage> _controller =
      StreamController<PresenceMessage>.broadcast();

  WebSocketChannel? _channel;
  StreamSubscription<dynamic>? _subscription;
  Timer? _heartbeatTimer;
  Timer? _reconnectTimer;
  int _reconnectAttempts = 0;
  bool _intentionalDisconnect = false;
  String? _communityId;
  String? _authToken;

  @override
  Stream<PresenceMessage> get events => _controller.stream;

  @override
  Future<void> connect({required String communityId, String? authToken}) async {
    _communityId = communityId;
    _authToken = authToken;
    _intentionalDisconnect = false;
    _reconnectAttempts = 0;

    _controller.add(PresenceMessage.connection(PresenceConnectionState.connecting));

    await _openSocket();
  }

  @override
  Future<void> disconnect() async {
    _intentionalDisconnect = true;
    await _closeSocket();
    _controller.add(PresenceMessage.connection(PresenceConnectionState.disconnected));
  }

  @override
  Future<void> dispose() async {
    await disconnect();
    await _controller.close();
  }

  Future<void> _openSocket() async {
    await _closeSocket();
    if (_communityId == null) {
      throw StateError('Community id must be provided before connecting');
    }

    final uri = _configuration.buildRealtimePresenceUri(
      communityId: _communityId!,
      token: _authToken,
    );

    try {
      _channel = _connector(uri);
      _controller.add(PresenceMessage.connection(PresenceConnectionState.connected));
    } catch (error) {
      _controller.add(PresenceMessage.error(error));
      _scheduleReconnect();
      return;
    }

    _subscription = _channel!.stream.listen(
      _handleMessage,
      onError: (Object error, StackTrace stackTrace) {
        _controller.add(PresenceMessage.error(error));
        _scheduleReconnect();
      },
      onDone: _handleDisconnect,
      cancelOnError: true,
    );

    _sendSubscribeFrame();
    _startHeartbeat();
  }

  void _sendSubscribeFrame() {
    final payload = jsonEncode(<String, dynamic>{
      'type': 'subscribe',
      'channel': 'community.presence',
      'community_id': _communityId,
      if (_authToken != null && _authToken!.isNotEmpty) 'token': _authToken,
    });

    _channel?.sink.add(payload);
  }

  void _handleMessage(dynamic data) {
    if (data is! String) {
      return;
    }

    final decoded = jsonDecode(data) as Map<String, dynamic>;
    final type = decoded['type']?.toString() ?? '';

    switch (type) {
      case 'presence.initial':
        final members = (decoded['members'] as List<dynamic>? ?? const [])
            .map((dynamic item) => PresenceMember.fromJson(Map<String, dynamic>.from(item as Map)))
            .toList(growable: false);
        _controller.add(PresenceMessage.initial(members));
        break;
      case 'presence.join':
        final member = PresenceMember.fromJson(
          Map<String, dynamic>.from(decoded['member'] as Map? ?? const {}),
        );
        _controller.add(PresenceMessage.join(member));
        break;
      case 'presence.leave':
        final member = PresenceMember.fromJson(
          Map<String, dynamic>.from(decoded['member'] as Map? ?? const {}),
        );
        _controller.add(PresenceMessage.leave(member));
        break;
      case 'ping':
        _channel?.sink.add(jsonEncode(<String, String>{'type': 'pong'}));
        break;
      case 'pong':
        // noop
        break;
      default:
        // Unknown message types are ignored but surfaced as diagnostic events.
        _controller.add(PresenceMessage.error(UnsupportedError('Unknown realtime message: $type')));
    }
  }

  void _handleDisconnect() {
    _stopHeartbeat();
    _subscription?.cancel();
    _subscription = null;

    if (_intentionalDisconnect) {
      _controller.add(PresenceMessage.connection(PresenceConnectionState.disconnected));
      return;
    }

    _scheduleReconnect();
  }

  Future<void> _closeSocket() async {
    _stopHeartbeat();
    await _subscription?.cancel();
    _subscription = null;
    await _channel?.sink.close();
    _channel = null;
  }

  void _startHeartbeat() {
    _heartbeatTimer?.cancel();
    _heartbeatTimer = Timer.periodic(_heartbeatInterval, (_) {
      _channel?.sink.add(jsonEncode(<String, String>{'type': 'ping'}));
    });
  }

  void _stopHeartbeat() {
    _heartbeatTimer?.cancel();
    _heartbeatTimer = null;
  }

  void _scheduleReconnect() {
    if (_intentionalDisconnect) {
      return;
    }

    if (_reconnectAttempts >= maxReconnectAttempts) {
      _controller.add(PresenceMessage.connection(PresenceConnectionState.error));
      return;
    }

    _reconnectAttempts += 1;
    _controller.add(PresenceMessage.connection(PresenceConnectionState.reconnecting));

    _reconnectTimer?.cancel();
    final delay = _reconnectDelay * _reconnectAttempts;
    _reconnectTimer = Timer(delay, () {
      _openSocket();
    });
  }
}
