<?php

namespace Chompy\Models;

use Illuminate\Database\Eloquent\Model;

class RockTheVoteLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'finish_with_state',
        'import_file_id',
        'pre_registered',
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

    /**
     * Log sanitized Rock The Vote data for given user and import file.
     */
    public static function createFromRecord($record, $user, $importFileId)
    {
        return static::firstOrCreate([
            'import_file_id' => $importFileId,
            'finish_with_state' => $record->rtv_finish_with_state,
            'pre_registered' => $record->rtv_pre_registered,
            'started_registration' => $record->rtv_started_registration,
            'status' => $record->rtv_status,
            'tracking_source' => $record->rtv_tracking_source,
            'user_id' => $user->id,
        ]);
    }
}
