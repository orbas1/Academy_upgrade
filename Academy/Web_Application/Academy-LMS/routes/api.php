<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\frontend\CourseController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\V1\Admin\AdminSavedSearchController;
use App\Http\Controllers\Api\V1\Admin\AdminSearchController;
use App\Http\Controllers\Api\V1\Billing\StripeWebhookController;
use App\Http\Controllers\Api\V1\Community\SearchAuthorizationController;
use App\Http\Controllers\Api\V1\Community\CommunityNotificationPreferenceController;
use App\Http\Controllers\Api\V1\Community\SearchQueryController;
use App\Http\Controllers\Api\V1\Queue\QueueHealthSummaryController;


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

Route::post('/login', [ApiController::class, 'login']);
Route::post('/two-factor/verify', [ApiController::class, 'verifyTwoFactor']);
Route::post('/signup', [ApiController::class, 'signup']);
Route::post('/forgot_password', [ApiController::class, 'forgot_password']);

Route::group(['middleware', ['auth:sanctum']], function () {
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

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('communities/{community}')->group(function () {
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

        Route::get('/ops/queue-health', QueueHealthSummaryController::class)
            ->middleware('throttle:120,1');
    });
});

