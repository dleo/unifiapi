# unifiapi
## Unifi API for PHP.
This is an adaptation for Unifi sh api (https://dl.ubnt.com/unifi/5.3.8/unifi_sh_api).

## Installation

Install the latest version with

```bash
$ composer require dleo/unifiapi
```

## Basic Usage

```php
<?php

use Unifi\Api;

// connect to Unifi Controller
$api = new Api();

```

## About

### Requirements

- Api works with PHP 7.0 or above.

### Submitting bugs and feature requests

Bugs and feature request are tracked on [GitHub](https://github.com/dleo/unifi/issues)

### Author

David López- <dleo.lopez@gmail.com> - <http://twitter.com/dleolopez>

### License

Unifi Api is licensed under the GNU GPLv3 License - see the `LICENSE` file for details

### Acknowledgements

This library is heavily inspired by Unifi API (https://dl.ubnt.com/unifi/5.3.8/unifi_sh_api)
library, although most concepts have been adjusted to fit to the PHP world.

