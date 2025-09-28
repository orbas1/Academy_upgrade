<?php

namespace App\Domain\Courses\DTO;

use App\Domain\Shared\DataTransferObject;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseCatalogResult extends DataTransferObject
{
    /**
     * @param array<int, int> $wishlistCourseIds
     */
    public function __construct(
        public readonly LengthAwarePaginator $courses,
        public readonly array $wishlistCourseIds,
        public readonly ?Category $category,
        public readonly string $layout,
        public readonly ?string $parentCategorySlug,
        public readonly ?string $childCategorySlug,
    ) {
    }
}
