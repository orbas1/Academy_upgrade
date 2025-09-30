import 'dart:convert';

import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

import '../models/community_feed_item.dart';
import '../models/community_summary.dart';
import 'paginated_response.dart';

typedef DatabaseBuilder = Future<Database> Function();

class CommunityCache {
  CommunityCache({
    DatabaseBuilder? databaseBuilder,
    Duration? communityListTtl,
    Duration? feedTtl,
  })  : _databaseBuilder = databaseBuilder ?? CommunityCache._defaultBuilder,
        communityListTtl = communityListTtl ?? const Duration(minutes: 15),
        feedTtl = feedTtl ?? const Duration(minutes: 5);

  final DatabaseBuilder _databaseBuilder;
  final Duration communityListTtl;
  final Duration feedTtl;

  Database? _database;

  static Future<Database> _defaultBuilder() async {
    final databasesPath = await getDatabasesPath();
    final dbPath = p.join(databasesPath, 'community_cache.db');

    return openDatabase(
      dbPath,
      version: 1,
      onConfigure: (db) async {
        await db.execute('PRAGMA journal_mode=WAL;');
      },
      onCreate: (db, version) async {
        await db.execute('''
          CREATE TABLE community_lists (
            filter TEXT PRIMARY KEY,
            payload TEXT NOT NULL,
            next_cursor TEXT,
            has_more INTEGER NOT NULL DEFAULT 0,
            updated_at INTEGER NOT NULL
          )
        ''');

        await db.execute('''
          CREATE TABLE community_feeds (
            community_id INTEGER NOT NULL,
            filter TEXT NOT NULL,
            payload TEXT NOT NULL,
            next_cursor TEXT,
            has_more INTEGER NOT NULL DEFAULT 0,
            updated_at INTEGER NOT NULL,
            PRIMARY KEY (community_id, filter)
          )
        ''');

        await db.execute('CREATE INDEX IF NOT EXISTS idx_community_feeds_updated_at ON community_feeds(updated_at);');
        await db.execute('CREATE INDEX IF NOT EXISTS idx_community_lists_updated_at ON community_lists(updated_at);');
      },
    );
  }

  Future<Database> _databaseInstance() async {
    if (_database != null) {
      return _database!;
    }

    _database = await _databaseBuilder();
    return _database!;
  }

  Future<void> writeCommunityList(String filter, PaginatedResponse<CommunitySummary> response) async {
    final db = await _databaseInstance();
    final payload = jsonEncode(response.items.map((item) => item.toJson()).toList());
    final now = DateTime.now().millisecondsSinceEpoch;

    await db.insert(
      'community_lists',
      <String, Object?>{
        'filter': filter,
        'payload': payload,
        'next_cursor': response.nextCursor,
        'has_more': response.hasMore ? 1 : 0,
        'updated_at': now,
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );

    await _pruneExpired(db);
  }

  Future<void> writeCommunityFeed(
    int communityId,
    String filter,
    PaginatedResponse<CommunityFeedItem> response,
  ) async {
    final db = await _databaseInstance();
    final payload = jsonEncode(
      response.items
          .map((item) => item.toJson())
          .toList(),
    );
    final now = DateTime.now().millisecondsSinceEpoch;

    await db.insert(
      'community_feeds',
      <String, Object?>{
        'community_id': communityId,
        'filter': filter,
        'payload': payload,
        'next_cursor': response.nextCursor,
        'has_more': response.hasMore ? 1 : 0,
        'updated_at': now,
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );

    await _pruneExpired(db);
  }

  Future<PaginatedResponse<CommunitySummary>?> readCommunityList(String filter) async {
    final db = await _databaseInstance();
    final rows = await db.query(
      'community_lists',
      where: 'filter = ?',
      whereArgs: <Object?>[filter],
      limit: 1,
    );

    if (rows.isEmpty) {
      return null;
    }

    final row = rows.first;
    if (_isExpired(row['updated_at'] as int?, communityListTtl)) {
      await db.delete('community_lists', where: 'filter = ?', whereArgs: <Object?>[filter]);
      return null;
    }

    final items = (jsonDecode(row['payload'] as String) as List<dynamic>)
        .map((dynamic entry) => CommunitySummary.fromJson(Map<String, dynamic>.from(entry as Map)))
        .toList();

    return PaginatedResponse<CommunitySummary>(
      items: items,
      nextCursor: row['next_cursor'] as String?,
      hasMore: (row['has_more'] as int? ?? 0) == 1,
    );
  }

  Future<PaginatedResponse<CommunityFeedItem>?> readCommunityFeed(
    int communityId,
    String filter,
  ) async {
    final db = await _databaseInstance();
    final rows = await db.query(
      'community_feeds',
      where: 'community_id = ? AND filter = ?',
      whereArgs: <Object?>[communityId, filter],
      limit: 1,
    );

    if (rows.isEmpty) {
      return null;
    }

    final row = rows.first;
    if (_isExpired(row['updated_at'] as int?, feedTtl)) {
      await db.delete(
        'community_feeds',
        where: 'community_id = ? AND filter = ?',
        whereArgs: <Object?>[communityId, filter],
      );
      return null;
    }

    final items = (jsonDecode(row['payload'] as String) as List<dynamic>)
        .map((dynamic entry) => CommunityFeedItem.fromJson(Map<String, dynamic>.from(entry as Map)))
        .toList();

    return PaginatedResponse<CommunityFeedItem>(
      items: items,
      nextCursor: row['next_cursor'] as String?,
      hasMore: (row['has_more'] as int? ?? 0) == 1,
    );
  }

  Future<void> clear() async {
    final db = await _databaseInstance();
    await db.delete('community_lists');
    await db.delete('community_feeds');
  }

  Future<void> close() async {
    await _database?.close();
    _database = null;
  }

  Future<void> _pruneExpired(Database db) async {
    final now = DateTime.now();
    final listThreshold = now.subtract(communityListTtl).millisecondsSinceEpoch;
    final feedThreshold = now.subtract(feedTtl).millisecondsSinceEpoch;

    await db.delete(
      'community_lists',
      where: 'updated_at < ?',
      whereArgs: <Object?>[listThreshold],
    );

    await db.delete(
      'community_feeds',
      where: 'updated_at < ?',
      whereArgs: <Object?>[feedThreshold],
    );
  }

  bool _isExpired(int? timestamp, Duration ttl) {
    if (timestamp == null) {
      return true;
    }

    final updated = DateTime.fromMillisecondsSinceEpoch(timestamp, isUtc: false);
    return DateTime.now().difference(updated) > ttl;
  }
}
