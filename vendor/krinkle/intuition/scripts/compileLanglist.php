<?php
/**
 * Extract language data from MediaWiki core.
 *
 * @copyright 2011-2015 See AUTHORS.txt
 * @license CC-BY 3.0 <https://creativecommons.org/licenses/by/3.0/>
 * @package intuition
 */

$dest = dirname( __DIR__ ) . '/language';
if ( !is_writable( $dest ) ) {
	echo "error: Unable to write to $dest\n";
	exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

$intuition = new Intuition( 'general' );
$data = $intuition->generateLanguageList();

$code = '<?php return ' . var_export( $data, true ) . ';';

$destFile = "$dest/langlist.php";
$written = file_put_contents( $destFile, $code );
if ( !$written ) {
	echo "error: Failed to write $destFile\n";
	exit(1);
}
