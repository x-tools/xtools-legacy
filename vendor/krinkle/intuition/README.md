[![Packagist](https://img.shields.io/packagist/v/Krinkle/intuition.svg?style=flat)](https://packagist.org/packages/Krinkle/intuition) [![Build Status](https://travis-ci.org/Krinkle/intuition.svg?branch=master)](https://travis-ci.org/Krinkle/intuition)

# Intuition

## Install

It's recommended you use [Composer](https://getcomposer.org).

* Run `composer require Krinkle/intuition`.
* Include `vendor/autoload.php` in your program.

## Usage

To use it in a tool, read the [Usage documentation](https://github.com/Krinkle/intuition/wiki/Documentation#usage).

Example:

<pre lang="php">
require_once __DIR__ . '/vendor/autoload.php';

$I18N = new Intuition( 'mytool' );
$I18N->registerDomain( 'mytool', __DIR__ . '/messages' );

echo $I18N->msg( 'example' );
</pre>

## Getting involved

### Testing

Use [Composer](https://getcomposer.org) for managing dependenices (such as [PHPUnit](http://www.phpunit.de)). Install Composer via your preferred package manager, or from [source](https://getcomposer.org/download/).

Prior to runnig tests, ensure presence of local dev dependencies:
```
composer install
```

Run the tests:
```
./tests/run
```

A small amount of frontend code is integrated via [Grunt](http://gruntjs.com/) on [node.js](http://nodejs.org/). Install the Grunt command-line utility:
`npm install -g grunt-cli`

Prior to runnig tests, ensure presence of local dev dependencies:
```
npm install
```

Run the tests:
```
npm test
```

### Misc

To regenerate the `AUTHORS.txt`:
```
npm install && grunt authors
```
