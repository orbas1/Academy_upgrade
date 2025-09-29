import 'package:flutter/foundation.dart';

import '../data/community_repository.dart';
import '../models/community_comment.dart';

class CommunityCommentsNotifier extends ChangeNotifier {
  CommunityCommentsNotifier({
    required CommunityRepository repository,
    required this.communityId,
    required this.postId,
  }) : _repository = repository;

  final CommunityRepository _repository;
  final int communityId;
  final int postId;

  List<CommunityComment> _comments = <CommunityComment>[];
  bool _isLoading = false;
  bool _isLoadingMore = false;
  bool _hasMore = false;
  String? _error;

  List<CommunityComment> get comments => _comments;
  bool get isLoading => _isLoading;
  bool get isLoadingMore => _isLoadingMore;
  bool get hasMore => _hasMore;
  String? get error => _error;

  Future<void> refresh({int pageSize = 20}) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _repository.loadComments(
        communityId,
        postId,
        resetCursor: true,
        pageSize: pageSize,
      );

      _comments = response.items;
      _hasMore = response.hasMore;
      _error = null;
    } catch (err) {
      _error = err.toString();
      _comments = <CommunityComment>[];
      _hasMore = false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> loadMore({int pageSize = 20}) async {
    if (_isLoadingMore || !_repository.hasMoreComments(communityId, postId)) {
      _hasMore = _repository.hasMoreComments(communityId, postId);
      return;
    }

    _isLoadingMore = true;
    notifyListeners();

    try {
      final response = await _repository.loadMoreComments(
        communityId,
        postId,
        pageSize: pageSize,
      );

      if (response.items.isNotEmpty) {
        _comments = <CommunityComment>[..._comments, ...response.items];
      }

      _hasMore = response.hasMore;
      _error = null;
    } catch (err) {
      _error = err.toString();
    } finally {
      _isLoadingMore = false;
      notifyListeners();
    }
  }

  Future<void> addComment(String bodyMarkdown, {int? parentId}) async {
    final comment = await _repository.createComment(
      communityId,
      postId,
      bodyMarkdown: bodyMarkdown,
      parentId: parentId,
    );

    _comments = <CommunityComment>[comment, ..._comments];
    _error = null;
    notifyListeners();
  }
}
