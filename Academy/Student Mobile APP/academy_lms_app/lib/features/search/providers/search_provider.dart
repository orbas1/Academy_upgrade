import 'dart:async';

import 'package:academy_lms_app/features/search/data/search_api.dart';
import 'package:academy_lms_app/features/search/models/search_result.dart';
import 'package:academy_lms_app/features/search/models/search_visibility_token.dart';
import 'package:flutter/foundation.dart';

class SearchProvider extends ChangeNotifier {
  SearchProvider({SearchApi? api}) : _api = api ?? SearchApi();

  final SearchApi _api;

  SearchVisibilityToken? _visibilityToken;
  String? _authToken;
  bool _isLoading = false;
  String? _error;
  SearchResultPayload? _latestPayload;
  SearchScope _scope = SearchScope.communities;

  bool get isLoading => _isLoading;
  String? get error => _error;
  SearchResultPayload? get results => _latestPayload;
  SearchScope get scope => _scope;

  void updateContext({String? authToken, SearchVisibilityToken? visibilityToken}) {
    _authToken = authToken;
    _visibilityToken = visibilityToken;
  }

  Future<void> search({
    required String query,
    SearchScope scope = SearchScope.communities,
    Map<String, dynamic>? filters,
    String? sort,
  }) async {
    if (query.trim().isEmpty) {
      _error = 'Search query cannot be empty';
      notifyListeners();
      return;
    }

    final token = _visibilityToken;
    if (token == null || token.isExpired) {
      _error = 'Search context expired. Refresh and try again.';
      notifyListeners();
      return;
    }

    _scope = scope;
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _latestPayload = await _api.execute(
        visibilityToken: token,
        scope: scope,
        query: query.trim(),
        authToken: _authToken,
        filters: filters,
        sort: sort,
      );
    } catch (error, stackTrace) {
      _error = error.toString();
      debugPrintStack(label: 'SearchProvider', stackTrace: stackTrace);
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  @override
  void dispose() {
    _api.dispose();
    super.dispose();
  }
}

