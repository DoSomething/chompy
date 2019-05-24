## SoftEdge Posts

## Create a SoftEdge Post

```
POST /api/v1/softedge/email
```

- **northstar_id**: required.
  The `northstar_id` of the user who sent the email.
- **action_id**: (int) required.
  The `id` of action the post is associated to.
- **email_timestamp** (date) required.
  The timestamp of when the email was sent.
- **campaign_target_name** (string) required.
  The name of the target the user emailed.
- **campaign_target_title** (string) required.
  The title of the target the user emailed.
- **campaign_target_district** (string)
  The district of the target the user emailed.

Example request body:
```
[
  "northstar_id" => "12345678",
  "action_id" => 1,
  "call_timestamp" => 2017-11-07 18:54:10.829655,
  "campaign_target_name" => "Mickey Mouse",
  "campaign_target_title" => "Representative",
  "campaign_target_district" => "FL-7",
]
```

Example response:

```
200 OK
```
