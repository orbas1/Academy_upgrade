import 'dart:convert';
import 'dart:math';

import 'package:sqflite/sqflite.dart';

typedef OfflineQueueDatabaseBuilder = Future<Database> Function();

enum CommunityOfflineActionType { createPost }

class CommunityOfflineAction {
  const CommunityOfflineAction({
    required this.type,
    required this.communityId,
    required this.payload,
    this.clientReference,
  });

  final CommunityOfflineActionType type;
  final int communityId;
  final Map<String, dynamic> payload;
  final String? clientReference;

  Map<String, Object?> toRecord(DateTime now) {
    return <String, Object?>{
      'action_type': type.name,
      'community_id': communityId,
      'payload': jsonEncode(payload),
      'client_reference': clientReference,
      'state': 'pending',
      'attempts': 0,
      'created_at': now.millisecondsSinceEpoch,
      'updated_at': now.millisecondsSinceEpoch,
    };
  }
}

class QueuedCommunityAction {
  const QueuedCommunityAction({
    required this.id,
    required this.type,
    required this.communityId,
    required this.payload,
    required this.state,
    required this.attempts,
    this.clientReference,
    this.lastError,
    this.nextAttemptAt,
    required this.createdAt,
    required this.updatedAt,
  });

  factory QueuedCommunityAction.fromRow(Map<String, Object?> row) {
    final typeName = row['action_type'] as String;
    return QueuedCommunityAction(
      id: row['id'] as int,
      type: CommunityOfflineActionType.values.firstWhere(
        (value) => value.name == typeName,
        orElse: () => CommunityOfflineActionType.createPost,
      ),
      communityId: row['community_id'] as int,
      payload: Map<String, dynamic>.from(
        jsonDecode(row['payload'] as String) as Map<String, dynamic>,
      ),
      clientReference: row['client_reference'] as String?,
      attempts: row['attempts'] as int? ?? 0,
      lastError: row['last_error'] as String?,
      nextAttemptAt: (row['next_attempt_at'] as int?) != null
          ? DateTime.fromMillisecondsSinceEpoch(row['next_attempt_at'] as int)
          : null,
      createdAt: DateTime.fromMillisecondsSinceEpoch(row['created_at'] as int),
      updatedAt: DateTime.fromMillisecondsSinceEpoch(row['updated_at'] as int),
      state: row['state'] as String? ?? 'pending',
    );
  }

  final int id;
  final CommunityOfflineActionType type;
  final int communityId;
  final Map<String, dynamic> payload;
  final String state;
  final int attempts;
  final String? clientReference;
  final String? lastError;
  final DateTime? nextAttemptAt;
  final DateTime createdAt;
  final DateTime updatedAt;

  QueuedCommunityAction copyWith({
    int? attempts,
    String? lastError,
    String? state,
    DateTime? nextAttemptAt,
    DateTime? updatedAt,
  }) {
    return QueuedCommunityAction(
      id: id,
      type: type,
      communityId: communityId,
      payload: payload,
      state: state ?? this.state,
      attempts: attempts ?? this.attempts,
      clientReference: clientReference,
      lastError: lastError ?? this.lastError,
      nextAttemptAt: nextAttemptAt ?? this.nextAttemptAt,
      createdAt: createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }
}

class CommunityOfflineProcessReport {
  const CommunityOfflineProcessReport({
    required this.successes,
    required this.permanentlyFailed,
  });

  const CommunityOfflineProcessReport.empty()
      : successes = const <QueuedCommunityAction>[],
        permanentlyFailed = const <QueuedCommunityAction>[];

  final List<QueuedCommunityAction> successes;
  final List<QueuedCommunityAction> permanentlyFailed;

  bool get hasChanges => successes.isNotEmpty || permanentlyFailed.isNotEmpty;
}

class OfflineCommunityActionQueue {
  OfflineCommunityActionQueue({
    OfflineQueueDatabaseBuilder? databaseBuilder,
    Duration? maxBackoff,
  })  : _databaseBuilder = databaseBuilder ?? OfflineCommunityActionQueue._defaultBuilder,
        _maxBackoff = maxBackoff ?? const Duration(minutes: 5);

  final OfflineQueueDatabaseBuilder _databaseBuilder;
  final Duration _maxBackoff;

  Database? _database;

  static Future<Database> _defaultBuilder() async {
    final path = await getDatabasesPath();
    return openDatabase(
      '$path/community_offline_actions.db',
      version: 1,
      onCreate: (db, version) async {
        await db.execute('''
          CREATE TABLE offline_actions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            action_type TEXT NOT NULL,
            community_id INTEGER NOT NULL,
            payload TEXT NOT NULL,
            client_reference TEXT,
            state TEXT NOT NULL DEFAULT 'pending',
            attempts INTEGER NOT NULL DEFAULT 0,
            last_error TEXT,
            next_attempt_at INTEGER,
            created_at INTEGER NOT NULL,
            updated_at INTEGER NOT NULL
          )
        ''');
        await db.execute('CREATE INDEX idx_offline_actions_state ON offline_actions(state);');
        await db.execute('CREATE INDEX idx_offline_actions_next_attempt ON offline_actions(next_attempt_at);');
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

  Future<int> enqueue(CommunityOfflineAction action) async {
    final db = await _databaseInstance();
    final now = DateTime.now();
    return db.insert('offline_actions', action.toRecord(now));
  }

  Future<int> pendingCount() async {
    final db = await _databaseInstance();
    final now = DateTime.now().millisecondsSinceEpoch;
    final result = Sqflite.firstIntValue(await db.rawQuery(
      'SELECT COUNT(*) FROM offline_actions WHERE state = ? AND (next_attempt_at IS NULL OR next_attempt_at <= ?)',
      <Object?>['pending', now],
    ));
    return result ?? 0;
  }

  Future<CommunityOfflineProcessReport> process({
    required Future<void> Function(QueuedCommunityAction action) handler,
    int maxAttempts = 5,
    int batchSize = 10,
  }) async {
    final db = await _databaseInstance();
    final now = DateTime.now().millisecondsSinceEpoch;
    final rows = await db.query(
      'offline_actions',
      where: 'state = ? AND (next_attempt_at IS NULL OR next_attempt_at <= ?)',
      whereArgs: <Object?>['pending', now],
      orderBy: 'created_at ASC',
      limit: batchSize,
    );

    if (rows.isEmpty) {
      return const CommunityOfflineProcessReport.empty();
    }

    final successes = <QueuedCommunityAction>[];
    final failures = <QueuedCommunityAction>[];

    for (final row in rows) {
      final action = QueuedCommunityAction.fromRow(row);
      try {
        await handler(action);
        await db.delete('offline_actions', where: 'id = ?', whereArgs: <Object?>[action.id]);
        successes.add(action);
      } catch (err) {
        final attempts = action.attempts + 1;
        final message = err.toString();
        final updatedAt = DateTime.now().millisecondsSinceEpoch;
        if (attempts >= maxAttempts) {
          await db.update(
            'offline_actions',
            <String, Object?>{
              'attempts': attempts,
              'state': 'failed',
              'last_error': message,
              'updated_at': updatedAt,
            },
            where: 'id = ?',
            whereArgs: <Object?>[action.id],
          );
          failures.add(action.copyWith(
            attempts: attempts,
            lastError: message,
            state: 'failed',
            updatedAt: DateTime.fromMillisecondsSinceEpoch(updatedAt),
          ));
        } else {
          final backoff = _computeBackoff(attempts);
          await db.update(
            'offline_actions',
            <String, Object?>{
              'attempts': attempts,
              'last_error': message,
              'next_attempt_at': DateTime.now().add(backoff).millisecondsSinceEpoch,
              'updated_at': updatedAt,
            },
            where: 'id = ?',
            whereArgs: <Object?>[action.id],
          );
        }
      }
    }

    return CommunityOfflineProcessReport(successes: successes, permanentlyFailed: failures);
  }

  Duration _computeBackoff(int attempts) {
    final seconds = pow(2, attempts - 1).toInt();
    final duration = Duration(seconds: seconds * 2);
    if (duration > _maxBackoff) {
      return _maxBackoff;
    }
    return duration;
  }

  Future<void> purgeFailedOlderThan(Duration age) async {
    final db = await _databaseInstance();
    final threshold = DateTime.now().subtract(age).millisecondsSinceEpoch;
    await db.delete(
      'offline_actions',
      where: 'state = ? AND updated_at < ?',
      whereArgs: <Object?>['failed', threshold],
    );
  }

  Future<void> close() async {
    await _database?.close();
    _database = null;
  }
}
