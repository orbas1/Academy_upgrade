<?php

namespace App\Domain\Courses\DTO;

use App\Domain\Shared\DataTransferObject;
use Illuminate\Http\Request;

class CourseFiltersData extends DataTransferObject
{
    public function __construct(
        public readonly ?string $categorySlug,
        public readonly ?string $search,
        public readonly ?string $price,
        public readonly ?string $level,
        public readonly ?string $language,
        public readonly ?string $rating,
        public readonly ?int $userId,
        /** @var array<string, mixed> */
        public readonly array $queryParameters = [],
    ) {
    }

    public static function fromRequest(Request $request, ?string $categorySlug): self
    {
        $query = $request->query();

        return new self(
            $categorySlug !== '' ? $categorySlug : null,
            self::stringOrNull($request->query('search')),
            self::stringOrNull($request->query('price')),
            self::stringOrNull($request->query('level')),
            self::stringOrNull($request->query('language')),
            self::stringOrNull($request->query('rating')),
            optional($request->user())->id,
            is_array($query) ? $query : [],
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
