<?php

namespace Chompy\Models;

use Chompy\RockTheVoteRecord;
use Illuminate\Database\Eloquent\Model;
use DoSomething\Gateway\Resources\NorthstarUser;

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
    public static function createFromRecord(RockTheVoteRecord $record, NorthstarUser $user, $importFileId)
    {
        $info = get_object_vars(json_decode($record->postData['details']));

        return static::firstOrCreate([
            'import_file_id' => $importFileId,
            'finish_with_state' => $info['Tracking Source'],
            'pre_registered' => $info['Pre-Registered'],
            'started_registration' => $info['Started registration'],
            'status' => $info['Status'],
            'tracking_source' => $info['Tracking Source'],
            'user_id' => $user->id,
        ]);
    }
}
