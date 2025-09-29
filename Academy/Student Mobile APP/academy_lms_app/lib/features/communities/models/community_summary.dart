import 'package:meta/meta.dart';

@immutable
class CommunitySummary {
  const CommunitySummary({
    required this.id,
    required this.slug,
    required this.name,
    required this.tagline,
    required this.memberCount,
    required this.isMember,
    required this.visibility,
  });

  factory CommunitySummary.fromJson(Map<String, dynamic> json) {
    return CommunitySummary(
      id: json['id'] as int,
      slug: json['slug'] as String,
      name: json['name'] as String,
      tagline: json['tagline'] as String? ?? '',
      memberCount: json['member_count'] as int? ?? 0,
      isMember: json['joined'] as bool? ?? false,
      visibility: json['visibility'] as String? ?? 'public',
    );
  }

  final int id;
  final String slug;
  final String name;
  final String tagline;
  final int memberCount;
  final bool isMember;
  final String visibility;

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'slug': slug,
      'name': name,
      'tagline': tagline,
      'member_count': memberCount,
      'joined': isMember,
      'visibility': visibility,
    };
  }
}
