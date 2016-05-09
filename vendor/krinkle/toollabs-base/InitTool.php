<?php
/**
 * Initialize global context for web tools.
 *
 * @author Timo Tijhof, 2015
 * @license Public domain
 * @package toollabs-base
 * @since v0.1.0
 */

global $kgConf, $kgReq, $kgCache;

require_once __DIR__ . '/src/GlobalDefinitions.php';
require_once __DIR__ . '/src/GlobalConfig.php';

// Never overwrite $kgConfig, but if not set already
// make sure GlobalConfig is initiated
if ( !isset( $kgConf ) || !is_object( $kgConf ) ) {
	$kgConf = new GlobalConfig();
}

require_once __DIR__ . '/src/GlobalFunctions.php';

require_once __DIR__ . '/src/Cache.php';
require_once __DIR__ . '/src/HtmlSelect.php';
require_once __DIR__ . '/src/HttpRequest.php';
require_once __DIR__ . '/src/LabsDB.php';
require_once __DIR__ . '/src/Request.php';
require_once __DIR__ . '/src/Wiki.php';

// POST overrides GET data
// We don't use $_REQUEST here to avoid interference from cookies.
$kgReq = new Request( $_POST + $_GET );

$kgCache = new Cache( array(
	new MemoryCacheStore()
) );
$kgCache->enableHarvest();

// Backward compatibility: Deprecated, use composer instead and set config
// from consumer application instead.
if ( file_exists(  __DIR__ . '/LocalConfig.php' ) ) {
	require_once __DIR__ . '/LocalConfig.php';
}

// Config init must have access to GlobalFunctions, $kgReq, and $kgCache.
// And must run after LocalConfig.
$kgConf->initConfig();

function kfIncludeMwClasses() {
	require_once __DIR__ . '/lib/mw-mock.php';
}
kfIncludeMwClasses();

// Debug
if ( $kgConf->isDebugMode() ) {
	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );
}

require_once __DIR__ . '/src/BaseTool.php';
require_once __DIR__ . '/src/KrToolBaseClass.php';
