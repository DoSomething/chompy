<?php

use Carbon\Carbon;
use Faker\Provider\Base;

class FakerRogueVoterRegPost extends Base
{
    /**
     * Return a mock voter-reg post from Rogue.
     *
     * @param array $data
     * @return array
     */
    public function rogueVoterRegPost($userId, $startedRegistration, $status)
    {
        return [
            'id' => $this->generator->randomDigitNotNull,
            'northstar_id' => $userId ? $userId : $this->generator->northstar_id,
            'status' => $status,
            'type' => 'voter-reg',
            'details' => json_encode((object)[
                'Started registration' => $startedRegistration,
            ]),
        ];
    }
}
