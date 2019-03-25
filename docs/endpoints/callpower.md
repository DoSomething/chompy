## CallPower Posts

## Create a CallPower Post

```
POST /api/v1/callpower/call
```

- **mobile**: required.
  The mobile number of the user who makes the phone call.
- **callpower_campaign_id**: (int) required.
  The CallPower campaign_id given by the CallPower system.
- **status** (string) required.
  The status of the CallPower call (e.g. completed, busy, failed, no answer, cancelled, unknown)
- **call_timestamp** (date) required.
  The timestamp of when the call was made.
- **call_duration** (int) required.
  The length of the call.
- **campaign_target_name** (string) required.
  The name of the target the user called.
- **campaign_target_title** (string) required.
  The title of the target the user called.
- **campaign_target_district** (string) required.
  The district of the target the user called.
- **callpower_campaign_name** (string) required.
  The CallPower campaign name given in CallPower.
- **number_dialed_into** required.
  The number the user called to connect to the target.

Example request body:
```
[
  "mobile" => "+16171234567",
  "callpower_campaign_id" => 1,
  "status" => "completed",
  "call_timestamp" => 2017-11-07 18:54:10.829655,
  "call_duration" => 36,
  "campaign_target_name" => "Mickey Mouse",
  "campaign_target_title" => "Representative",
  "campaign_target_district" => "FL-7",
  "callpower_campaign_name" => "DefendDreamers_Nov9_CongressCalls",
  "number_dialed_into" => "+12028519273",
]
```

Example response:

```
200 OK
```