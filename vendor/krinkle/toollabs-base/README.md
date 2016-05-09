[![Packagist](https://img.shields.io/packagist/v/Krinkle/toollabs-base.svg?style=flat)](https://packagist.org/packages/Krinkle/toollabs-base) [![Build Status](https://travis-ci.org/Krinkle/toollabs-base.svg?branch=master)](https://travis-ci.org/Krinkle/toollabs-base)

# Tool Labs Base

## Install

It's recommended you use [Composer](https://getcomposer.org).

* Run `composer require Krinkle/toollabs-base`.
* Create a symlink to `vendor/krinkle/toollabs-base/public_html` from your application's public directory.
* Include `vendor/autoload.php` in your program.
* Set `$kgConf->remoteBase` to where `toollabs-base/public_html is exposed (e.g. `https://example.org/mytool/base` or `http://localhost/mytool/public_html/base`).

## Usage

It's recommended to set `$kgConf->remoteBase` (and any variables your tool may need) from a separate `config.php` file.

<pre lang="php">
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

$kgBase = BaseTool::newFromArray( array(
	'displayTitle' => 'Example',
) );
$kgBase->flushMainOutput();
</pre>

See [Template](/template) for an example.

## License

This project is released in the public domain.

BaseTool comes with a few classes from the [MediaWiki 1.24](https://www.mediawiki.org/) code base. MediaWiki 1.24 is a collaborative project released under the GNU General Public License v2. For full license text and authors refer to https://git.wikimedia.org/blob/mediawiki%2Fcore.git/fb9023bb2e/CREDITS and https://git.wikimedia.org/blob/mediawiki%2Fcore.git/fb9023bb2e/COPYING.
