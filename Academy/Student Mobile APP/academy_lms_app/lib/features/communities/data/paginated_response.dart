class PaginatedResponse<T> {
  const PaginatedResponse({
    required this.items,
    this.nextCursor,
    bool? hasMore,
  }) : _hasMore = hasMore;

  final List<T> items;
  final String? nextCursor;
  final bool? _hasMore;

  bool get hasMore {
    if (_hasMore != null) {
      return _hasMore!;
    }
    return nextCursor != null && nextCursor!.isNotEmpty;
  }

  PaginatedResponse<T> copyWith({
    List<T>? items,
    String? nextCursor,
    bool? hasMore,
  }) {
    return PaginatedResponse<T>(
      items: items ?? this.items,
      nextCursor: nextCursor ?? this.nextCursor,
      hasMore: hasMore ?? _hasMore,
    );
  }

  static PaginatedResponse<T> empty<T>() {
    return PaginatedResponse<T>(items: <T>[], hasMore: false);
  }
}
