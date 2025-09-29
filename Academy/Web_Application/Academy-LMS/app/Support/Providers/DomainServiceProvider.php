<?php

namespace App\Support\Providers;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Domain\Communities\Observers\CommunityObserver;
use App\Domain\Communities\Observers\CommunityMemberObserver;
use App\Domain\Communities\Observers\CommunityPostCommentObserver;
use App\Domain\Communities\Observers\CommunityPostObserver;
use App\Domain\Courses\Contracts\CourseCatalogReader;
use App\Domain\Courses\Services\EloquentCourseCatalogReader;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CourseCatalogReader::class => EloquentCourseCatalogReader::class,
    ];

    public function boot(): void
    {
        Community::observe(CommunityObserver::class);
        CommunityMember::observe(CommunityMemberObserver::class);
        CommunityPost::observe(CommunityPostObserver::class);
        CommunityPostComment::observe(CommunityPostCommentObserver::class);
    }
}
