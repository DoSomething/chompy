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
        $emailOptIn = str_to_boolean($record['Opt-in to Partner email?']);
        // Note: Not a typo, this column name does not have the trailing question mark.
        $smsOptIn = str_to_boolean($record['Opt-in to Partner SMS/robocall']);
        $rtvStatus = $this->parseVoterRegistrationStatus($record['Status'], $record['Finish with State']);

        $this->userData = [
            'addr_street1' => $record['Home address'],
            'addr_street2' => $record['Home unit'],
            'addr_city' => $record['Home city'],
            'addr_zip' => $record['Home zip code'],
            'email' => $record['Email address'],
            'first_name' => $record['First name'],
            'last_name' => $record['Last name'],
            'mobile' => isset($record['Phone']) && is_valid_mobile($record['Phone']) ? $record['Phone'] : null,
            'email_subscription_status' => $emailOptIn,
            'email_subscription_topics' => $emailOptIn ? explode(',', $config['user']['email_subscription_topics']) : [],
            'voter_registration_status' => Str::contains($rtvStatus, 'register') ? 'registration_complete' : $rtvStatus,
            // Source is required in order to set the source detail.
            'source' => config('services.northstar.client_credentials.client_id'),
            'source_detail' => $config['user']['source_detail'],
        ];

        if ($smsOptIn && $this->userData['mobile']) {
            $this->userData['sms_status'] = $smsOptin ? 'active' : 'stop';
        }

        $this->postData = [
            'source' => $config['post']['source'],
            'source_details' => null,
            'details' => $this->parsePostDetails($record),
            'status' => $rtvStatus,
            'type' => $config['post']['type'],
            'action_id' => $config['post']['action_id'],
        ];

        $this->setUserId($record['Tracking Source']);
    }

    /**
     * Parse existing user ID from referral code string, and add to userData.
     *
     * @param string $referralCode
     */
    public function setUserId($referralCode)
    {
        $this->userData['id'] = null;

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

        if (isset($userId)) {
            $this->userData['id'] = $userId;
        }
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
            'Pre-Registered',
            'Home zip code',
        ];

        foreach ($importantKeys as $key) {
            $details[$key] = $record[$key];
        }

        return json_encode($details);
    }
}
