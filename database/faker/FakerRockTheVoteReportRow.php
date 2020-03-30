<?php

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
            'Phone' => $this->generator->phoneNumber,
            'Finish with State' => 'Yes',
            'Pre-Registered' => 'No',
            'Started registration' => '2020-02-22 19:16:32 -0500',
            'Status' => 'Step 1',
            'Tracking Source' => 'ads',
            'Opt-in to Partner email?' => 'Yes',
            'Opt-in to Partner SMS/robocall' => 'Yes',
        ], $data);
    }
}