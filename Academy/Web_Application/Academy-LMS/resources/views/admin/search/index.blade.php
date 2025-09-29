@extends('layouts.admin')

@push('title')
    {{ get_phrase('Search Intelligence') }}
@endpush

@section('content')
    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ get_phrase('Create Saved Search') }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.search.saved.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="search-name">{{ get_phrase('Name') }}</label>
                            <input type="text" name="name" id="search-name" class="form-control"
                                   value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="search-scope">{{ get_phrase('Scope') }}</label>
                            <select name="scope" id="search-scope" class="form-select" required>
                                <option value="" disabled selected>{{ get_phrase('Choose scope') }}</option>
                                @foreach($scopes as $scope)
                                    <option value="{{ $scope }}" @selected(old('scope') === $scope)>
                                        {{ ucfirst($scope) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="search-query">{{ get_phrase('Query (optional)') }}</label>
                            <input type="text" name="query" id="search-query" class="form-control"
                                   value="{{ old('query') }}" maxlength="200">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="search-filters">{{ get_phrase('Filters (JSON)') }}</label>
                            <textarea name="filters" id="search-filters" class="form-control" rows="4"
                                      placeholder='{"visibility":"community"}'>{{ old('filters') }}</textarea>
                            <small class="text-muted">{{ get_phrase('Use JSON to define field/value pairs. Leave blank for none.') }}</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="search-sort">{{ get_phrase('Sort (field:direction)') }}</label>
                            <input type="text" name="sort" id="search-sort" class="form-control"
                                   value="{{ old('sort') }}" placeholder="recent_activity_at:desc">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="search-frequency">{{ get_phrase('Alert cadence') }}</label>
                            <select name="frequency" id="search-frequency" class="form-select">
                                <option value="none" @selected(old('frequency') === 'none')>{{ get_phrase('Manual only') }}</option>
                                <option value="hourly" @selected(old('frequency') === 'hourly')>{{ get_phrase('Hourly') }}</option>
                                <option value="daily" @selected(old('frequency') === 'daily')>{{ get_phrase('Daily') }}</option>
                                <option value="weekly" @selected(old('frequency') === 'weekly')>{{ get_phrase('Weekly') }}</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">{{ get_phrase('Save Search') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ get_phrase('Saved Searches') }}</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th>{{ get_phrase('Name') }}</th>
                            <th>{{ get_phrase('Scope') }}</th>
                            <th>{{ get_phrase('Frequency') }}</th>
                            <th>{{ get_phrase('Last run') }}</th>
                            <th class="text-end">{{ get_phrase('Actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($savedSearches as $saved)
                            <tr>
                                <td>{{ $saved->name }}</td>
                                <td>{{ ucfirst($saved->scope) }}</td>
                                <td>{{ ucfirst($saved->frequency) }}</td>
                                <td>{{ optional($saved->last_triggered_at)->diffForHumans() ?? get_phrase('Never') }}</td>
                                <td class="text-end">
                                    <form class="d-inline" method="POST" action="{{ route('admin.search.run') }}">
                                        @csrf
                                        <input type="hidden" name="saved_search_id" value="{{ $saved->id }}">
                                        <button class="btn btn-sm btn-outline-primary">{{ get_phrase('Run') }}</button>
                                    </form>
                                    <form class="d-inline" method="POST" action="{{ route('admin.search.saved.destroy', $saved) }}"
                                          onsubmit="return confirm('{{ get_phrase('Delete saved search?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">{{ get_phrase('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ get_phrase('No saved searches yet.') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ get_phrase('Recent Audit Logs') }}</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <thead>
                        <tr>
                            <th>{{ get_phrase('Timestamp') }}</th>
                            <th>{{ get_phrase('Scope') }}</th>
                            <th>{{ get_phrase('Query') }}</th>
                            <th>{{ get_phrase('Result count') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->executed_at->toDayDateTimeString() }}</td>
                                <td>{{ ucfirst($log->scope) }}</td>
                                <td>{{ $log->query ?: get_phrase('—') }}</td>
                                <td>{{ $log->result_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">{{ get_phrase('No search activity recorded yet.') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($activeResult)
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">{{ get_phrase('Search Results') }}</h5>
                            <span class="text-muted small">{{ $activeResult['label'] }} · {{ ucfirst($activeResult['meta']['scope']) }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @php($result = $activeResult['data'])
                        @if(($result['meta']['scope'] ?? null) === 'all')
                            @foreach($result['data'] as $scope => $dataset)
                                <h6 class="mt-3">{{ ucfirst($scope) }} <span class="badge bg-secondary">{{ $dataset['total'] }} {{ get_phrase('matches') }}</span></h6>
                                <ul class="list-group list-group-flush mb-3">
                                    @forelse($dataset['hits'] as $hit)
                                        <li class="list-group-item">
                                            <strong>{{ $hit['attributes']['name'] ?? $hit['attributes']['title'] ?? get_phrase('Result') }}</strong>
                                            <div class="text-muted small">{{ get_phrase('ID:') }} {{ $hit['id'] ?? get_phrase('N/A') }}</div>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted">{{ get_phrase('No records in this scope.') }}</li>
                                    @endforelse
                                </ul>
                            @endforeach
                        @else
                            <p class="text-muted">{{ get_phrase('Total results:') }} {{ $result['total'] ?? 0 }}</p>
                            <ul class="list-group list-group-flush">
                                @forelse(($result['hits'] ?? []) as $hit)
                                    <li class="list-group-item">
                                        <strong>{{ $hit['attributes']['name'] ?? $hit['attributes']['title'] ?? get_phrase('Result') }}</strong>
                                        <div class="text-muted small">{{ get_phrase('ID:') }} {{ $hit['id'] ?? get_phrase('N/A') }}</div>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">{{ get_phrase('No matches found for this query.') }}</li>
                                @endforelse
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

