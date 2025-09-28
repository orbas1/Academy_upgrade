<?php

namespace App\Support\Providers;

use App\Domain\Courses\Contracts\CourseCatalogReader;
use App\Domain\Courses\Services\EloquentCourseCatalogReader;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CourseCatalogReader::class => EloquentCourseCatalogReader::class,
    ];
}
