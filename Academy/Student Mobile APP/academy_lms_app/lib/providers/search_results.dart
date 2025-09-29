import 'dart:async';

import 'package:academy_lms_app/features/search/data/search_api.dart';
import 'package:academy_lms_app/features/search/models/search_response.dart';
import 'package:academy_lms_app/features/search/models/search_visibility_token.dart';
import 'package:flutter/foundation.dart';

class SearchResultsProvider extends ChangeNotifier {
  SearchResultsProvider({SearchApi? api}) : _api = api ?? SearchApi();

  final SearchApi _api;
  SearchResponse? _lastResponse;
  bool _isLoading = false;
  String? _errorMessage;

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  SearchResponse? get response => _lastResponse;
  List<Map<String, dynamic>> get hits => _lastResponse?.hits ?? <Map<String, dynamic>>[];

  void clear() {
    _lastResponse = null;
    _errorMessage = null;
    notifyListeners();
  }

  Future<void> search({
    required String query,
    required String index,
    required SearchVisibilityToken visibilityToken,
    String? authToken,
  }) async {
    if (query.isEmpty) {
      _lastResponse = null;
      notifyListeners();
      return;
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _lastResponse = await _api.query(
        index: index,
        query: query,
        visibilityToken: visibilityToken.token,
        filters: visibilityToken.filters,
        authToken: authToken,
      );
    } catch (error, stackTrace) {
      _errorMessage = error.toString();
      FlutterError.reportError(FlutterErrorDetails(
        exception: error,
        stack: stackTrace,
        library: 'search_results_provider',
      ));
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  @override
  void dispose() {
    unawaited(_api.dispose());
    super.dispose();
  }
}

