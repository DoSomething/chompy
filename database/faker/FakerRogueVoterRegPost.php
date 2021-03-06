<?php

use Faker\Provider\Base;

class FakerRogueVoterRegPost extends Base
{
    /**
     * Return a mock voter-reg post from Rogue.
     *
     * @param array $data
     * @return array
     */
    public function rogueVoterRegPost($data = [], $startedRegistration = null)
    {
        $result = array_merge([
            'id' => $this->generator->randomDigitNotNull,
            'northstar_id' => $this->generator->northstar_id,
            'status' => 'step-1',
            'type' => config('import.rock_the_vote.post.type'),
        ], $data);

        $result['details'] = json_encode((object) [
            'Started registration' => $startedRegistration ? $startedRegistration : $this->generator->daysAgoInRockTheVoteFormat(),
        ]);

        return $result;
    }
}
