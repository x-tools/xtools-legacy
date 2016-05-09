<?php
/**
 * Extract language data from MediaWiki core.
 *
 * @copyright 2011-2015 See AUTHORS.txt
 * @license CC-BY 3.0 <https://creativecommons.org/licenses/by/3.0/>
 * @package intuition
 */

if ( !isset( $argv[1] ) || strpos( $argv[1], '/messages' ) === false ) {
	$scriptName = basename( __FILE__ );
	echo "usage: php $scriptName <dir>\n\n  <dir>  The path to mediawiki/languages/messages\n";
	exit(1);
}
$msgDir = $argv[1];
if ( !is_readable( $msgDir ) ) {
	echo "error: Path to languages/messages not found\n";
	exit(1);
}

$dest = dirname( __DIR__ ) . '/language';
if ( !is_writable( $dest ) ) {
	echo "error: Unable to write to $dest\n";
	exit(1);
}

$data = array(
	'fallbacks' => array(),
	'rtl' => array(),
);
$rFallback = "/fallback = \\'(.*?)\\';/i";
$rIsRtl = '/rtl = true;/i';

foreach ( scandir( $msgDir ) as $file ) {
	$filePath = "$msgDir/$file";
	if ( $file === '.' || $file === '..' || !is_file( $filePath ) ) {
		continue;
	}

	$content = file_get_contents( $filePath );
	if ( !$content ) {
		echo "error: Unable to open $filePath\n";
		exit( 1 );
	}

	preg_match( '/Messages(.*?)\\.php/', $file, $fileMatch );
	$source = str_replace( '_', '-', strtolower( $fileMatch[1] ) );

	if ( preg_match( $rFallback, $content, $contentMatch ) ) {
		$fallbacks = array_map( 'trim', explode( ',', $contentMatch[1] ) );
		$data['fallbacks'][ $source ] = count( $fallbacks ) > 1 ? $fallbacks : $fallbacks[0];
	}

	if ( preg_match( $rIsRtl, $content ) ) {
		$data['rtl'][] = $source;
	}
}

$destFile = "$dest/fallbacks.json";
$written = file_put_contents( $destFile, json_encode( $data['fallbacks'], JSON_PRETTY_PRINT ) );
if ( !$written ) {
	echo "error: Failed to write $destFile\n";
	exit(1);
}

$destFile = "$dest/rtl.json";
$written = file_put_contents( $destFile, json_encode( $data['rtl'], JSON_PRETTY_PRINT ) );
if ( !$written ) {
	echo "error: Failed to write $destFile\n";
	exit(1);
}
