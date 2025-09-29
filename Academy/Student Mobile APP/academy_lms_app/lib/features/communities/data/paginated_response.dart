class PaginatedResponse<T> {
  const PaginatedResponse({required this.items, this.nextCursor});

  final List<T> items;
  final String? nextCursor;

  bool get hasMore => nextCursor != null && nextCursor!.isNotEmpty;

  PaginatedResponse<T> copyWith({List<T>? items, String? nextCursor}) {
    return PaginatedResponse<T>(
      items: items ?? this.items,
      nextCursor: nextCursor ?? this.nextCursor,
    );
  }

  static PaginatedResponse<T> empty<T>() {
    return PaginatedResponse<T>(items: <T>[]);
  }
}
