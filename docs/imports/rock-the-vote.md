# Rock The Vote

We import CSVs from Rock The Vote (RTV) to upsert users and their `voter-reg` posts. We previously imported this data from TurboVote (TV) in 2016, 2018. See [VR Tech Inventory](https://docs.google.com/document/d/1xs2C3DNdD5h1j_abBrGVBNrsrxKvwn2VHDWweIEhvqc/edit?usp=sharing) for more details.

## Overview

The import upserts a `voter-reg` type post for each unique "Started registration" datetime we receive for a user -- saving it to an action we set via config variable. This import action is changed each election year, in order to track user registrations per election.

If a user registers to vote twice in 2020 (e.g. change of address), two `voter-reg` posts will be upserted for the user and this year's action. Prior to [changes made in April 2020](https://github.com/DoSomething/chompy/pull/154), the import would upsert a single post for all registrations for an action ID (e.g registering to vote twice in 2018 resulted in a single `voter-reg` post).

## Status

As of [April 2020](https://github.com/DoSomething/chompy/pull/153), we save the status provided by Rock The Vote on `voter-reg` posts for Rock the Vote, with the exception of using two different statuses for the `Complete` status:

- `register-form` - User completed the registration form (the row `Finish with State` column is set to `No`)

- `register-OVR` - User completed the registration form on their state's Online Voter Registration platform (the row `Finish with State` column is set to `Yes`)

We count `voter-reg` posts with these two `register-*` statuses as registrations (and reportbacks) within reporting. We also count the historical `confirmed` status imported from TurboVote as a registration.

The other status values returned from RTV are:

- `rejected`: a person was either not old enough to (pre-)register or did not check the box affirming they were a US citizen, and stopped the process

- `step-1`: a person entered email/ZIP code on the first page, then stopped.

  - Note -- we've seen PII entered in rows that are on Step 1, and TBD why this happens sometimes.

- `step-2`: user got to the second page to start filling out their personal info, but did not finish

- `step-3`/`step-4`: a person finished entering their registration information, but either (1) did not click to open/print their paper registration form, or (2) were eligible to finish on their state website, but did not click through

- `under-18`: a person was not old enough to (pre-)register in their state, but requested an automated 18th birthday reminder to register

These definitions can be found in the [RTV docs](https://www.rockthevote.org/programs-and-partner-resources/tech-for-civic-engagement/partner-ovr-tool-faqs/partner-ovr-tool-faqs/).

### Historical values

- We used to save all of the `step-*` status values as `uncertain`.

- We used to save `rejected` and `under-18` values as `ineligible`.

- The TurboVote data (which we imported before Rock The Vote), would supply a `confirmed` status - similar to the Rock the Vote `Completed` status.

### Status Hierarchy

Because RTV CSVs may contain multiple records _for a single user_, we use the following hierarchy, from lowest to highest, to determine which status should be reported on their Rogue post if a user post already exists for the import Action and its `Started registration` datetime:

- `uncertain`
- `ineligible`
- `under-18`
- `rejected`
- `step-1`
- `step-2`
- `step-3`
- `step-4`
- `confirmed`
- `register-OVR`
- `register-form`

For example: If a user has a `confirmed` status already from a previous TurboVote import, and the RTV file suggests that it should be `step-1`, do not update.

We’ve established this hierarchy because each time a user interacts with the RTV form, a new row is created in the CSV. There are the edge cases when a user is chased to finish their registration that they would be interacting with the same row (thus the "steps"). Here’s one example:

- User A completes the RTV form —> `register-form` status
- User A, for whatever reason, starts the RTV form again and drops off --> `step-2` status

In this case, we would want to count the form completion (`register-form`). It’s important to note that the hierarchy is for internal reporting and doesn’t prevent the user from interacting with the RTV form if they want to do so.

## Existing Users

If an existing User is found using the NS ID, email, or number, we may update the user's `voter_registration_status` or SMS preferences based on the new values from the record.

### Voter Registration Status

If there's an existing status on the user, we follow the same hierarchy rules established above but check for a few additional statuses:

- `unregistered` -- can be set when creating an account on the web, denoting that the user has not registered to vote.

- `registration_complete` -- set from our RTV import if the record's status is either `register-form` or `register-ovr`.

So the full hierarchy order taken into account when updating the profile from lowest to highest is:

- `uncertain`
- `ineligible`
- `under-18`
- `rejected`
- `unregistered`
- `step-1`
- `step-2`
- `step-3`
- `step-4`
- `confirmed`
- `registration_complete`

### Mobile

If an existing user has a null `mobile` profile field and provides a phone number via RTV form, we save it to the user's `mobile` profile field.

**Notes**:

- We do **not** override an existing phone number.
- We save a user's mobile number regardless of whether they opt-in to SMS messaging from DS.

### SMS Status

If an existing user **opts-in** to voting-related SMS messaging from DS via RTV form, we update `sms_status` as `active` if current value is either null, `less`, `stop`, or `undeliverable`.

If an existing user **opts-out** of voting-related SMS messaging from DS via RTV form, we update `sms_status` as `stop` if current value is null or `undeliverable`.

### SMS Subscription Topics

If an existing user **opts-in** to voting-related SMS messaging from DS via RTV form, we add the `voting` topic to their `sms_subscription_topics` if it doesn't exist.

If an existing user **opts-out** of voting-related SMS messaging from DS via RTV form, we remove the `voting` topic from their `sms_subscription_topics` if it exists.

## New Users

If the referral column doesn't have a NS ID, we try to find to a user by email, and last by mobile number. If a user is still not found, then create a NS account for them with PII provided from Rock The Vote:

- First Name
- Last Name
- Street Address, City, Zip
- Mobile

Note: We do not import the user's birthdate.

### Online Drives

Online drives is one of the tactics we have for getting people to get their friends registered to vote. For example, someone would sign up for the campaign and they have their own personal registration page (w/ a RTV form on it w/ the same kind of tracking) that they share with their friends/family. The appeal for them is that on their campaign action page, it will show how many people has viewed their personal registration page (v2 feature enhancement might be upping this to show who has registered).

So, the Alpha sends their page to a Beta and they register. The Alpha's referral links look like this: https://vote.dosomething.org/member-drive?userId={userId}&r=user:{userId},campaign:{campaignID},campaignRunID={campaignRunID},source=web,source_details=onlinedrivereferral,referral=true

We've added `referral=true` to the link so that we can know to not attribute the registration to the NS ID that is present in the URL. In this case, this NS ID is the referrer and not the registrant.

If the referral column has `referral=true` in it, then proceed with the logic with dealing w/ non-member registrants above.

## Notes

- Data uses the post `details` to determine `source` and `source_detail` used in voter registration reporting.
- The `submission_created_at` date is when the importer ran. Details about when the registration was created/updated are in the `source_details`.
- All of these signups will have a `source` of `importer-client` (this is how messaging is suppressed in C.io)
- In early iterations of the import, the month that the registration came in would inform the `action` column (e.g., february-2018-rockthevote)
- In early iterations of the import, we would pass Campaign/Run IDs as parameters within the referral code to use when upsert a `voter-reg` post.
- If a user shares their UTM'ed URL with other people, there could be duplicate referral codes but associated with different registrants:
  See a [screenshot](https://cl.ly/0v210N283y2X) of what this data looks like (note: the user depicted in this spreadsheet is fake.)
