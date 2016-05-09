<?php
/**
 * Entry point for loading the Intuition client javascript.
 *
 * @copyright 2011-2014 See AUTHORS.txt
 * @license CC-BY 3.0 <https://creativecommons.org/licenses/by/3.0/>
 * @package intuition
 */

/**
 * Set up
 * -------------------------------------------------
 */

require_once __DIR__ . '/../vendor/autoload.php';

$I18N = new Intuition( array(
	'domain' => 'tsintuition',
	'mode' => 'dashboard',
) );
require_once __DIR__ . '/config.php';

// Cache for 30 minutes (in seconds)
$maxAge = 30 * 60;

/**
 * Request
 * -------------------------------------------------
 */

$env = $kgReq->getVal( 'env' );

/**
 * Response
 * -------------------------------------------------
 */
$kgReq->setHeader( 'Access-Control-Allow-Origin', '*' );
$kgReq->setHeader( 'Content-Type', 'text/javascript; charset=utf-8' );

if ( !$env ) {
	// HTTP 400 Bad Request
	http_response_code( 400 );
	echo '/* Parameter "env" is required. */';
	die;
}

if ( !in_array( $env, array( 'mw', 'standalone' ) ) ) {
	// HTTP 400 Bad Request
	http_response_code( 400 );
	echo '/* Invalid value for parameter "env". */';
	exit;
}


$jsEnvFile = dirname( __DIR__ ) . '/includes/js-env/intuition-' . $env . '.js';
if ( !is_readable( $jsEnvFile ) ) {
	// HTTP 500 Internal Server Error
	http_response_code( 500 );
	echo '/* Failed to read javascript file from disk. */';
	exit;
}

$mtime = max(
	filemtime( $jsEnvFile ),
	filemtime( __FILE__ )
);

if ( $mtime > 0 ) {
	$kgReq->setHeader( 'Last-Modified', gmdate( 'D, d M Y H:i:s', $mtime ) . ' GMT' );
	$kgReq->setHeader( 'Cache-Control', 'public, max-age=' . intval( $maxAge ) );
	$kgReq->setHeader( 'Expires', gmdate( 'D, d M Y H:i:s', time() + $maxAge ) . ' GMT' );

	if ( $kgReq->tryLastModified( $mtime ) ) {
		exit;
	}
}

echo str_replace(
	'apiPath = \'api.php\',',
	'apiPath = ' . json_encode( "{$I18N->dashboardHome}api.php" ) . ',',
	file_get_contents($jsEnvFile)
);
exit;
