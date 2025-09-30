<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminCommunityService;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommunityDashboardController extends Controller
{
    public function __construct(private readonly AdminCommunityService $service)
    {
        $this->middleware(['admin', 'admin.ip', 'audit.log']);
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'visibility', 'category']);
        $perPage = (int) $request->input('per_page', 25);
        $cursor = $request->input('cursor');

        $results = $this->service->summarizeCommunities($filters, $perPage, $cursor);

        /** @var CursorPaginator $paginator */
        $paginator = $results['paginator'];

        return view('admin.communities.index', [
            'communities' => $paginator,
            'total' => $results['total'],
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, int $communityId): View
    {
        $community = $this->service->findCommunityById($communityId);
        $metrics = $this->service->loadMetrics($community);
        $members = $this->service->loadMembers($community, ['per_page' => 10]);
        $feed = $this->service->loadFeed($community, $request->user(), 'moderation', 10);

        return view('admin.communities.show', [
            'community' => $community,
            'metrics' => $metrics,
            'members' => $members['paginator'],
            'feed' => $feed['paginator'],
        ]);
    }

    public function exportMembers(int $communityId): StreamedResponse
    {
        $community = $this->service->findCommunityById($communityId);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => sprintf('attachment; filename="community-%d-members.csv"', $communityId),
        ];

        $callback = function () use ($community): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Member ID', 'Name', 'Email', 'Role', 'Status', 'Joined At']);

            $cursor = null;
            do {
                $page = $this->service->loadMembers($community, ['per_page' => 100], 100, $cursor);
                /** @var CursorPaginator $paginator */
                $paginator = $page['paginator'];

                foreach ($paginator->items() as $member) {
                    fputcsv($handle, [
                        $member['id'],
                        $member['name'],
                        $member['email'] ?? '',
                        $member['role'],
                        $member['status'],
                        $member['joined_at'],
                    ]);
                }

                $cursor = $paginator->nextCursor()?->encode();
            } while ($cursor);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
