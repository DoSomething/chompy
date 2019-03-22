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
        $file = str_replace("\r", "\n", $file);

        $csv = Reader::createFromString($file);
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();
        $this->totalRecords = count($csv);

        event(new LogProgress('Total rows to chomp: ' . $this->totalRecords, 'general'));
        event(new LogProgress('', 'progress', 0));

        $this->stats['totalRecords'] = $this->totalRecords;

        return $records;
    }
}
