import 'dart:async';

/// Represents the high-level connection state emitted by the realtime channel.
enum PresenceConnectionState {
  disconnected,
  connecting,
  connected,
  reconnecting,
  error,
}

/// Describes events emitted by a [PresenceChannel].
class PresenceMessage {
  PresenceMessage.connection(this.connectionState)
      : type = PresenceMessageType.connection,
        member = null,
        members = const [],
        error = null;

  PresenceMessage.initial(this.members)
      : type = PresenceMessageType.initial,
        member = null,
        connectionState = null,
        error = null;

  PresenceMessage.join(this.member)
      : type = PresenceMessageType.join,
        members = const [],
        connectionState = null,
        error = null;

  PresenceMessage.leave(this.member)
      : type = PresenceMessageType.leave,
        members = const [],
        connectionState = null,
        error = null;

  PresenceMessage.error(this.error)
      : type = PresenceMessageType.error,
        members = const [],
        member = null,
        connectionState = PresenceConnectionState.error;

  final PresenceMessageType type;
  final PresenceMember? member;
  final List<PresenceMember> members;
  final PresenceConnectionState? connectionState;
  final Object? error;
}

enum PresenceMessageType { connection, initial, join, leave, error }

/// Represents a member connected to the realtime presence channel.
class PresenceMember {
  PresenceMember({
    required this.memberId,
    required this.displayName,
    required this.lastSeenAt,
    this.avatarUrl,
    Map<String, dynamic>? metadata,
  }) : metadata = Map<String, dynamic>.unmodifiable(
            Map<String, dynamic>.from(metadata ?? const <String, dynamic>{}),
          );

  factory PresenceMember.fromJson(Map<String, dynamic> json) {
    final metadata = Map<String, dynamic>.from(json['metadata'] as Map? ?? const {});
    return PresenceMember(
      memberId: json['id']?.toString() ?? '',
      displayName: json['name']?.toString() ?? 'Member',
      lastSeenAt: DateTime.tryParse(json['last_seen_at']?.toString() ?? '') ?? DateTime.now().toUtc(),
      avatarUrl: json['avatar_url']?.toString(),
      metadata: metadata,
    );
  }

  final String memberId;
  final String displayName;
  final DateTime lastSeenAt;
  final String? avatarUrl;
  final Map<String, dynamic> metadata;

  Map<String, dynamic> toJson() => <String, dynamic>{
        'id': memberId,
        'name': displayName,
        'last_seen_at': lastSeenAt.toIso8601String(),
        'avatar_url': avatarUrl,
        'metadata': Map<String, dynamic>.from(metadata),
      };
}

typedef PresenceChannelFactory = PresenceChannel Function();

abstract class PresenceChannel {
  Stream<PresenceMessage> get events;

  Future<void> connect({
    required String communityId,
    String? authToken,
  });

  Future<void> disconnect();

  Future<void> dispose();
}
