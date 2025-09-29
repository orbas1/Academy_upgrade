import 'dart:convert';

class ApiEnvelope {
  ApiEnvelope({
    required this.data,
    required this.meta,
    required this.errors,
  });

  final dynamic data;
  final Map<String, dynamic> meta;
  final List<Map<String, dynamic>> errors;

  factory ApiEnvelope.fromJson(String body) {
    final dynamic decoded = jsonDecode(body);
    if (decoded is! Map<String, dynamic>) {
      throw const FormatException('API response must be a JSON object.');
    }

    return ApiEnvelope.fromMap(decoded);
  }

  factory ApiEnvelope.fromMap(Map<String, dynamic> map) {
    final rawMeta = map['meta'];
    final meta = rawMeta is Map<String, dynamic>
        ? Map<String, dynamic>.from(rawMeta)
        : <String, dynamic>{};

    final rawErrors = map['errors'];
    final errors = <Map<String, dynamic>>[];
    if (rawErrors is List) {
      for (final error in rawErrors) {
        if (error is Map<String, dynamic>) {
          errors.add(Map<String, dynamic>.from(error));
        }
      }
    }

    return ApiEnvelope(
      data: map['data'],
      meta: meta,
      errors: errors,
    );
  }

  Map<String, dynamic> get pagination => meta['pagination'] is Map<String, dynamic>
      ? Map<String, dynamic>.from(meta['pagination'] as Map<String, dynamic>)
      : const <String, dynamic>{};

  String? get nextCursor => pagination['next_cursor'] as String?;
  String? get previousCursor => pagination['previous_cursor'] as String?;

  bool get hasMore {
    final dynamic explicit = pagination['has_more'];
    if (explicit is bool) {
      return explicit;
    }

    if (explicit is num) {
      return explicit != 0;
    }

    final dynamic next = pagination['next_cursor'];
    if (next is String) {
      return next.isNotEmpty;
    }

    return false;
  }

  int? get limit {
    final dynamic value = pagination['limit'] ?? pagination['per_page'];
    if (value is int) {
      return value;
    }
    if (value is num) {
      return value.toInt();
    }
    return null;
  }

  int? get offset {
    final dynamic value = pagination['offset'];
    if (value is int) {
      return value;
    }
    if (value is num) {
      return value.toInt();
    }
    return null;
  }

  int? get count {
    final dynamic value = pagination['count'];
    if (value is int) {
      return value;
    }
    if (value is num) {
      return value.toInt();
    }
    return null;
  }

  int? get estimatedTotal {
    final dynamic value = pagination['estimated_total'];
    if (value is int) {
      return value;
    }
    if (value is num) {
      return value.toInt();
    }
    return null;
  }

  String? get requestId => meta['request_id'] as String?;

  DateTime? get timestamp {
    final dynamic value = meta['timestamp'];
    if (value is String) {
      return DateTime.tryParse(value);
    }
    return null;
  }

  bool get isSuccess => errors.isEmpty;

  String? get firstErrorMessage {
    if (errors.isEmpty) {
      return null;
    }
    final detail = errors.first['detail'];
    if (detail is String && detail.isNotEmpty) {
      return detail;
    }
    final title = errors.first['title'];
    if (title is String && title.isNotEmpty) {
      return title;
    }
    return null;
  }

  Map<String, dynamic> requireMapData() {
    if (data is Map<String, dynamic>) {
      return Map<String, dynamic>.from(data as Map<String, dynamic>);
    }
    throw const FormatException('Expected response data to be an object.');
  }

  List<dynamic> requireListData() {
    if (data is List) {
      return List<dynamic>.from(data as List);
    }
    throw const FormatException('Expected response data to be an array.');
  }
}
