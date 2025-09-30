@extends('admin.layouts.master')

@section('title', 'Communities Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Communities Overview</h1>
        <form class="form-inline" method="get" action="{{ route('admin.communities.index') }}">
            <input type="text" name="search" class="form-control mr-2" placeholder="Search by name or slug" value="{{ $filters['search'] ?? '' }}">
            <select name="visibility" class="form-control mr-2">
                <option value="">All visibilities</option>
                <option value="public" @selected(($filters['visibility'] ?? '') === 'public')>Public</option>
                <option value="private" @selected(($filters['visibility'] ?? '') === 'private')>Private</option>
                <option value="paid" @selected(($filters['visibility'] ?? '') === 'paid')>Paid</option>
            </select>
            <button class="btn btn-primary" type="submit">Filter</button>
        </form>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Communities ({{ number_format($total) }})</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Members</th>
                            <th>Online</th>
                            <th>Posts/Day</th>
                            <th>MRR</th>
                            <th>Flags</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($communities as $community)
                        <tr>
                            <td>
                                <strong>{{ $community['name'] }}</strong><br>
                                <span class="text-muted small">{{ $community['tagline'] ?? 'No description' }}</span>
                            </td>
                            <td>{{ number_format($community['member_count']) }}</td>
                            <td>{{ number_format($community['online_now'] ?? 0) }}</td>
                            <td>{{ number_format($community['posts_per_day'] ?? 0, 1) }}</td>
                            <td>${{ number_format($community['mrr'] ?? 0, 2) }}</td>
                            <td><span class="badge badge-{{ ($community['open_flags'] ?? 0) > 0 ? 'danger' : 'success' }}">{{ $community['open_flags'] ?? 0 }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('admin.communities.show', $community['id']) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span>Showing {{ $communities->count() }} of {{ number_format($total) }} communities</span>
            <div>
                {{ $communities->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
