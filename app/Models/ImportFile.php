<?php

namespace Chompy\Models;

use Illuminate\Database\Eloquent\Model;

class ImportFile extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'filepath',
        'import_type',
        'row_count',
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
        'import_type',
    ];
}
