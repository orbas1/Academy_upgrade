import 'package:meta/meta.dart';

@immutable
class SearchResponse {
  const SearchResponse({
    required this.index,
    required this.query,
    required this.hits,
    required this.estimatedTotalHits,
    required this.limit,
    required this.offset,
    required this.appliedFilters,
    required this.nextCursor,
  });

  final String index;
  final String query;
  final List<Map<String, dynamic>> hits;
  final int estimatedTotalHits;
  final int limit;
  final int offset;
  final List<String> appliedFilters;
  final String? nextCursor;

  factory SearchResponse.fromJson(Map<String, dynamic> json) {
    final rawHits = json['hits'] as List<dynamic>? ?? <dynamic>[];
    final cursor = json['cursor'] as Map<String, dynamic>?;

    return SearchResponse(
      index: json['index'] as String? ?? '',
      query: json['query'] as String? ?? '',
      hits: rawHits
          .map((dynamic entry) => Map<String, dynamic>.from(
                entry as Map<String, dynamic>,
              ))
          .toList(growable: false),
      estimatedTotalHits: json['estimated_total_hits'] as int? ?? 0,
      limit: json['limit'] as int? ?? 0,
      offset: json['offset'] as int? ?? 0,
      appliedFilters: (json['applied_filters'] as List<dynamic>? ?? <dynamic>[])
          .map((dynamic entry) => entry.toString())
          .toList(growable: false),
      nextCursor: cursor != null ? cursor['next'] as String? : null,
    );
  }
}

