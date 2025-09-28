<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\CursorPaginator;
use Tests\TestCase;

class KeysetPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyset_pagination_returns_cursor_paginator(): void
    {
        User::factory()->count(30)->create();

        $paginator = User::query()->keysetPaginate(10);

        $this->assertInstanceOf(CursorPaginator::class, $paginator);
        $this->assertCount(10, $paginator->items());
        $this->assertTrue($paginator->hasMorePages());
    }

    public function test_keyset_pagination_caps_requested_page_size(): void
    {
        config(['database_performance.keyset.max_per_page' => 25]);

        User::factory()->count(50)->create();

        $paginator = User::query()->keysetPaginate(200);

        $this->assertCount(25, $paginator->items());
    }
}
