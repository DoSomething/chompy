<?php

namespace Chompy\Traits;

use League\Csv\Reader;
use Chompy\Events\LogProgress;
use Illuminate\Support\Facades\Storage;

trait ImportToRogue
{
	/**
	 * Initiate the stat counters to use with imports to Rogue.
	 *
	 * @return array
	 */
	public function statsInit()
	{
	    return [
	        'totalRecords' => 0,
	        'countScrubbed' => 0,
	        'countProcessed' => 0,
	        'countPostCreated' => 0,
	        'countUserAccountsCreated' => 0,
	    ];
	}

    public function getCSVRecords($filepath)
    {
        $file = Storage::get($filepath);
        $file = str_replace("\r","\n", $file);

        $csv = Reader::createFromString($file);
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();
        $this->totalRecords = count($csv);

        event(new LogProgress('Total rows to chomp: ' . $this->totalRecords, 'general'));
        event(new LogProgress('', 'progress', 0));

        $this->stats['totalRecords'] = $this->totalRecords;

        return $records;
    }

    /**
     * Translate a status from TurboVote into a status that can be sent to Rogue.
     *
     * @param  string $tvStatus
     * @param  string $tvMethod
     * @return string
     */
    public function translateTVStatus($tvStatus, $tvMethod)
    {
        $translatedStatus = '';

        switch($tvStatus)
        {
            case 'initiated':
                $translatedStatus = 'register-form';
                break;
            case 'registered':
                $translatedStatus = $tvMethod === 'online' ? 'register-OVR' : 'confirmed';
                break;
            case 'unknown':
            case 'pending':
                $translatedStatus = 'uncertain';
                break;
            case 'ineligible':
            case 'not-required':
                $translatedStatus = 'ineligible';
                break;
            default:
                $translatedStatus = 'pending';
        }

        return $translatedStatus;
    }

}