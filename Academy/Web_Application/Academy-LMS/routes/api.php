<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\frontend\CourseController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\V1\Admin\AdminSavedSearchController;
use App\Http\Controllers\Api\V1\Admin\AdminSearchController;
use App\Http\Controllers\Api\V1\Admin\CommunityModuleManifestController;
use App\Http\Controllers\Api\V1\Admin\SecretController as AdminSecretController;
use App\Http\Controllers\Api\V1\Billing\StripeWebhookController;
use App\Http\Controllers\Webhooks\MessagingWebhookController;
use App\Http\Controllers\Api\V1\Community\SearchAuthorizationController;
use App\Http\Controllers\Api\V1\Community\CommunityFeedController;
use App\Http\Controllers\Api\V1\Community\CommunityNotificationPreferenceController;
use App\Http\Controllers\Api\V1\Community\SearchQueryController;
use App\Http\Controllers\Api\V1\Ops\MigrationPlanController;
use App\Http\Controllers\Api\V1\Ops\MigrationRunbookController;
use App\Http\Controllers\Api\V1\Profile\AnalyticsConsentController;
use App\Http\Controllers\Api\V1\Queue\QueueHealthSummaryController;
use App\Http\Controllers\Api\V1\Security\DeviceSessionController;
use App\Http\Controllers\Monitoring\MetricsController;
use App\Http\Controllers\Api\Observability\MobileMetricController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/billing/stripe/webhook', StripeWebhookController::class)
    ->name('billing.stripe.webhook');

Route::post('/messaging/webhooks/{provider}', [MessagingWebhookController::class, 'handle'])
    ->name('messaging.webhook');

Route::post('/login', [ApiController::class, 'login']);
Route::post('/two-factor/verify', [ApiController::class, 'verifyTwoFactor']);
Route::post('/signup', [ApiController::class, 'signup']);
Route::post('/forgot_password', [ApiController::class, 'forgot_password']);

Route::middleware('observability.token')
    ->get('/internal/metrics', MetricsController::class)
    ->name('observability.metrics');

Route::middleware(['auth:sanctum'])
    ->post('/observability/mobile-metrics', [MobileMetricController::class, 'store'])
    ->name('observability.mobile-metrics');

Route::group(['middleware' => ['auth:sanctum', 'device.activity', 'role:member,moderator,owner,admin']], function () {
    Route::get('/top_courses', [ApiController::class, 'top_courses']);
    Route::get('/all_categories', [ApiController::class, 'all_categories']);
    Route::get('/categories', [ApiController::class, 'categories']);
    Route::get('/category_details', [ApiController::class, 'category_details']);
    Route::get('/sub_categories/{id}', [ApiController::class, 'sub_categories']);
    Route::get('/category_wise_course', [ApiController::class, 'category_wise_course']);
    Route::get('/category_subcategory_wise_course', [ApiController::class, 'category_subcategory_wise_course']);
    Route::get('/filter_course', [ApiController::class, 'filter_course']);
    Route::get('/my_wishlist', [ApiController::class, 'my_wishlist']);
    Route::get('/toggle_wishlist_items', [ApiController::class, 'toggle_wishlist_items']);
    Route::get('/languages', [ApiController::class, 'languages']);
    Route::get('/courses_by_search_string', [ApiController::class, 'courses_by_search_string']);
    Route::get('/my_courses', [ApiController::class, 'my_courses']);
    Route::get('/sections', [ApiController::class, 'sections']);
    Route::get('/course_details_by_id', [ApiController::class, 'course_details_by_id']);
    Route::post('/update_password', [ApiController::class, 'update_password']);
    Route::post('/update_userdata', [ApiController::class, 'update_userdata']);
    Route::post('/account_disable', [ApiController::class, 'account_disable']);
    Route::get('/cart_list', [ApiController::class, 'cart_list']);
    Route::get('/toggle_cart_items', [ApiController::class, 'toggle_cart_items']);
    Route::get('/save_course_progress', [ApiController::class, 'save_course_progress']);
    Route::post('/logout', [ApiController::class, 'logout']);
    Route::post('/storage/restore', [\App\Http\Controllers\Api\StorageController::class, 'requestRestore']);

    //Zoom live class
    Route::get('zoom/settings', [ApiController::class, 'zoom_settings']);
    Route::get('zoom/meetings', [ApiController::class, 'live_class_schedules']);

    Route::get('payment/{token}', [ApiController::class, 'payment']);
    Route::get('token', [ApiController::class, 'token']);

    Route::get('free_course_enroll/{course_id}', [ApiController::class, 'free_course_enroll']);

    Route::get('cart_tools', [ApiController::class, 'cart_tools']);
});

Route::prefix('v1')->group(function () {
    Route::get('/search/visibility-token', [SearchAuthorizationController::class, 'token'])
        ->middleware('throttle:120,1');

    Route::post('/search/query', SearchQueryController::class)
        ->middleware('throttle:240,1');

    Route::middleware(['auth:sanctum', 'device.activity'])->group(function () {
        Route::get('/communities', [\App\Http\Controllers\Api\V1\Community\CommunityController::class, 'index'])
            ->middleware('throttle:240,1');
        Route::post('/communities', [\App\Http\Controllers\Api\V1\Community\CommunityController::class, 'store'])
            ->middleware('throttle:60,1');
        Route::get('/communities/{community}', [\App\Http\Controllers\Api\V1\Community\CommunityController::class, 'show'])
            ->middleware('throttle:240,1');
        Route::put('/communities/{community}', [\App\Http\Controllers\Api\V1\Community\CommunityController::class, 'update'])
            ->middleware('throttle:120,1');
        Route::delete('/communities/{community}', [\App\Http\Controllers\Api\V1\Community\CommunityController::class, 'destroy'])
            ->middleware('throttle:60,1');

        Route::prefix('communities/{community}')->group(function () {
            Route::get('/feed', [CommunityFeedController::class, 'index'])
                ->middleware('throttle:240,1');
            Route::get('/feed/pinned', [CommunityFeedController::class, 'pinned'])
                ->middleware('throttle:240,1');
            Route::get('/members', [\App\Http\Controllers\Api\V1\Community\CommunityMemberController::class, 'index'])
                ->middleware('throttle:240,1');
            Route::put('/members/{member}', [\App\Http\Controllers\Api\V1\Community\CommunityMemberController::class, 'update'])
                ->middleware('throttle:120,1');
            Route::get('/geo/places', [\App\Http\Controllers\Api\V1\Community\CommunityGeoController::class, 'index'])
                ->middleware('throttle:240,1');
            Route::put('/geo/bounds', [\App\Http\Controllers\Api\V1\Community\CommunityGeoController::class, 'update'])
                ->middleware('throttle:120,1');
            Route::delete('/geo/places/{place}', [\App\Http\Controllers\Api\V1\Community\CommunityGeoController::class, 'destroy'])
                ->middleware('throttle:120,1');
            Route::get('/notification-preferences', [CommunityNotificationPreferenceController::class, 'show'])
                ->middleware('throttle:240,1');
            Route::put('/notification-preferences', [CommunityNotificationPreferenceController::class, 'update'])
                ->middleware('throttle:180,1');
            Route::delete('/notification-preferences', [CommunityNotificationPreferenceController::class, 'destroy'])
                ->middleware('throttle:120,1');
        });

        Route::post('/admin/search/audit', [AdminSearchController::class, 'audit'])
            ->middleware('throttle:180,1');

        Route::get('/admin/search/saved-queries', [AdminSavedSearchController::class, 'index'])
            ->middleware('throttle:180,1');
        Route::post('/admin/search/saved-queries', [AdminSavedSearchController::class, 'store'])
            ->middleware('throttle:120,1');
        Route::delete('/admin/search/saved-queries/{savedQuery}', [AdminSavedSearchController::class, 'destroy'])
            ->middleware('throttle:120,1');

        Route::get('/admin/communities/modules', CommunityModuleManifestController::class)
            ->middleware('throttle:120,1');

        Route::prefix('/admin/communities')
            ->middleware(['can:communities.manage', 'role:admin'])
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\CommunityController::class, 'index'])
                    ->middleware('throttle:240,1');
                Route::post('/', [\App\Http\Controllers\Api\V1\Admin\CommunityController::class, 'store'])
                    ->middleware('throttle:60,1');
                Route::get('/{community}', [\App\Http\Controllers\Api\V1\Admin\CommunityController::class, 'show'])
                    ->middleware('throttle:240,1');
                Route::put('/{community}', [\App\Http\Controllers\Api\V1\Admin\CommunityController::class, 'update'])
                    ->middleware('throttle:120,1');
                Route::delete('/{community}', [\App\Http\Controllers\Api\V1\Admin\CommunityController::class, 'destroy'])
                    ->middleware('throttle:60,1');

                Route::get('/{community}/metrics', [\App\Http\Controllers\Api\V1\Admin\CommunityMetricsController::class, 'show'])
                    ->middleware('throttle:240,1');
                Route::get('/{community}/members', [\App\Http\Controllers\Api\V1\Admin\CommunityMemberController::class, 'index'])
                    ->middleware('throttle:240,1');
                Route::get('/{community}/feed', [\App\Http\Controllers\Api\V1\Admin\CommunityFeedController::class, 'index'])
                    ->middleware('throttle:240,1');
                Route::post('/{community}/posts', [\App\Http\Controllers\Api\V1\Admin\CommunityPostController::class, 'store'])
                    ->middleware('throttle:60,1');
                Route::post('/{community}/posts/{post}/reactions', [\App\Http\Controllers\Api\V1\Admin\CommunityReactionController::class, 'store'])
                    ->middleware('throttle:120,1');
            });

        Route::get('/ops/queue-health', QueueHealthSummaryController::class)
            ->middleware('throttle:120,1');

        Route::get('/ops/migration-plan', [MigrationPlanController::class, 'index'])
            ->middleware('throttle:60,1');
        Route::get('/ops/migration-plan/{planKey}', [MigrationPlanController::class, 'show'])
            ->middleware('throttle:60,1');

        Route::get('/ops/migration-runbooks', [MigrationRunbookController::class, 'index'])
            ->middleware('throttle:60,1');
        Route::get('/ops/migration-runbooks/{runbookKey}', [MigrationRunbookController::class, 'show'])
            ->middleware('throttle:60,1');

        Route::post('/me/analytics-consent', AnalyticsConsentController::class)
            ->middleware('throttle:60,1');

        Route::get('/admin/secrets/{key}', [AdminSecretController::class, 'show'])
            ->middleware(['throttle:60,1', 'can:secrets.manage', 'role:admin']);
        Route::post('/admin/secrets/{key}/rotate', [AdminSecretController::class, 'rotate'])
            ->middleware(['throttle:30,1', 'can:secrets.manage', 'role:admin']);

        Route::get('/security/device-sessions', [DeviceSessionController::class, 'index'])
            ->middleware('role:member,moderator,owner,admin')
            ->name('api.security.device-sessions.index');
        Route::delete('/security/device-sessions/{device}', [DeviceSessionController::class, 'destroy'])
            ->middleware('role:member,moderator,owner,admin')
            ->name('api.security.device-sessions.destroy');
        Route::patch('/security/device-sessions/{device}', [DeviceSessionController::class, 'update'])
            ->middleware('role:member,moderator,owner,admin')
            ->name('api.security.device-sessions.update');
    });
