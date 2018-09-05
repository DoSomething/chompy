<?php

namespace Chompy\Jobs;

// use Carbon\Carbon;
// use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GetOrCreateUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The record to be created into a post from the csv.
     *
     * @var array
     */
    protected $record;

    /**
     * The referral code values to translate to Rogue post.
     *
     * @var array
     */
    protected $referralCodeValues;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($record, $referralCodeValues)
    {
    	$this->record = $record;

    	$this->referralCodeValues = $referralCodeValues;
    }

    /**
     * Execute the job to get or create a user.
     *
     * For a given record and referral code values, first check if we have a northstar ID, then grab the user using that.
     * Otherwise, see if we can find the user with the given email (if it exists), if not check if we can find them with the given phone number (if it exists).
     * If all else fails, create the user .
     *
     * @TODO - If we have a northstar id in the referral code, then we probably don't need to make a call to northstar for the full user object.
     *
     * @return array
     */
    public function handle()
    {
		dd('hi');
		$user = null;
        $userFieldsToLookFor = [
            'id' => isset($this->referralCodeValues['northstar_id']) && !empty($this->referralCodeValues['northstar_id']) ? $this->referralCodeValues['northstar_id'] : null,
            'email' => isset($this->record['email']) && !empty($this->record['email']) ? $this->record['email'] : null,
            'mobile' => isset($this->record['phone']) && !empty($this->record['phone']) ? $this->record['phone'] : null,
        ];

        foreach ($userFieldsToLookFor as $field => $value)
        {
            if ($value) {
                info('getting user with the '.$field.' field', [$field => $value]);
                $user = gateway('northstar')->asClient()->getUser($field, $value);
            }

            if ($user) {
                break;
            }
        }

        if (is_null($user)) {
            $user = gateway('northstar')->asClient()->createUser([
                'email' => $this->record['email'],
                'mobile' => $this->record['phone'],
                'first_name' => $this->record['first-name'],
                'last_name' => $this->record['last-name'],
                'addr_street1' => $this->record['registered-address-street'],
                'addr_street2' => $this->record['registered-address-street-2'],
                'addr_city' => $this->record['registered-address-city'],
                'addr_state' => $this->record['registered-address-state'],
                'addr_zip' => $this->record['registered-address-zip'],
                'source' => env('NORTHSTAR_CLIENT_ID'),
            ]);

            info('created user', ['user' => $user->id]);
            $this->stats['countUserAccountsCreated']++;
        }

        return $user;
    }
}