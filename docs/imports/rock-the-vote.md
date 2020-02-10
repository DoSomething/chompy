# Rock The Vote

We import CSVs from Rock The Vote (RTV) to upsert users and their `voter-reg` posts. We use the `Tracking Code` column provided to determine whether a voter registration (VR) was from a member's online VR drive page.

See [VR Technical Inventory](https://docs.google.com/document/d/1xs2C3DNdD5h1j_abBrGVBNrsrxKvwn2VHDWweIEhvqc/edit?usp=sharing) for more details.

## Voter Registration Status

Ultimately, there are 4 `post` statuses we want to capture for `voter-reg` posts for Rock the Vote (**Note** RTV doesn't have a `confirmed` status like TurboVote did):

- `register-form` - User completed the registration form
- `register-OVR` - User completed the registration form on their state's Online Voter Registration platform
- `ineligible` - User is ineligible to register for whatever reason
- `uncertain` - We can not be certain about this user registration status

This is the logic for how to determine what a `post` status should be.

- If `status` is `complete` and `finish with state` is `no` --> `register-form`
- If `status` is `complete` and `finish with state` is `yes` --> `register-OVR`
- If `status` is any of the `step`s --> `uncertain`
- If `status` is `rejected` --> `ineligible`

Because we're pulling some of the columns into the post details, data will then be able to know if they, for example, pre-registered or why their registration was ineligible.

### Status Hierarchy

**When a RTV CSV has multiple records _per user_ we use the following hierarchy to determine which status should be reported on the Rogue post. If when importing, there is an existing status per the campaign and user from any previous import (TV or RTV), follow the hierarchy. **

1. `register-form`
2. `register-OVR`
3. `confirmed`
4. `ineligible`
5. `uncertain`

For example: If a user has a `confirmed` status already from a TV import, and the RTV file suggests that it should be `uncertain`, do not update.

We’ve established this hierarchy because each time a user interacts with the RTV form, a new row is created in the CSV. There are the edge cases when a user is chased to finish their registration that they would be interacting with the same row (thus the "steps"). The hierarchy is the simplest approach to dealing with varying statuses, but we anticipate some edge cases that we may need to deal with as they come up.

Here’s one example:

- User A completes the RTV form —> `register-form` status
- User A, for whatever reason, starts the RTV form again and drops off --> `uncertain` status

In this case, we would want to count the form completion (`register-form`). It’s important to note that the hierarchy is for internal reporting and doesn’t prevent the user from interacting with the RTV form if they want to do so.

### Dealing with Member Registrants

If an existing User is found using the NS ID, email, or number, we attempt to update the user's `voter_registration_status` profile field based on the new status from the record.

If there's an existing status on the user, we follow the same hierarchy rules established above. There are a few additional statuses we take into account:

- `unregistered` which is a status that can be set when creating an account in Northstar which denotes that the user has not registered to vote.
- `registration_complete` which is the status we assign to the profile when importing from RTV if the record's status is either `register-form` or `register-ovr`.

So the full hierarchy order taken into account when updating the profile is:

1. `registration_complete`
2. `confirmed`
3. `unregistered`
4. `ineligible`
5. `uncertain`

### Dealing with Non-Member Registrants

If the referral column doesn't have a NS ID, we should do what we do with the TV import.

1. Try to match to a member on number
2. Try to match to a member on email

If those don't work, then create a NS account for them with the relevant information (First name, last name, contact information) like we do with TurboVote. For the `sms_status` we should populate it for the time being with what's in the partner SMS opt-in column.

### Special Case: Referral Links

Online drives is one of the tactics we have for getting people to get their friends registered to vote. For example, someone would sign up for the campaign and they have their own personal registration page (w/ a RTV form on it w/ the same kind of tracking) that they share with their friends/family. The appeal for them is that on their campaign action page, it will show how many people has viewed their personal registration page (v2 feature enhancement might be upping this to show who has registered).

So, the Alpha sends their page to a Beta and they register. The Alpha's referral links look like this: https://vote.dosomething.org/member-drive?userId={userId}&r=user:{userId},campaign:{campaignID},campaignRunID={campaignRunID},source=web,source_details=onlinedrivereferral,referral=true

We've added `referral=true` to the link so that we can know to not attribute the registration to the NS ID that is present in the URL. In this case, this NS ID is the referrer and not the registrant.

If the referral column has `referral=true` in it, then proceed with the logic with dealing w/ non-member registrants above.

### How to count these as impact

Based on the above statuses, some should be counted as a RB and some should not. This determination was made by the executive team and allows us to report internally progress towards the organization's report back goal. Here's what counts as a reportback from the RTV export:

- `register-form`
- `register-OVR`

Note: `register-form` and `register-OVR` are the only statuses that count as _registrations_.

Rogue will NOT store this information, but will return a derived value in the JSON response when the voter registration post is created or read that holds this information. The logic to determine this is as follows:

```php
if (in_array($rogueStatus, ['confirmed', 'register-form', 'register-OVR'])) {
    $reportbackStatus = 'T';
} else {
    $reportbackStatus = 'F';
}
```

## What This Looks Like In Rogue

Post Example:

```
{
    "id" : 467424,
    "signup_id" : 9157041,
    "campaign_id" : 8017, // from "Tracking Source"
    "northstar_id" : "5af5xxxxxxxxxxxxxxxx0493", // user from "Tracking Source" (or new user)
    "type" : "voter-reg", // same for all
    "action" : "may-2018-rockthevote", // Month and Year of "Started Registration"
    "quantity" : null,
    "url" : null,
    "text" : null,
    "details": {"Tracking Source":"iframe?r=user:5af5xxxxxxxxxxxxxxxx0493,campaignID:7059,campaignRunID:8105,source:web,source_details:VoterBlockGeneralLYVC","Started registration":"2018-05-11 18:21:50 UTC","Finish with State":"No"}, // "Tracking Source", "Started registration", and "Finish with State".
    "status" : "ineligible", // We use "Status" and "Finish with State" to compute this in "Status Translation Rules" above
    "source" : "rock-the-vote", // same for all
    "source_detail" : NULL, // source_details from "Tracking Source"
    "created_at" : 2018-08-01 15:40:03, // Time of import
    "updated_at" : 2018-08-01 15:40:03, // Time of import
    "deleted_at" : null
}
```

Signup Example:

```
{
    "id" : 9359595,
    "campaign_id" : 8017,
    "campaign_run_id" : 8022,
    "northstar_id" : "5af5xxxxxxxxxxxxxxxx0493",
    "why_participated" : null,
    "quantity" : null,
    "quantity_pending" : null,
    "source" : "rock-the-vote",
    "source_detail" : NULL,
    "details": {"Tracking Source":"iframe?r=user:5af5xxxxxxxxxxxxxxxx0493,campaignID:7059,campaignRunID:8105,source:web,source_details:VoterBlockGeneralLYVC","Started registration":"2018-05-11 18:21:50 UTC","Finish with State":"No"},
    "created_at" : 2018-08-01 15:40:03,
    "updated_at" : 2018-08-01 15:40:03,
    "deleted_at" : null
}
```

## Notes

- The `details` on the post will have the row of information available for data to use.
- The `submission_created_at` date is when the importer ran. Details about when the registration was created/updated are in the `source_details`.
- All of these signups will have a `source` of `importer-client` (this is how messaging is suppressed in C.io)
- All of these posts have a `type` = `voter-reg`
- The month that the registration came in is what informs the `action` column (e.g., february-2018-rockthevote)
- All posts are created for a single Action ID, which is set via config variable. We previously would pass Campaign/Run IDs as query parameters to upsert a  `voter-reg` post for.
- If a user shares their UTM'ed URL with other people, there could be duplicate referral codes but associated with different registrants:
  See a [screenshot](https://cl.ly/0v210N283y2X) of what this data looks like (note: the user depicted in this spreadsheet is fake.)

## Open Questions

1. How can we distinguish between TV and RTV import? Do we need to?
2. RTV has more birthdays in it, can we use that on the NS profile when we create accounts? (TV didn't provide this)
3. We've been having some Chompy validation issues w/ the TV import -- is this the good time to tackle those things? If so, is a running list the best way of communicating some of the things we've seen in the referral column? There's one specific referral that's different and it's just `ads` -- these are from Google ads and the only way they could set up tracking that Google likes. Would love to talk through best way to deal with these...if it's something unique!
