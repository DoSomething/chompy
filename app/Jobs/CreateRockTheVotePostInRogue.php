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
        $this->email = $record['Email address'];
        $this->mobile = $record['Phone'];
        $this->email_opt_in = $record['Opt-in to Partner email?'];
        // Note: Not a typo, this column name does not have the trailing question mark.
        $this->sms_opt_in = $record['Opt-in to Partner SMS/robocall'];
        // TODO: Add all other properties used for getting/validating/creating users/posts.
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
    	if ($this->isTestData($this->record)) {
            info('progress_log: Skipping test: ' . $this->data->email);
            return;
        }

        info('progress_log: Processing: ' . $this->data->email);

        $referralCodeValues = $this->parseReferralCode($this->record['Tracking Source']);
        info('Referral code: ' . implode(', ', $referralCodeValues));

        $user = $this->getUser($referralCodeValues['northstar_id']);
        if (!($user && $user->id)) {
            $user = $this->createUser();
        }

        // @TODO: If we write more functions that are identical across all voter reg imports, pull out into a ImportsVoterReg trait
        $this->updateNorthstarStatus($user, $this->translateStatus($this->record['Status'], $this->record['Finish with State']));

        $campaignId = (int) $referralCodeValues['campaign_id'];
        $postType = 'voter-reg';

        $existingPosts = $rogue->getPost([
            'campaign_id' => $campaignId,
            'northstar_id' => $user->id,
            'type' => $postType,
        ]);

        if (!$existingPosts['data']) {
            info('post not found for user ' . $user->id);
            $rtvCreatedAtMonth = strtolower(Carbon::parse($this->record['Started registration'])->format('F-Y'));
            $sourceDetails = isset($referralCodeValues['source_details']) ? $referralCodeValues['source_details'] : null;
            $postDetails = $this->extractDetails($this->record);

            $postData = [
                'campaign_id' => $campaignId,
                'northstar_id' => $user->id,
                'type' => $postType,
                'action' => $rtvCreatedAtMonth . '-rockthevote',
                'status' => $this->translateStatus($this->record['Status'], $this->record['Finish with State']),
                'source' => 'rock-the-vote',
                'source_details' => $sourceDetails,
                'details' => $postDetails,
            ];

            $post = $rogue->createPost($postData);
            info('post created in rogue for ' . $this->data->email);
            return;
        }

        $postId = $existingPosts['data'][0]['id'];
        info($postType.' post '.$postId.' found for user ' . $user->id.' and campaign '.$campaignId);
        $newStatus = $this->translateStatus($this->record['Status'], $this->record['Finish with State']);
        $statusShouldChange = $this->updateStatus($existingPosts['data'][0]['status'], $newStatus);

        if ($statusShouldChange) {
            $rogue->updatePost($postId, ['status' => $statusShouldChange]);
        }
    }

    /*
     * Returns whether a record is test data that should not create/update users and/or posts.
     * TODO: Move this into helpers and DRY with any other Jobs that are still relevant.
     *
     * @param array $record
     * @return bool
     */
    private function isTestData($record)
    {
        $isNotValidEmail = strrpos($record['Email address'], 'thing.org') !== false || strrpos($record['Email address'] !== false, '@dosome') || strrpos($record['Email address'], 'rockthevote.com') !== false || strrpos($record['Email address'], 'test') !== false || strrpos($record['Email address'], '+') !== false;
        $isNotValidLastName = strrpos($record['Last name'], 'Baloney') !== false;

        return $isNotValidEmail || $isNotValidLastName ? true : false;
    }

    /**
     * Parse the referral code field to grab individual values.
     *
     * @param  array $refferalCode
     */
    private function parseReferralCode($referralCode)
    {
        $values = [];

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
                    'source' => 'rock-the-vote',
                    'source_details' => null,
                ];
            }

            // If we have NS id, use it! This also means that we do not have both campaign_id and campaign_run_id, so use the defaults
            if (array_has($values, ['northstar_id'])) {
                $finalValues = [
                    'northstar_id' => $values['northstar_id'], // set the user to null so we force account creation when the code is not present.
                    'campaign_id' => 8017,
                    'campaign_run_id' => 8022,
                    'source' => 'rock-the-vote',
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
                'source' => 'rock-the-vote',
                'source_details' => null,
            ];
        }

        return $finalValues;
    }

    /**
     * If userId param is given, check for valid user.
     * Otherwise check by job record email, mobile.
     * TODO: Move this to DRY with TurboVote imports (if we keep it).
     * @see https://www.pivotaltracker.com/n/projects/2019429/stories/164114650
     *
     * @param string
     * @return NorthstarUser
     */
    private function getUser($userId)
    {
        if ($userId) {
            $user = gateway('northstar')->asClient()->getUser('id', $userId);
            if ($user && $user->id) {
                return $user;
            }
        }
        if ($this->data->email) {
            $user = gateway('northstar')->asClient()->getUser('email', $this->data->email);
            if ($user && $user->id) {
                return $user;
            }
        }
        if (!$this->data->mobile) {
            return null;
        }
        return gateway('northstar')->asClient()->getUser('mobile', $this->data->mobile);
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
     * @param  array $extraData
     */
    private function extractDetails($record, $extraData = null)
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

        if ($extraData) {
            $details = array_merge($details, $extraData);
        }

        return json_encode($details);
    }

    /**
     * Translate a status from Rock The Vote into a status that can be sent to Rogue.
     *
     * @param  string $rtvStatus
     * @param  string $rtvFinishWithState
     * @return string
     */
    private function translateStatus($rtvStatus, $rtvFinishWithState)
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

    /*
     * Determines if a status should be changed and what it should be changed to.
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @return string|null
     */
    private function updateStatus($currentStatus, $newStatus)
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
    }
}