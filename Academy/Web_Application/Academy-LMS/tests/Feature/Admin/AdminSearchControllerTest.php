<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\SearchAdminController;
use App\Http\Requests\Admin\Search\RunAdminSearchRequest;
use App\Http\Requests\Admin\Search\StoreSavedSearchRequest;
use App\Models\User;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

class AdminSearchControllerTest extends TestCase
{
    use UsesInMemoryDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('search.visibility.token_secret', 'testing-secret');
        config()->set('search.scopes', [
            'communities' => [
                'index' => 'communities',
                'allowed_filters' => ['visibility'],
                'allowed_sorts' => ['recent_activity_at'],
                'default_sort' => 'recent_activity_at:desc',
                'admin_allowed_filters' => ['status'],
            ],
        ]);

        $this->useInMemoryDatabase(function (): void {
            Schema::dropIfExists('users');
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('student');
                $table->rememberToken();
                $table->timestamps();
            });

            Schema::dropIfExists('admin_saved_searches');
            Schema::create('admin_saved_searches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id');
                $table->string('name');
                $table->string('scope');
                $table->string('query')->nullable();
                $table->json('filters')->nullable();
                $table->string('sort')->nullable();
                $table->string('frequency')->default('none');
                $table->timestamp('last_triggered_at')->nullable();
                $table->timestamps();
            });

            Schema::dropIfExists('search_audit_logs');
            Schema::create('search_audit_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->string('scope');
                $table->string('query')->nullable();
                $table->json('filters')->nullable();
                $table->unsignedInteger('result_count')->default(0);
                $table->boolean('is_admin')->default(false);
                $table->timestamp('executed_at');
                $table->timestamps();
            });
        });

        app('router')->get('/admin/search', fn () => 'ok')->name('admin.search.index');
    }

    public function test_admin_can_store_saved_search_and_run(): void
    {
        Http::fake([
            'http://meilisearch:7700/indexes/communities/search' => Http::response([
                'hits' => [],
                'estimatedTotalHits' => 0,
            ], 200),
        ]);

        $admin = User::query()->create([
            'name' => 'Search Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $controller = app(SearchAdminController::class);

        $storeRequest = StoreSavedSearchRequest::create('/admin/search/saved', 'POST', [
            'name' => 'Flagged communities',
            'scope' => 'communities',
            'query' => 'test',
            'filters' => json_encode(['visibility' => 'community', 'status' => 'flagged']),
            'sort' => 'recent_activity_at:desc',
            'frequency' => 'daily',
        ]);
        $storeRequest->setContainer(app());
        $storeRequest->setRedirector(app('redirect'));
        $storeRequest->setUserResolver(fn () => $admin);
        $storeRequest->validateResolved();

        $storeResponse = $controller->store($storeRequest);

        $this->assertInstanceOf(RedirectResponse::class, $storeResponse);
        $this->assertSame(route('admin.search.index'), $storeResponse->getTargetUrl());
        $this->assertDatabaseHas('admin_saved_searches', [
            'name' => 'Flagged communities',
            'user_id' => $admin->getKey(),
        ]);

        $saved = $admin->refresh()->adminSavedSearches()->firstOrFail();

        $runRequest = RunAdminSearchRequest::create('/admin/search/run', 'POST', [
            'saved_search_id' => $saved->id,
        ]);
        $runRequest->setContainer(app());
        $runRequest->setRedirector(app('redirect'));
        $runRequest->setUserResolver(fn () => $admin);
        $runRequest->validateResolved();

        $response = $controller->run($runRequest);

        $this->assertInstanceOf(ViewContract::class, $response);
        $this->assertSame('admin.search.index', $response->name());
        $this->assertDatabaseCount('search_audit_logs', 1);

        $recorded = Http::recorded();
        $this->assertNotEmpty($recorded);

        $last = $recorded->last();
        $filters = $last[0]->data()['filter'] ?? [];

        $this->assertContains("visibility = 'community'", $filters);
        $this->assertContains("status = 'flagged'", $filters);
    }
}

