# Chompy

Chompy imports DoSomething users and activity from third-party source data, which is ingested via API request or a staff member manually uploading a CSV.

## API

Third-parties with authorized access can post data to the Chompy API. See [API documentation](https://github.com/DoSomething/chompy/tree/master/docs/endpoints).

## CSV

Chompy supports imports of three types of CSV:

- [Rock The Vote voter registrations](https://github.com/DoSomething/chompy/tree/master/docs/imports#rock-the-vote)

- [Email subscriptions](https://github.com/DoSomething/chompy/tree/master/docs/imports#email-subscriptions) to newsletters

- [Mute promotions](https://github.com/DoSomething/chompy/tree/master/docs/imports#mute-promotions) for deleting Customer.io profiles

Staff members may login to Chompy with their Northstar credentials, and select a CSV to import. The uploaded file is stored on S3, and then a [queue job](https://laravel.com/docs/5.6/queues) is pushed onto a Redis queue to import records from the CSV as users and/or activity.

### Pusher

Chompy uses [Pusher](https://pusher.com/) as a [broadcast driver](https://laravel.com/docs/5.6/broadcasting) to update the import progress bar in real time.
