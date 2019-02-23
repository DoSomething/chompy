<?php

namespace Chompy\Jobs;

use Exception;
use Carbon\Carbon;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\Traits\ImportToRogue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RockTheVoteRecord {
    public function __construct($record)
    {
        $this->post_type = 'voter-reg';
        $this->source = 'rock-the-vote';

        $this->email = $record['Email address'];
        $this->mobile = $record['Phone'];
        $this->email_opt_in = $record['Opt-in to Partner email?'];
        // Note: Not a typo, this column name does not have the trailing question mark.
        $this->sms_opt_in = $record['Opt-in to Partner SMS/robocall'];

        $this->voter_registration_status = $this->parseVoterRegistrationStatus($record['Status'], $record['Finish with State']);

        $referral = $this->parseReferralCode($record['Tracking Source']);
        $this->user_id = $referral['northstar_id'];
        $this->campaign_id = (int) $referral['campaign_id'];
        $this->source_details = $referral['source_details'];

        // TODO: Add all other properties used for getting/validating/creating users/posts.
    }

    /**
     * Parse key values from referral code string.
     *
     * @param  string $referralCode
     * @return array
     */
    private function parseReferralCode($referralCode)
    {
        $values = [];
        info('Parsing referral code: ' . $referralCode);

        // Remove some nonsense that comes in front of the referral code sometimes
        if (strrpos($referralCode, 'iframe?r=') !== false) {
            $referralCode = str_replace('iframe?r=', null, $referralCode);
        }
        if (strrpos($referralCode, 'iframe?') !== false) {
            $referralCode = str_replace('iframe?', null, $referralCode);
        }

        if (! empty($referralCode)) {
            $referralCode = explode(',', $referralCode);

            $referral = false;

            foreach ($referralCode as $value) {

                // See if we are dealing with ":" or "="
                if (strpos($value, ':')) {
                    $value = explode(':', $value);
                }
                elseif (strpos($value, '=')) {
                    $value = explode('=', $value);
                }

                // Add northstar_id, campaign id and run, source, and source details into $values
                switch (strtolower($value[0])) {
                    case 'user':
                        $values['northstar_id'] = $value[1];
                        break;

                    case 'campaignid':
                        $values['campaign_id'] = $value[1];
                        break;

                    case 'campaign':
                        $values['campaign_id'] = $value[1];
                        break;

                    case 'campaignrunid':
                        $values['campaign_run_id'] = $value[1];
                        break;

                    case 'source':
                        $values['source'] = $value[1];
                        break;

                    case 'source_details':
                        $values['source_details'] = $value[1];
                        break;

                    default:
                        break;
                }

                // Is this a referral?
                if (strtolower($value[0]) === 'referral' && strtolower($value[1]) === 'true') {
                    $referral = true;
                }
            }
        }

        // See if we have all the required information we need
        if (!array_has($values, ['northstar_id', 'campaign_id', 'campaign_run_id'])) {
            // If we have valid campaign values, use em! This also means that we do not have NS id
            if (array_has($values, ['campaign_id', 'campaign_run_id']) && is_numeric($values['campaign_id']) && is_numeric($values['campaign_run_id'])) {
                $finalValues = [
                    'northstar_id' => null, // set the user to null so we force account creation when the code is not present.
                    'campaign_id' => $values['campaign_id'],
                    'campaign_run_id' => $values['campaign_run_id'],
                    'source' => $this->source,
                    'source_details' => null,
                ];
            }

            // If we have NS id, use it! This also means that we do not have both campaign_id and campaign_run_id, so use the defaults
            if (array_has($values, ['northstar_id'])) {
                $finalValues = [
                    'northstar_id' => $values['northstar_id'], // set the user to null so we force account creation when the code is not present.
                    'campaign_id' => 8017,
                    'campaign_run_id' => 8022,
                    'source' => $this->source,
                    'source_details' => null,
                ];
            }
        }

        // If we were missing all the necessary values or if this is a referral, use all the defaults
        if (empty($finalValues) || $referral) {
            $finalValues = [
                'northstar_id' => null, // set the user to null so we force account creation when the code is not present.
                'campaign_id' => 8017,
                'campaign_run_id' => 8022,
                'source' => $this->source,
                'source_details' => null,
            ];
        }

        return $finalValues;
    }
    /**
     * Translate a status from Rock The Vote into a status that can be sent to Rogue.
     *
     * @param  string $rtvStatus
     * @param  string $rtvFinishWithState
     * @return string
     */
    private function parseVoterRegistrationStatus($rtvStatus, $rtvFinishWithState)
    {
        $rtvStatus = strtolower($rtvStatus);
        $rtvFinishWithState = strtolower($rtvFinishWithState);

        if($rtvStatus === 'complete') {
            if ($rtvFinishWithState === "no") {
                return 'register-form';
            }
            if ($rtvFinishWithState === "yes") {
                return 'register-OVR';
            }
        }

        if (strpos($rtvStatus, 'step') !== false) {
            return 'uncertain';
        }

        if ($rtvStatus === 'rejected' || $rtvStatus === 'under 18') {
            return 'ineligible';
        }

        return '';
    }

}

class CreateRockTheVotePostInRogue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ImportToRogue;

    /**
     * The record to be created into a post from the csv.
     *
     * @var array
     */
    protected $data;
    protected $record;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($record)
    {
        $this->data = new RockTheVoteRecord($record);
        // TODO: Remove once all $this->record references have been replaced with $this->data.
        $this->record = $record;
    }

    /**
     * Execute the job to create a Turbo Vote post in Rogue.
     *
     * @return array
     */
    public function handle(Rogue $rogue)
    {
        if (is_test_email($this->data->email)) {
            info('progress_log: Skipping test: ' . $this->data->email);
            return;
        }

        info('progress_log: Processing: ' . $this->data->email);

        $user = $this->getUser($this->data);
        if (!($user && $user->id)) {
            $user = $this->createUser();
        }

        // @TODO: Refactor this to only update for existing users, add to create payload.
        $this->updateNorthstarStatus($user, $this->data->voter_registration_status);

        $existingPosts = $rogue->getPost([
            'campaign_id' => $this->data->campaign_id,
            'northstar_id' => $user->id,
            'type' => $this->data->post_type,
        ]);

        if (!$existingPosts['data']) {
            info('post not found for user ' . $user->id);
            $rtvCreatedAtMonth = strtolower(Carbon::parse($this->record['Started registration'])->format('F-Y'));
            $postDetails = $this->extractDetails($this->record);

            $postData = [
                'campaign_id' => $this->data->campaign_id,
                'northstar_id' => $user->id,
                'type' => $this->data->post_type,
                'action' => $rtvCreatedAtMonth . '-rockthevote',
                'status' => $this->data->voter_registration_status,
                'source' => $this->data->source,
                'source_details' => $this->data->source_details,
                'details' => $postDetails,
            ];

            $post = $rogue->createPost($postData);
            info('post created in rogue for ' . $this->data->email);
            return;
        }

        $postId = $existingPosts['data'][0]['id'];
        info('Found post ' . $postId . ' for user ' . $user->id);

        $newStatus = $this->getVoterRegistrationStatusChange($existingPosts['data'][0]['status'], $this->data->voter_registration_status);
        if ($newStatus) {
            $rogue->updatePost($postId, ['status' => $newStatus]);
        }
    }

    /**
     * Check for user first by id, next by email, last by mobile..
     * TODO: Move this to DRY with TurboVote imports (if we keep it).
     * @see https://www.pivotaltracker.com/n/projects/2019429/stories/164114650
     *
     * @param string $data
     * @return NorthstarUser
     */
    private function getUser($data)
    {
        if ($data->user_id) {
            $user = gateway('northstar')->asClient()->getUser('id', $data->user_id);
            if ($user && $user->id) {
                return $user;
            }
        }
        if ($data->email) {
            $user = gateway('northstar')->asClient()->getUser('email', $data->email);
            if ($user && $user->id) {
                return $user;
            }
        }
        if (!$data->mobile) {
            return null;
        }
        return gateway('northstar')->asClient()->getUser('mobile', $data->mobile);
    }

    /**
     * Creates new user from job record.
     *
     * @return NorthstarUser
     */
    private function createUser()
    {
        $record = $this->record;
        $userData = [
            'email' => $this->data->email,
            'mobile' => $this->data->mobile,
            'first_name' => $record['First name'],
            'last_name' => $record['Last name'],
            'addr_street1' => $record['Home address'],
            'addr_street2' => $record['Home unit'],
            'addr_city' => $record['Home city'],
            'addr_state' => $record['Home state'],
            'addr_zip' => $record['Home zip code'],
            'source' => config('services.northstar.client_credentials.client_id'),
        ];
        if (!empty($this->data->email_opt_in)) {
            $userData['email_subscription_status'] = str_to_boolean($this->data->email_opt_in);
        }
        if (!empty($this->data->sms_opt_in) && !empty($this->data->mobile)) {
            $userData['sms_status'] = str_to_boolean($this->data->sms_opt_in) ? 'active' : 'stop';
        }

        $user = gateway('northstar')->asClient()->createUser($userData);

        if (!$user->id) {
            throw new Exception(500, 'Unable to create user: ' . $this->data->email);
        }
        info('created user', ['user' => $user->id]);

        return $user;
    }

    /**
     * Parse the record for extra details and return them as a JSON object.
     *
     * @param  array $record
     * @return string
     */
    private function extractDetails($record)
    {
        $details = [];

        $importantKeys = [
            'Tracking Source',
            'Started registration',
            'Finish with State',
            'Status',
            'Email address',
            'Home zip code'
        ];

        foreach ($importantKeys as $key) {
            $details[$key] = $record[$key];
        }

        return json_encode($details);
    }


    /*
     * Determines if a status should be changed and what it should be changed to.
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @return string|null
     */
    private function getVoterRegistrationStatusChange($currentStatus, $newStatus)
    {
        $statusHierarchy = [
            'uncertain',
            'ineligible',
            'confirmed',
            'register-OVR',
            'register-form',
        ];

        $indexOfCurrentStatus = array_search($currentStatus, $statusHierarchy);
        $indexOfNewStatus = array_search($newStatus, $statusHierarchy);

        return $indexOfCurrentStatus < $indexOfNewStatus ? $newStatus : null;
    }

    /*
     * Translate to Northstar status and update Northstar user (Northstar takes care of the hierarchy)
     *
     * @param Object $user
     * @param string $statusToSend
    */
    private function updateNorthstarStatus($user, $statusToSend)
    {
        if ($statusToSend === 'register-form' || $statusToSend === 'register-OVR') {
            $statusToSend = 'registration_complete';
        }
        gateway('northstar')->asClient()->updateUser($user->id, ['voter_registration_status' => $statusToSend]);
        info('updated user', ['user' => $user->id]);
    }
}
