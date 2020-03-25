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
            'Finish with State' => null,
            'Pre-Registered' => null,
            'Started registration' => null,
            'Status' => null,
            'Tracking Source' => null,
            'Opt-in to Partner email?' => 'Yes',
            'Opt-in to Partner SMS/robocall' => 'Yes',
        ], $data);
    }
}
