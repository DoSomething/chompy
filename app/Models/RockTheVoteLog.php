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
    public static function createFromRecord(RockTheVoteRecord $record, NorthstarUser $user, ImportFile $importFile)
    {
        $info = $record->getPostDetails();

        $rockTheVoteLog = self::create([
            'import_file_id' => $importFile->id,
            'finish_with_state' => $info['Finish with State'],
            'pre_registered' => $info['Pre-Registered'],
            'started_registration' => $info['Started registration'],
            'status' => $info['Status'],
            'tracking_source' => $info['Tracking Source'],
            'user_id' => $user->id,
        ]);

        $importFile->incrementImportCount();

        return $rockTheVoteLog;
    }

    /**
     * Find a log for given record and user.
     */
    public static function getByRecord(RockTheVoteRecord $record, NorthstarUser $user)
    {
        $info = $record->getPostDetails();

        return self::where([
            'started_registration' => $info['Started registration'],
            'status' => $info['Status'],
            'user_id' => $user->id,
        ])->first();
    }
}
