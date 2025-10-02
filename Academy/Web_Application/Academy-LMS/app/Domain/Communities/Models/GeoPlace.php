<?php

namespace App\Domain\Communities\Models;

use App\Casts\EncryptedAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoPlace extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'bounding_box' => 'array',
        'metadata' => EncryptedAttribute::class,
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function communities(): HasMany
    {
        return $this->hasMany(Community::class);
    }
}
