<?php

namespace Chompy\Models;

use Chompy\Services\RockTheVote;
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
        'row_count',
        'current_index',
        'dispatched_at',
        'user_id',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'since',
        'before',
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
     * Creates a Rock The Vote Report via API request and saves to storage.
     *
     * @param string $since
     * @param string $before
     * @return RockTheVoteReport
     */
    public static function createViaApi($since = null, $before = null)
    {
        $response = app(RockTheVote::class)->createReport([
            'since' => $since,
            'before' => $before,
        ]);

        // Parse response to find the new Rock The Vote Report ID.
        $statusUrlParts = explode('/', $response->status_url);
        $reportId = $statusUrlParts[count($statusUrlParts) - 1];

        // Log our created report in the database, to keep track of reports requested.
        return static::create([
            'id' => $reportId,
            'since' => $since,
            'before' => $before,
            'status' => $response->status,
            'user_id' => optional(\Auth::user())->northstar_id,
        ]);
    }

    /**
     * @return int
     */
    public function getPercentageAttribute()
    {
        return round(($this->current_index * 100) / $this->row_count);
    }
}
