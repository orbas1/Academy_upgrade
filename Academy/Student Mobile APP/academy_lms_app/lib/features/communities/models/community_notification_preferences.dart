class CommunityNotificationPreferences {
  const CommunityNotificationPreferences({
    required this.communityId,
    required this.channelEmail,
    required this.channelPush,
    required this.channelInApp,
    required this.digestFrequency,
    required this.mutedEvents,
    this.locale,
  });

  factory CommunityNotificationPreferences.fromJson(Map<String, dynamic> json) {
    return CommunityNotificationPreferences(
      communityId: json['community_id'] as int? ?? 0,
      channelEmail: json['channel_email'] as bool? ?? true,
      channelPush: json['channel_push'] as bool? ?? true,
      channelInApp: json['channel_in_app'] as bool? ?? true,
      digestFrequency: json['digest_frequency'] as String? ?? 'daily',
      mutedEvents: List<String>.from(json['muted_events'] as List<dynamic>? ?? const <String>[]),
      locale: json['locale'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'channel_email': channelEmail,
      'channel_push': channelPush,
      'channel_in_app': channelInApp,
      'digest_frequency': digestFrequency,
      'muted_events': mutedEvents,
      if (locale != null) 'locale': locale,
    };
  }

  CommunityNotificationPreferences copyWith({
    bool? channelEmail,
    bool? channelPush,
    bool? channelInApp,
    String? digestFrequency,
    List<String>? mutedEvents,
    String? locale,
  }) {
    return CommunityNotificationPreferences(
      communityId: communityId,
      channelEmail: channelEmail ?? this.channelEmail,
      channelPush: channelPush ?? this.channelPush,
      channelInApp: channelInApp ?? this.channelInApp,
      digestFrequency: digestFrequency ?? this.digestFrequency,
      mutedEvents: mutedEvents ?? this.mutedEvents,
      locale: locale ?? this.locale,
    );
  }

  final int communityId;
  final bool channelEmail;
  final bool channelPush;
  final bool channelInApp;
  final String digestFrequency;
  final List<String> mutedEvents;
  final String? locale;
}
