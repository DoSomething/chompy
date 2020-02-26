<?php

namespace Chompy\Models;

use Illuminate\Database\Eloquent\Model;

class RockTheVoteRecord extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'finish_with_state',
        'import_file_id',
        'started_registration',
        'status',
        'tracking_source',
        'user_id',
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
        'import_file_id',
        'user_id',
    ];
}
