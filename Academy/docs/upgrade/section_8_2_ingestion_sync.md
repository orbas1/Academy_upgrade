# Section 8.2 – Ingestion & Sync

## Overview

This tranche introduces an enterprise-grade ingestion pipeline that streams relational data into Meilisearch with deterministic chunking, CDC hooks, and operational tooling. The implementation complements the index configuration added in Section 8.1 and unlocks continuous discovery experiences for communities, posts, and members.

## Architecture

### Components

- **`config/search.php`** – central catalogue of searchable resources, including target indexes, data sources, transformers, and chunk sizes.
- **Data sources** (`App\Domain\Search\DataSources\*`) – stream DB records as `LazyCollection` instances with schema-aware fallbacks.
- **Transformers** (`App\Domain\Search\Transformers\*`) – normalise relational rows and model instances into Meilisearch documents.
- **`SearchSyncManager`** – orchestrates full and incremental sync, dispatches queue jobs, and handles inline ingestion.
- **Queue jobs** (`DispatchResourceSync`, `PushDocumentsToIndex`, `RemoveDocumentsFromIndex`) – ensure ingestion work is resilient and horizontally scalable.
- **`search:ingest` command** – operator entrypoint for scheduled or ad-hoc reindexing.
- **Model CDC trait** (`App\Domain\Search\Concerns\Searchable`) – wires eloquent lifecycle events to incremental updates.

### Data Flow

1. Operators (or CI) call `php artisan search:ingest` to queue or synchronously execute ingestion.
2. `SearchSyncManager` resolves the configured data sources and transformers, streaming DB rows in deterministic chunks.
3. Each chunk is enqueued to the `search-index` queue via `PushDocumentsToIndex`, which calls the hardened Meilisearch client.
4. Model-level changes trigger CDC via the `Searchable` trait, ensuring near-real-time freshness without full re-indexing.
5. Deletions dispatch `RemoveDocumentsFromIndex` jobs, keeping the index clean.

### Resilience & Observability

- Uses `LazyCollection::chunk` to cap memory usage and allow arbitrarily large datasets.
- Jobs are isolated on the `search-index` queue for autoscaling.
- Errors bubble through Laravel’s job failure pipeline and are logged with context by `SearchSyncManager`.
- Chunk overrides and synchronous mode support debugging and incident response.

## Operations

### Full Reindex

```bash
php artisan search:ingest                    # queue all resources
php artisan search:ingest members --sync     # run inline for members only
php artisan search:ingest posts --chunk=200  # override chunk size for posts
```

### CDC Enablement

Apply the `Searchable` trait to any eloquent model covered in `config('search.sync.resources')`. Example (`App\Models\User`):

```php
use App\Domain\Search\Concerns\Searchable;

class User extends Authenticatable
{
    use Searchable;
}
```

The trait automatically dispatches upserts and deletions, reusing configured transformers to keep payloads consistent.

## Deployment Checklist

- [x] Register new ingestion command in `SearchServiceProvider`.
- [x] Ensure Horizon/workers monitor the `search-index` queue.
- [x] Inject required environment variables (`MEILISEARCH_*`, optional per-resource chunk overrides).
- [x] Backfill indexes using `search:ingest --sync` during initial rollout, then switch to queued mode.
- [x] Add cron/CI schedule to run `search:ingest` nightly as a safety net.
- [x] Monitor job failures and Meilisearch health endpoints post-deploy.

## Next Steps

- Extend data sources to leverage the forthcoming community & post domain models as they land.
- Attach dedicated observers to new aggregate roots (e.g., `Community`, `CommunityPost`).
- Emit ingestion metrics (duration, chunk sizes, failure counts) to the observability stack in Section 10.
