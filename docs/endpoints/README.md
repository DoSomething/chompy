# Chompy API

This is the Chompy API, it is used for Third Parties to send information to our systems to integrate with Rogue, our user activity service.

This endpoint is secured behind a static third party API Key. To use, please reach out to DoSomething.org for credentials.

## Endpoints

### v1

#### CallPower Third Party Integration

| Endpoint               | Functionality                                                                              |
| ---------------------- | ------------------------------------------------------------------------------------------ |
| `POST /api/v1/callpower/call`   | [Create a post](endpoints/callpower.md#create-a-callpower-post) |

#### SoftEdge Third Party Integration

| Endpoint               | Functionality                                                                              |
| ---------------------- | ------------------------------------------------------------------------------------------ |
| `POST /api/v1/softedge/email`   | [Create a post](endpoints/softedge.md#create-a-softedge-post) |


