<?php

use Carbon\Carbon;
use Faker\Provider\Base;

class FakerRockTheVoteReportRow extends Base
{
    /**
     * Return a mock row from a Rock the Vote report.
     *
     * @param array $data
     * @return array
     */
    public function rockTheVoteReportRow($data = [])
    {
        return array_merge([
            'Home address' => $this->generator->streetAddress,
            'Home unit' => $this->generator->randomDigit,
            'Home city' => $this->generator->city,
            'Home state' => $this->generator->state,
            'Home zip code' => $this->generator->postcode,
            'Email address' => $this->generator->email,
            'First name' => $this->generator->firstName,
            'Last name' => $this->generator->lastName,
            'Phone' => null,
            'Finish with State' => 'Yes',
            'Pre-Registered' => 'No',
            'Started registration' => $this->daysAgoInRockTheVoteFormat(),
            'Status' => 'Step 2',
            'Tracking Source' => 'ads',
            'Opt-in to Partner email?' => 'Yes',
            'Opt-in to Partner SMS/robocall' => 'No',
        ], $data);
    }

    /**
     * Return a mock Started registration field value.
     *
     * @return string
     */
    public function daysAgoInRockTheVoteFormat($numDaysToSubtract = 0)
    {
        return Carbon::now()->subDays($numDaysToSubtract)->format('Y-m-d H:i:s O');
    }
}
