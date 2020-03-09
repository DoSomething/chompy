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
        'dispatched_at',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'dispatched_at',
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
        'dispatched_at',
    ];

    /**
     * Logs a Rock The Vote Report that we've created via API request.
     *
     * @param array $response
     * @param datetime $since
     * @param datetime $before
     * @return RockTheVoteReport
     */
    public static function createFromApiResponse($response, $since = null, $before = null)
    {
        // Parse response to find the new Rock The Vote Report ID.
        $statusUrlParts = explode('/', $response->status_url);
        $reportId = $statusUrlParts[count($statusUrlParts) - 1];

        // Log our created report in the database, to keep track of reports requested.
        return static::create([
            'id' => $reportId,
            'since' => $since,
            'before' => $before,
            'status' => $response->status,
        ]);
    }
}
