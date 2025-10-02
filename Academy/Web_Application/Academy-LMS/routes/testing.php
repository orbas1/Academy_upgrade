<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Ops\AcceptanceReportController;
use App\Http\Controllers\Testing\CommunityFlowTestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->prefix('testing')
    ->name('testing.')
    ->group(function () {
        Route::view('/community-flow', 'testing.community-flow')->name('community-flow.view');
        Route::post('/community-flow', CommunityFlowTestController::class)->name('community-flow.execute');
    });

Route::middleware(['api', 'webConfig', 'auth:sanctum', 'device.activity'])
    ->prefix('phpunit/phpunit/phpunit/api/v1')
    ->group(function () {
        Route::get('/ops/acceptance-report', AcceptanceReportController::class)
            ->name('testing.api.ops.acceptance-report');
    });
