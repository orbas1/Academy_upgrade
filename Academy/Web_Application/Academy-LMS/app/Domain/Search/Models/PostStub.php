<?php

namespace App\Domain\Search\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Placeholder model used for search configuration while the
 * community posts domain is under construction.
 */
class PostStub extends Model
{
    protected $table = 'community_posts';

    protected $guarded = [];

    public $timestamps = false;
}
