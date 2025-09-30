import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:meta/meta.dart';

import '../../../services/realtime/realtime_presence_service.dart';

class CommunityPresenceNotifier extends ChangeNotifier {
  CommunityPresenceNotifier({
    RealtimePresenceService? presenceService,
    Duration typingDisplayDuration = const Duration(seconds: 6),
  })  : _typingDisplayDuration = typingDisplayDuration {
    if (presenceService != null) {
      attachService(presenceService);
    }
  }

  final Duration _typingDisplayDuration;
  final Map<int, Map<int, PresenceMember>> _onlineMembers = <int, Map<int, PresenceMember>>{};
  final Map<int, Map<int, _TypingStatus>> _typingMembers = <int, Map<int, _TypingStatus>>{};

  RealtimePresenceService? _service;
  StreamSubscription<RealtimePresenceEvent>? _subscription;
  bool _connected = false;
  String? _connectionError;
  String? _authToken;
  Map<String, dynamic>? _lastNotificationPayload;

  bool get isConnected => _connected;

  String? get connectionError => _connectionError;

  Map<String, dynamic>? get lastNotificationPayload => _lastNotificationPayload;

  List<PresenceMember> typingMembersFor(int communityId) {
    _pruneExpiredTyping(communityId);
    final members = _typingMembers[communityId];
    if (members == null) {
      return const <PresenceMember>[];
    }
    return members.values.map((status) => status.member).toList(growable: false);
  }

  Set<PresenceMember> onlineMembersFor(int communityId) {
    final members = _onlineMembers[communityId];
    if (members == null) {
      return <PresenceMember>{};
    }
    return members.values.toSet();
  }

  void attachService(RealtimePresenceService service) {
    if (identical(_service, service)) {
      return;
    }
    _subscription?.cancel();
    _service = service;
    _subscription = service.events.listen(_handleEvent);
    if (_authToken != null) {
      service.updateAuthToken(_authToken);
    }
  }

  void updateAuthToken(String? token) {
    _authToken = token;
    _service?.updateAuthToken(token);
  }

  void updateRealtimeConfig(RealtimePresenceConfig? config) {
    _service?.updateConfig(config);
  }

  void watchCommunity(int communityId) {
    _service?.subscribe(communityId);
  }

  void unwatchCommunity(int communityId) {
    _service?.unsubscribe(communityId);
    _onlineMembers.remove(communityId);
    _typingMembers.remove(communityId);
    notifyListeners();
  }

  Future<void> markTyping(int communityId) {
    return _service?.trackTyping(communityId) ?? Future<void>.value();
  }

  @visibleForTesting
  void debugApplyEvent(RealtimePresenceEvent event) {
    _handleEvent(event);
  }

  @override
  void dispose() {
    for (final entry in _typingMembers.values) {
      for (final status in entry.values) {
        status.cancel();
      }
    }
    _typingMembers.clear();
    _subscription?.cancel();
    super.dispose();
  }

  void _handleEvent(RealtimePresenceEvent event) {
    if (event is PresenceConnectionStateChanged) {
      _connected = event.connected;
      _connectionError = event.connected ? null : event.error?.toString();
      notifyListeners();
      return;
    }

    if (event is PresenceMembersSynced) {
      _onlineMembers[event.communityId] = {
        for (final member in event.members) member.memberId: member,
      };
      notifyListeners();
      return;
    }

    if (event is PresenceMemberStateChanged) {
      final members = _onlineMembers.putIfAbsent(event.communityId, () => <int, PresenceMember>{});
      if (event.joined) {
        members[event.member.memberId] = event.member;
      } else {
        members.remove(event.member.memberId);
        if (members.isEmpty) {
          _onlineMembers.remove(event.communityId);
        }
      }
      notifyListeners();
      return;
    }

    if (event is PresenceTypingEvent) {
      final typingMap = _typingMembers.putIfAbsent(event.communityId, () => <int, _TypingStatus>{});
      final existing = typingMap[event.member.memberId];
      existing?.cancel();

      if (event.isTyping) {
        final timeout = event.timeout ?? _typingDisplayDuration;
        final status = _TypingStatus(member: event.member);
        typingMap[event.member.memberId] = status;
        status.timer = Timer(timeout, () {
          final current = typingMap[event.member.memberId];
          if (current == status) {
            typingMap.remove(event.member.memberId);
            if (typingMap.isEmpty) {
              _typingMembers.remove(event.communityId);
            }
            notifyListeners();
          }
        });
      } else {
        typingMap.remove(event.member.memberId);
        if (typingMap.isEmpty) {
          _typingMembers.remove(event.communityId);
        }
      }
      notifyListeners();
      return;
    }

    if (event is PresenceNotificationEvent) {
      _lastNotificationPayload = event.payload;
      notifyListeners();
      return;
    }
  }

  void _pruneExpiredTyping(int communityId) {
    final typingMap = _typingMembers[communityId];
    if (typingMap == null) {
      return;
    }

    final expiredIds = <int>[];
    for (final entry in typingMap.entries) {
      if (!entry.value.isActive) {
        expiredIds.add(entry.key);
      }
    }
    for (final id in expiredIds) {
      typingMap.remove(id);
    }
    if (typingMap.isEmpty) {
      _typingMembers.remove(communityId);
    }
  }
}

class _TypingStatus {
  _TypingStatus({required this.member});

  final PresenceMember member;
  Timer? timer;

  bool get isActive => timer?.isActive ?? false;

  void cancel() {
    timer?.cancel();
  }
}
