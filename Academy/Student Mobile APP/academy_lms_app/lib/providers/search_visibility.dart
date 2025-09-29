import 'dart:async';

import 'package:academy_lms_app/features/search/data/search_visibility_api.dart';
import 'package:academy_lms_app/features/search/models/search_visibility_token.dart';
import 'package:flutter/foundation.dart';

class SearchVisibilityProvider extends ChangeNotifier {
  SearchVisibilityProvider({SearchVisibilityApi? api})
      : _api = api ?? SearchVisibilityApi();

  final SearchVisibilityApi _api;
  SearchVisibilityToken? _token;
  bool _isLoading = false;
  String? _errorMessage;
  String? _authToken;

  SearchVisibilityToken? get token => _token;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  Future<void> refreshToken() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _token = await _api.fetchVisibilityToken(authToken: _authToken);
    } catch (error, stackTrace) {
      _errorMessage = error.toString();
      FlutterError.reportError(FlutterErrorDetails(
        exception: error,
        stack: stackTrace,
        library: 'search_visibility_provider',
      ));
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void updateAuthToken(String? token, {bool forceRefresh = false}) {
    if (_authToken == token && !forceRefresh) {
      return;
    }

    _authToken = token;

    if (token == null) {
      _token = null;
      notifyListeners();
      return;
    }

    unawaited(refreshToken());
  }

  @override
  void dispose() {
    _api.dispose();
    super.dispose();
  }
}
