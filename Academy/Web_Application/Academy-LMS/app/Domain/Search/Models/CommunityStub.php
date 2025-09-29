<?php

namespace App\Domain\Search\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Placeholder model used to bind search synchronisation configuration
 * for communities until the dedicated domain models are introduced.
 */
class CommunityStub extends Model
{
    protected $table = 'communities';

    protected $guarded = [];

    public $timestamps = false;
}
