import 'package:academy_lms_app/services/api_envelope.dart';
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
    required this.sort,
    required this.hasMore,
    this.nextCursor,
    this.previousCursor,
    required this.facets,
    this.requestId,
    this.timestamp,
  });

  final String index;
  final String query;
  final List<Map<String, dynamic>> hits;
  final int estimatedTotalHits;
  final int limit;
  final int offset;
  final List<String> appliedFilters;
  final List<String> sort;
  final bool hasMore;
  final String? nextCursor;
  final String? previousCursor;
  final Map<String, dynamic> facets;
  final String? requestId;
  final DateTime? timestamp;

  factory SearchResponse.fromEnvelope(ApiEnvelope envelope) {
    final data = envelope.requireMapData();
    final rawHits = data['hits'] as List<dynamic>? ?? <dynamic>[];
    final cursor = data['cursor'] is Map<String, dynamic>
        ? Map<String, dynamic>.from(data['cursor'] as Map<String, dynamic>)
        : const <String, dynamic>{};
    final facets = data['facets'] is Map<String, dynamic>
        ? Map<String, dynamic>.from(data['facets'] as Map<String, dynamic>)
        : <String, dynamic>{};

    final appliedFilters = (envelope.meta['applied_filters'] as List<dynamic>? ??
            data['applied_filters'] as List<dynamic>? ??
            const <dynamic>[])
        .map((dynamic entry) => entry.toString())
        .toList(growable: false);

    final sortValues = (envelope.meta['sort'] as List<dynamic>? ??
            data['sort'] as List<dynamic>? ??
            const <dynamic>[])
        .map((dynamic entry) => entry.toString())
        .toList(growable: false);

    final hits = rawHits
        .map((dynamic entry) => Map<String, dynamic>.from(entry as Map<String, dynamic>))
        .toList(growable: false);

    final nextCursor = envelope.nextCursor ?? cursor['next'] as String?;
    final previousCursor = envelope.previousCursor ?? cursor['previous'] as String?;

    return SearchResponse(
      index: data['index'] as String? ?? '',
      query: data['query'] as String? ?? '',
      hits: hits,
      estimatedTotalHits:
          envelope.meta['estimated_total_hits'] as int? ?? data['estimated_total_hits'] as int? ?? hits.length,
      limit: envelope.limit ?? data['limit'] as int? ?? hits.length,
      offset: envelope.offset ?? data['offset'] as int? ?? 0,
      appliedFilters: appliedFilters,
      sort: sortValues,
      hasMore: envelope.hasMore,
      nextCursor: nextCursor,
      previousCursor: previousCursor,
      facets: facets,
      requestId: envelope.requestId,
      timestamp: envelope.timestamp,
    );
  }
}

