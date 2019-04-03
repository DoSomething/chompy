# Chompy

This is **Chompy**, the DoSomething.org importer app. Chompy is built using [Laravel 5.6](https://laravel.com/docs/5.6) and [bootstrap-sass](https://www.npmjs.com/package/bootstrap-sassbootstrap-sass).

### Getting Started

Check out the [documentation](https://github.com/DoSomething/chompy/blob/master/documentation/README.md) for details about Chompy imports. :frog:

### Contributing

To get started with development, you'll first need local instances of [Northstar](https://github.com/DoSomething/northstar) and [Rogue](https://github.com/DoSomething/rogue) setup to use for running Chompy imports on your localhost.

Next, fork and clone this repository, and [add it to your Homestead](https://github.com/DoSomething/communal-docs/blob/master/Homestead/readme.md).

```sh
# First, switch to PHP 7.1
$ php71

# Install dependencies
$ composer install && npm install

# Configure application & run migrations:
$ php artisan chompy:setup

# Finally, build the frontend assets:
$ npm run dev
```

When running `php artisan chompy:setup`, add your local instances of Northstar and Rogue, along with your local Northstar auth credentials.

We follow [Laravel's code style](http://laravel.com/docs/5.6/contributions#coding-style) and automatically
lint all pull requests with [StyleCI](https://github.styleci.io/repos/125392958). Be sure to configure
[EditorConfig](http://editorconfig.org) to ensure you have proper indentation settings.

### Testing
Performance & debug information is available at [`/__clockwork`](http://chompy.test/__clockwork), or using the [Chrome Extension](https://chrome.google.com/webstore/detail/clockwork/dmggabnehkmmfmdffgajcflpdjlnoemp).


### Security Vulnerabilities

We take security very seriously. Any vulnerabilities in Chompy should be reported to [security@dosomething.org](mailto:security@dosomething.org),
and will be promptly addressed. Thank you for taking the time to responsibly disclose any issues you find.

### License

&copy;2019 DoSomething.org. Chompy is free software, and may be redistributed under the terms specified
in the [LICENSE](https://github.com/DoSomething/chompy/blob/master/LICENSE) file. The name and logo for
DoSomething.org are trademarks of Do Something, Inc and may not be used without permission.
