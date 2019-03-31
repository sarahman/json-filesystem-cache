## Simple JSON Cache Client Adapter ##
### v1.0.0 ###
PHP Cache library built by PSR-16 simple cache interface

You can find implementations of the specification by looking for packages providing the
 `psr/simple-cache-implementation` virtual package.

#### Installation ####
This library is installed via [Composer](http://getcomposer.org). To install, 
- simply add to your `composer.json` file: `"sarahman/json-filesystem-cache": "^1.0"`

OR

- run this command:
```
$ composer require sarahman/sarahman/json-filesystem-cache
```

#### Usages ####

- Create a file named `test.php` in your root directory and add these following codes:
 
```php
<?php

require "vendor/autoload.php";

$cache = new Sarahman\JSONCache\JSONFileSystemCache(); // the custom cache directory can be set through the parameter.

// Set Cache key.
$data = [
    'sample' => 'data',
    'another' => 'data'
];

$cache->set('your_custom_key', $data);

// Check cached key exists or not.
$cache->has('your_custom_key');

// Get Cached key data.
$cache->get('your_custom_key');

```
- Then run `php test.php`.

#### Documentation ####
[psr/simple-cache-implementation](https://packagist.org/providers/psr/simple-cache-implementation)

#### Support ####

If you are having general issues with this package, feel free to contact me through [Gmail](mailto:aabid048@gmail.com).

If you believe you have found an issue,
 please report it using the [GitHub issue tracker](https://github.com/sarahman/json-filesystem-cache/issues),
 or better yet, fork the repository and submit a pull request.

If you're using this package, I'd love to hear your thoughts. Thanks!
