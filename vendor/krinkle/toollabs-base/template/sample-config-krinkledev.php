<?php
/**
 * Tool configuration (example for localhost development)
 */

$kgConf->remoteBase = '//localhost/mw-tool-example/public_html/base';

$tool->setSettings(array(
	'foo' => 'example',
));

$kgCache->set(
	kfCacheKey( 'base', 'labsdb', 'meta', 'dbinfos' ),
	array(
		'krinklewiki' => array(
			'dbname' => 'krinklewiki',
			'family' => 'wikipedia',
			'url' => 'http://alpha.wikipedia.krinkle.dev',
			'slice' => 's0.local'
		),
		'metawiki' => array(
			'dbname' => 'metawiki',
			'family' => 'special',
			'url' => 'https://meta.wikimedia.org',
			'slice' => 's0.local'
		)
	)
);
