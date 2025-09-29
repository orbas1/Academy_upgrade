import 'package:meta/meta.dart';

@immutable
class CommunityLeaderboardEntry {
  const CommunityLeaderboardEntry({
    required this.memberId,
    required this.displayName,
    required this.points,
    required this.rank,
  });

  factory CommunityLeaderboardEntry.fromJson(Map<String, dynamic> json, int index) {
    return CommunityLeaderboardEntry(
      memberId: json['member_id'] as int,
      displayName: json['display_name'] as String? ?? 'Member',
      points: json['points'] as int? ?? 0,
      rank: index + 1,
    );
  }

  final int memberId;
  final String displayName;
  final int points;
  final int rank;
}

