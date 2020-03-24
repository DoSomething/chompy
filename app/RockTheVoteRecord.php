<?php

namespace Chompy;

use Illuminate\Support\Str;

class RockTheVoteRecord
{
    /**
     * Parses values to send to DS API from given CSV record, using given config.
     *
     * @param array $record
     * @param array $config
     */
    public function __construct($record, $config)
    {
        // User PII.
        $this->addr_street1 = $record['Home address'];
        $this->addr_street2 = $record['Home unit'];
        $this->addr_city = $record['Home city'];
        $this->addr_state = $record['Home state'];
        $this->addr_zip = $record['Home zip code'];
        $this->email = $record['Email address'];
        $this->first_name = $record['First name'];
        $this->last_name = $record['Last name'];
        $this->mobile = isset($record['Phone']) && is_valid_mobile($record['Phone']) ? $record['Phone'] : null;

        // Voter registration details.
        $this->rtv_finish_with_state = $record['Finish with State'];
        $this->rtv_pre_registered = $record['Pre-Registered'];
        $this->rtv_started_registration = $record['Started registration'];
        $this->rtv_status = $record['Status'];
        $this->rtv_tracking_source = $record['Tracking Source'];

        $emailOptIn = $record['Opt-in to Partner email?'];
        if ($emailOptIn) {
            $this->email_subscription_status = str_to_boolean($emailOptIn);
            if ($this->email_subscription_status) {
                $this->email_subscription_topics = explode(',', $config['user']['email_subscription_topics']);
            }
        }

        $this->user_source_detail = $config['user']['source_detail'];

        // Note: Not a typo, this column name does not have the trailing question mark.
        $smsOptIn = $record['Opt-in to Partner SMS/robocall'];
        if ($smsOptIn && $this->mobile) {
            $this->sms_status = str_to_boolean($smsOptIn) ? 'active' : 'stop';
        }
        $rtvStatus = $this->parseVoterRegistrationStatus($record['Status'], $record['Finish with State']);

        $this->voter_registration_status = Str::contains($rtvStatus, 'register') ? 'registration_complete' : $rtvStatus;

        $this->user_id = $this->parseUserId($record['Tracking Source']);

        $postConfig = $config['post'];
        $this->post_source = $postConfig['source'];
        $this->post_source_details = null;
        $this->post_details = $this->parsePostDetails($record);
        $this->post_status = $rtvStatus;
        $this->post_type = $postConfig['type'];
        $this->post_action_id = $postConfig['action_id'];
    }

    /**
     * Parse existing user ID from referral code string.
     *
     * @param  string $referralCode
     * @return string
     */
    private function parseUserId($referralCode)
    {
        info('Parsing referral code: ' . $referralCode);

        // Remove some nonsense that comes in front of the referral code sometimes
        if (str_contains($referralCode, 'iframe?r=')) {
            $referralCode = str_replace('iframe?r=', null, $referralCode);
        }
        if (str_contains($referralCode, 'iframe?')) {
            $referralCode = str_replace('iframe?', null, $referralCode);
        }

        if (empty($referralCode)) {
            return null;
        }

        $referralCode = explode(',', $referralCode);

        foreach ($referralCode as $value) {
            // See if we are dealing with ":" or "="
            if (str_contains($value, ':')) {
                $value = explode(':', $value);
            } elseif (str_contains($value, '=')) {
                $value = explode('=', $value);
            }

            $key = strtolower($value[0]);
            // We expect 'user', but check for any variations/typos in any manually entered URLs.
            if ($key === 'user' || $key === 'user_id' || $key === 'userid') {
                $userId = $value[1];
            }

            /**
             * If referral parameter is set to true, the user parameter belongs to the referring
             * user, not the user that should be associated with this voter registration record.
             */
            // We expect 'referral', but check for any typos in any manually entered URLs.
            if (($key === 'referral' || $key === 'refferal') && str_to_boolean($value[1])) {
                /**
                 * Return null to force querying for existing user via this record email or mobile
                 * upon import.
                 */
                return null;
            }
        }

        return isset($userId) ? $userId : null;
    }

    /**
     * Translate a status from Rock The Vote into a Rogue post status.
     *
     * @param  string $rtvStatus
     * @param  string $rtvFinishWithState
     * @return string
     */
    private function parseVoterRegistrationStatus($rtvStatus, $rtvFinishWithState)
    {
        $rtvStatus = strtolower($rtvStatus);

        if ($rtvStatus === 'complete') {
            return str_to_boolean($rtvFinishWithState) ? 'register-OVR' : 'register-form';
        }

        if (str_contains($rtvStatus, 'step')) {
            return 'uncertain';
        }

        if ($rtvStatus === 'rejected' || $rtvStatus === 'under 18') {
            return 'ineligible';
        }

        return '';
    }

    /**
     * Parse the record for extra details and return them as a JSON object.
     *
     * @param  array $record
     * @return string
     */
    private function parsePostDetails($record)
    {
        $details = [];

        $importantKeys = [
            'Tracking Source',
            'Started registration',
            'Finish with State',
            'Status',
            'Home zip code',
        ];

        foreach ($importantKeys as $key) {
            $details[$key] = $record[$key];
        }

        return json_encode($details);
    }
}
