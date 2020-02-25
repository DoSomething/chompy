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
}
