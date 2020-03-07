<?php

namespace Chompy\Models;

use Illuminate\Database\Eloquent\Model;

class RockTheVoteReport extends Model
{
    /**
     * We use the externally created Rock the Vote ID as our primary key.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'status',
        'since',
        'before',
        'imported_at',
    ];

    /**
     * Attributes that can be queried when filtering.
     *
     * This array is manually maintained. It does not necessarily mean that
     * any of these are actual indexes on the database... but they should be!
     *
     * @var array
     */
    public static $indexes = [
        'status',
    ];
}
