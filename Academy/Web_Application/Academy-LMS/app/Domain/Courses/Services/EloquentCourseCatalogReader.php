<?php

namespace App\Domain\Courses\Services;

use App\Domain\Courses\Contracts\CourseCatalogReader;
use App\Domain\Courses\DTO\CourseCatalogResult;
use App\Domain\Courses\DTO\CourseFiltersData;
use App\Models\Category;
use App\Models\Course;
use App\Models\Wishlist;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class EloquentCourseCatalogReader implements CourseCatalogReader
{
    public function __construct(private readonly CacheRepository $cache)
    {
    }

    public function fetchCatalog(CourseFiltersData $filters, string $layout): CourseCatalogResult
    {
        $query = Course::query()
            ->join('users', 'courses.user_id', '=', 'users.id')
            ->select(
                'courses.*',
                'users.name as instructor_name',
                'users.email as instructor_email',
                'users.photo as instructor_image'
            )
            ->where('courses.status', 'active');

        $categoryDetails = $this->applyCategoryFilter($query, $filters->categorySlug);
        [$parentCategorySlug, $childCategorySlug] = $this->resolveCategoryContext($categoryDetails);

        $this->applySearchFilters($query, $filters);
        $this->applyPriceFilter($query, $filters->price);
        $this->applyStringFilter($query, 'courses.level', $filters->level);
        $this->applyStringFilter($query, 'courses.language', $filters->language);
        $this->applyStringFilter($query, 'courses.average_rating', $filters->rating);

        $perPage = $layout === 'grid' ? 9 : 5;
        $courses = $query
            ->latest('courses.id')
            ->paginate($perPage);

        if ($filters->queryParameters !== []) {
            $courses->appends($filters->queryParameters);
        }

        return new CourseCatalogResult(
            courses: $courses,
            wishlistCourseIds: $this->resolveWishlistCourseIds($filters->userId),
            category: $categoryDetails,
            layout: $layout,
            parentCategorySlug: $parentCategorySlug,
            childCategorySlug: $childCategorySlug,
        );
    }

    private function applyCategoryFilter($query, ?string $categorySlug): ?Category
    {
        if ($categorySlug === null) {
            return null;
        }

        $category = $this->cache->remember(
            sprintf('catalog:category-by-slug:%s', $categorySlug),
            now()->addMinutes(10),
            fn () => Category::where('slug', $categorySlug)->first()
        );

        if (! $category) {
            return null;
        }

        if ($category->parent_id > 0) {
            $query->where('courses.category_id', $category->id);
        } else {
            $subCategoryIds = $this->cache->remember(
                sprintf('catalog:category-children:%d', $category->id),
                now()->addMinutes(10),
                fn () => Category::where('parent_id', $category->id)->pluck('id')->toArray()
            );

            $subCategoryIds[] = $category->id;
            $query->whereIn('courses.category_id', $subCategoryIds);
        }

        return $category;
    }

    private function resolveCategoryContext(?Category $category): array
    {
        if (! $category) {
            return [null, null];
        }

        if ($category->parent_id > 0) {
            $parent = $this->cache->remember(
                sprintf('catalog:category-parent:%d', $category->parent_id),
                now()->addMinutes(10),
                fn () => Category::find($category->parent_id)
            );

            return [$parent?->slug, $category->slug];
        }

        return [$category->slug, null];
    }

    private function applySearchFilters($query, CourseFiltersData $filters): void
    {
        if (! $filters->search) {
            return;
        }

        $searchTerm = $filters->search;

        $query->where(function ($inner) use ($searchTerm) {
            $inner->where('courses.title', 'LIKE', "%{$searchTerm}%")
                ->orWhere('courses.short_description', 'LIKE', "%{$searchTerm}%")
                ->orWhere('courses.level', 'LIKE', "%{$searchTerm}%")
                ->orWhere('courses.meta_keywords', 'LIKE', "%{$searchTerm}%")
                ->orWhere('courses.meta_description', 'LIKE', "%{$searchTerm}%")
                ->orWhere('courses.description', 'LIKE', "%{$searchTerm}%");
        });
    }

    private function applyPriceFilter($query, ?string $price): void
    {
        if (! $price) {
            return;
        }

        $map = [
            'paid' => ['column' => 'courses.is_paid', 'value' => 1],
            'discount' => ['column' => 'courses.discount_flag', 'value' => 1],
            'free' => ['column' => 'courses.is_paid', 'value' => 0],
        ];

        if (isset($map[$price])) {
            $query->where($map[$price]['column'], $map[$price]['value']);
        }
    }

    private function applyStringFilter($query, string $column, ?string $value): void
    {
        if ($value === null) {
            return;
        }

        $query->where($column, $value);
    }

    /**
     * @return array<int, int>
     */
    private function resolveWishlistCourseIds(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return $this->cache->remember(
            sprintf('catalog:wishlist:%d', $userId),
            now()->addMinutes(5),
            fn () => Wishlist::where('user_id', $userId)->pluck('course_id')->toArray()
        );
    }
}
