# Couchbase PHP Beer-Sample Application

This sample application demonstrates the usage of the Couchbase PHP SDK 1.1 in
combination with the Couchbase Server 2.0 release.

The official tutorial belonging to this application can be found
[here](http://www.couchbase.com/docs/couchbase-sdk-php-1.1/tutorial.html).

It is a very simple web application that should show off the basics on how to
interact with Couchbase Server 2.0 on both key-based and view-based operations.

## Requirements
Please make sure to have PHP 5.3 and Composer available. You also need to have
the Couchbase Extension (Version 1.1 or higher) installed.

## Installation
Clone the application (or follow along in the tutorial), and run

```
php composer.phar install
```

Make sure to clone the application inside the /beersample-php subdirectory of
the WEBROOT, because otherwise you need to change all the absolute links in
there as well.

## Configuration
The application should be able to run out of the box if you have the beer-sample
dataset installed and Couchbase Server 2.0 is running on your local machine. You
can tune the settings on top of the `index.php` file:

```php
define("SILEX_DEBUG", true);
define("COUCHBASE_HOSTS", "127.0.0.1");
define("COUCHBASE_BUCKET", "beer-sample");
define("COUCHBASE_PASSWORD", "");
define("COUCHBASE_CONN_PERSIST", true);
define("INDEX_DISPLAY_LIMIT", 20);
```

Have fun!