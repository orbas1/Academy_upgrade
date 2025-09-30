@extends('admin.layouts.master')

@section('title', 'Community Detail')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ $community->name }}</h1>
            <p class="text-muted mb-0">{{ $community->tagline ?? 'No tagline provided.' }}</p>
        </div>
        <div>
            <a href="{{ route('admin.communities.index') }}" class="btn btn-outline-secondary">Back to list</a>
            <a href="{{ route('admin.communities.export', $community->getKey()) }}" class="btn btn-primary">Export members</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">DAU / WAU / MAU</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['dau'] }} / {{ $metrics['wau'] }} / {{ $metrics['mau'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Retention 7 / 28 / 90</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($metrics['retention_7'] * 100, 1) }}% / {{ number_format($metrics['retention_28'] * 100, 1) }}% / {{ number_format($metrics['retention_90'] * 100, 1) }}%</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">MRR / ARPU / LTV</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($metrics['mrr'], 2) }} / ${{ number_format($metrics['arpu'], 2) }} / ${{ number_format($metrics['ltv'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Conversion to First Post</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($metrics['conversion_first_post'] * 100, 1) }}%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Moderation Queue ({{ $metrics['queue_size'] }})</h6>
                    <span class="text-muted small">{{ number_format($metrics['posts_per_minute'], 2) }} posts/min</span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($feed as $post)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ $post['author']['name'] }}</strong>
                                        <p class="mb-1 text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($post['body_html']), 120) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-secondary">{{ $post['created_at'] }}</span>
                                        <div class="small text-muted">{{ $post['comment_count'] }} comments · {{ $post['like_count'] }} reactions</div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No items in moderation queue.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Members</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($members as $member)
                            <tr>
                                <td>{{ $member['name'] }}</td>
                                <td>{{ ucfirst($member['role']) }}</td>
                                <td>{{ ucfirst($member['status']) }}</td>
                                <td>{{ $member['joined_at'] ? \Carbon\CarbonImmutable::parse($member['joined_at'])->diffForHumans() : '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
