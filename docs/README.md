# How to contribute

Follow the [installation instructions](https://github.com/DoSomething/chompy/blob/master/docs/installation.md) to set up your local environment. 

## How it works

Chompy is built in Laravel 5.6 on the backend and a frontend built with JQuery and Bootstrap 3.

Staff members can login and upload a CSV for two types of imports:

* Rock The Vote voter registrations

* Email subscriptions to newsletters

We store the uploaded file on s3, and then push a [queue job](https://laravel.com/docs/5.6/queues) onto the redis queue that does the work of processing the records in the CSV as defined by the business rules. 


## Logging

In order to see progress of the import, there is a progress bar that gets updated in real time using [Pusher](https://pusher.com/) as a [broadcast driver](https://laravel.com/docs/5.6/broadcasting). There is also logging that happens on the backend.

[Pusher](https://pusher.com/) is a third-party service that lets you create real-time apps. In the case of this app, it is used to just show job progress. 

Log in to the Pusher Dashboard by going to https://pusher.com/ and signing in with the credentials in LastPass. 

There are three different pusher app environments and the credentials for connecting to them can be found in `Channel Apps -> {select app name} -> app keys` (ex. https://cl.ly/2o2G3F1v3X2v)

The [Debug Console](https://dashboard.pusher.com/apps/550921/console/realtime_messages) in the Pusher Dashboard will let you see events coming in and going out of each pusher app. 

### How to send/subscribe to a pusher event

Pusher is used as the "Broadcast Driver" that is supported in Laravel. Follow the [Laravel Documentation](https://laravel.com/docs/5.6/broadcasting) on Broadcasts to see how it is implemented.
