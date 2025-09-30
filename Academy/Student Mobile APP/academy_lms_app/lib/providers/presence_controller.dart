import 'dart:async';

import 'package:flutter/foundation.dart';

import '../services/realtime/presence_channel.dart';
import '../services/realtime/websocket_presence_channel.dart';

class PresenceController extends ChangeNotifier {
  PresenceController({PresenceChannelFactory? channelFactory})
      : _channelFactory = channelFactory ?? (() => WebSocketPresenceChannel());

  final PresenceChannelFactory _channelFactory;

  PresenceChannel? _channel;
  StreamSubscription<PresenceMessage>? _subscription;
  final Map<String, PresenceMember> _members = <String, PresenceMember>{};
  PresenceConnectionState _state = PresenceConnectionState.disconnected;
  String? _activeCommunityId;
  String? _authToken;
  String? _errorMessage;

  PresenceConnectionState get state => _state;
  String? get errorMessage => _errorMessage;
  List<PresenceMember> get members => List<PresenceMember>.unmodifiable(_members.values);
  int get onlineCount => _members.length;

  void updateAuthToken(String? token) {
    _authToken = token;
  }

  Future<void> connectToCommunity(String communityId) async {
    if (_activeCommunityId == communityId &&
        _state != PresenceConnectionState.disconnected &&
        _state != PresenceConnectionState.error) {
      return;
    }

    await _teardownChannel();
    _setState(PresenceConnectionState.connecting);
    _activeCommunityId = communityId;
    _channel = _channelFactory();
    _subscription = _channel!.events.listen(_handleEvent, onError: _handleError);
    await _channel!.connect(communityId: communityId, authToken: _authToken);
  }

  Future<void> disconnect() async {
    await _teardownChannel();
    _members.clear();
    _activeCommunityId = null;
    _setState(PresenceConnectionState.disconnected);
  }

  @override
  void dispose() {
    unawaited(_teardownChannel());
    super.dispose();
  }

  void _handleEvent(PresenceMessage message) {
    switch (message.type) {
      case PresenceMessageType.connection:
        if (message.connectionState != null) {
          _setState(message.connectionState!);
        }
        if (message.connectionState == PresenceConnectionState.error) {
          _errorMessage = 'Realtime connection failed';
        }
        break;
      case PresenceMessageType.initial:
        _members
          ..clear()
          ..addEntries(
            message.members.map((PresenceMember member) => MapEntry(member.memberId, member)),
          );
        _errorMessage = null;
        notifyListeners();
        break;
      case PresenceMessageType.join:
        if (message.member != null) {
          _members[message.member!.memberId] = message.member!;
          notifyListeners();
        }
        break;
      case PresenceMessageType.leave:
        if (message.member != null) {
          _members.remove(message.member!.memberId);
          notifyListeners();
        }
        break;
      case PresenceMessageType.error:
        _errorMessage = message.error?.toString();
        _setState(PresenceConnectionState.error);
        break;
    }
  }

  void _handleError(Object error, StackTrace stackTrace) {
    _errorMessage = error.toString();
    _setState(PresenceConnectionState.error);
  }

  Future<void> _teardownChannel() async {
    await _subscription?.cancel();
    _subscription = null;
    await _channel?.disconnect();
    await _channel?.dispose();
    _channel = null;
  }

  void _setState(PresenceConnectionState state) {
    if (_state == state) {
      return;
    }
    _state = state;
    notifyListeners();
  }
}
