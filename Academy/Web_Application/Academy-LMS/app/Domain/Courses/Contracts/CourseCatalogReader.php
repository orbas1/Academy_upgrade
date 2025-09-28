<?php

namespace App\Domain\Courses\Contracts;

use App\Domain\Courses\DTO\CourseCatalogResult;
use App\Domain\Courses\DTO\CourseFiltersData;

interface CourseCatalogReader
{
    public function fetchCatalog(CourseFiltersData $filters, string $layout): CourseCatalogResult;
}
