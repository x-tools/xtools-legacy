<?php
/**
 * Main index
 *
 * @author Timo Tijhof, 2015
 * @license http://krinkle.mit-license.org/
 * @package mw-tool-example
 */

/**
 * Configuration
 * -------------------------------------------------
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../class.php';
// require_once __DIR__ . '/../config.php';

$tool = new ExampleTool();
$I18N = new Intuition( 'example' );

$kgBase = BaseTool::newFromArray( array(
	'displayTitle' => 'Example',
	'revisionId' => '0.0.0',
	'remoteBasePath' => dirname( $kgConf->getRemoteBase() ). '/',
	'I18N' => $I18N,
) );
$kgBase->setSourceInfoGithub( 'Krinkle', 'mw-tool-example', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$tool->run();
$kgBase->flushMainOutput();
