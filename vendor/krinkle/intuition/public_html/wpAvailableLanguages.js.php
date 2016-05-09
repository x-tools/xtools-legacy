<?php
/**
 * wpAvailableLanguages.js.php
 *
 * @author Timo Tijhof, 2012-2014
 * @license CC0 <https://creativecommons.org/publicdomain/zero/1.0/legalcode>
 * @license MIT <http://krinkle.mit-license.org/>
 */

// Configuration
date_default_timezone_set( 'UTC' );
ini_set(
	'user_agent',
	'wpAvailableLanguages.js.php/v1.0.1 (rev 2014-02-26; Author: github.com/Krinkle)'
);

/**
 * Polyfill for json_encode JSON_UNESCAPED_UNICODE (new in PHP 5.4.0) for PHP 5.3
 */
function kfJsonEncode( $arr ) {
	array_walk_recursive( $arr, function ( &$item, $key ) {
		if ( is_string( $item ) ) {
			$item = mb_encode_numericentity( $item, array( 0x80, 0xffff, 0, 0xffff ), 'UTF-8' );
		}
	} );
	return mb_decode_numericentity( json_encode( $arr), array( 0x80, 0xffff, 0, 0xffff ), 'UTF-8' );
}

// Header
header( 'Content-Type: text/javascript; charset=utf-8' );

// Download
$result = file_get_contents( 'http://commons.wikimedia.org/w/api.php?' . http_build_query( array(
	'format' => 'json',
	'action' => 'query',
	'meta' => 'siteinfo',
	'siprop' => 'languages'
) ) );

// Convert to more compact format
$result = json_decode( $result, /* $assoc = */ true );
$result = $result['query']['languages'];// [0] => array('code' => 'af', '*' => 'Afrikaans')
$json_return = array();
foreach ( $result as $lang ) {
	$json_return[ $lang['code'] ] = $lang['*'];
}

// Spit it out
echo '// Update from https://tools.wmflabs.org/intuition/wpAvailableLanguages.js.php'
	. ' - Last update: ' . date( 'r' ) . "\n"
	. 'window.wpAvailableLanguages='
	. kfJsonEncode( $json_return )
	. ';';

die;
