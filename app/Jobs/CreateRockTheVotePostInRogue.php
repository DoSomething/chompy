<?php

namespace Chompy\Jobs;

use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\Traits\ImportToRogue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateRockTheVotePostInRogue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ImportToRogue;

    /**
     * The record to be created into a post from the csv.
     *
     * @var array
     */
    protected $record;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($record)
    {
    	$this->record = $record;
    }

    /**
     * Execute the job to create a Turbo Vote post in Rogue.
     *
     * @return array
     */
    public function handle(Rogue $rogue)
    {

    }

}