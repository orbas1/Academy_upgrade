<?php

namespace App\Support\Caching\Warmers;

interface CacheWarmer
{
    public function warm(): void;
}
