# Chompy

Chompy imports users and activity into the DoSomething API from third-party source data, which is ingested via API request or a staffer manually uploading a CSV.

## API

Third-parties with authorized access can post data to the Chompy API. See [API documentation](https://github.com/DoSomething/chompy/tree/master/docs/endpoints).

## CSV

Chompy supports imports of two types of CSV:

* Rock The Vote voter registrations

* Email subscriptions to newsletters

A staffer can login to Chompy with their Northstar credentials, and select a CSV to import. The file is stored on S3, and then a [queue job](https://laravel.com/docs/5.6/queues) is pushed onto the Redis queue to import records from the CSV as users and/or activity.

### Pusher

For CSV uploads, there is a progress bar that gets updated in real time using [Pusher](https://pusher.com/) as a [broadcast driver](https://laravel.com/docs/5.6/broadcasting).

Log in to the Pusher Dashboard by going to https://pusher.com/ and signing in with the credentials in LastPass. 

There are three different pusher app environments and the credentials for connecting to them can be found in `Channel Apps -> {select app name} -> app keys` (ex. https://cl.ly/2o2G3F1v3X2v)

The [Debug Console](https://dashboard.pusher.com/apps/550921/console/realtime_messages) in the Pusher Dashboard will let you see events coming in and going out of each pusher app. 
