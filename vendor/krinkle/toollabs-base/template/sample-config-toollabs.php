<?php
/**
 * Tool configuration (example for a tool in Tool Labs)
 */

$kgConf->remoteBase = 'https://tools.wmflabs.org/example/base';
$kgConf->cookiePrefix = 'example_';

$kgCache->addStore(
	new RedisCacheStore( array(
		'preset' => 'toollabs',
		'prefix' => 'tools.example:',
	) )
);
