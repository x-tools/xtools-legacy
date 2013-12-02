<?php

//Requires
	require_once( '/data/project/xtools/public_html/WebTool.php' );


//Load WebTool class
	$wt = new WebTool( 'Translator', 'translate', array( 'database', 'showonlyerrors', 'peachy' ) );
	WebTool::setMemLimit();
	WebTool::setDBVars();
	/*UPDATE ME*/$wtSource = "//code.google.com/p/soxred93tools/source/browse/trunk/web/translate";
	$wtTranslate = true;


//Get list of languages

#FIXME: There is no workaround for this at the moment until labs has the bd directory setup
$dbr = new Database( 
	'sql-toolserver', 
	$toolserver_username, 
	$toolserver_password, 
	'toolserver'
);

$res = $dbr->select(
	'wiki',
	'DISTINCT lang',
	array(
		'is_meta != 1',
		'is_multilang != 1',
		'is_closed != 1',
	),
	array(
		'ORDER BY' => 'lang ASC'
	)
);

$langs = array();
foreach($res as $r) {
	$langs[] = $r['lang'];
}
$s = array_search( 'en-simple', $langs );
unset( $langs[$s] );
$langs[] = 'simple';
sort($langs);
$langs[] = 'qqq';


$tools = array(
	'Global' => array(
		'uri' => '/data/project/xtools/public_html/configs/',
		'name' => 'main'
	),
	'Articleinfo' => array(
		'uri' => '/data/project/xtools/public_html/articleinfo/configs/',
		'name' => 'articleinfo'
	),
	'Bash' => array(
		'uri' => '/data/project/xtools/public_html/bash/configs/',
		'name' => 'bash'
	),
	'Autoedits' => array(
		'uri' => '/data/project/xtools/public_html/autoedits/configs/',
		'name' => 'autoedits'
	),
	'Editcounter' => array(
		'uri' => '/data/project/xtools/public_html/pcount/configs/',
		'name' => 'pcount'
	),
	'Replag' => array(
		'uri' => '/data/project/xtools/public_html/replag/configs/',
		'name' => 'replag'
	),
	'Translator' => array(
		'uri' => '/data/project/xtools/public_html/translate/configs/',
		'name' => 'translate'
	),
);

//Show form if &action parameter is not set (or empty)
	if( !$wgRequest->getSafeVal( 'getBool', 'action' ) ) {
		
			$content->assign( 'form', true );
			$content->assign( 'tools', $tools );
			$content->assign( 'langs', $langs );
			
			if( $wgRequest->getBool( 'usetool' ) ) $content->assign( 'usetool', $wgRequest->getSafeVal( 'usetool' ) );
			
		WebTool::assignContent();
	}
	
	
	
	
	
if( $wgRequest->getSafeVal( 'action' ) != "admin" ) {

	//Set the article variables
		$tool = $wgRequest->getSafeVal( 'toolname' );
		$lang = $wgRequest->getSafeVal( 'lang' );
	
	if( !in_array( $lang, $langs ) ) WebTool::toDie( $phptemp->get_config_vars( "invalidlanguage", $lang ) );
	if( !isset( $tools[$tool] ) ) WebTool::toDie( $phptemp->get_config_vars( "invalidtool", $tool ) );
	
	$fauxsmarty = new Smarty();

	$fauxsmarty->config_load( '../../../../..' . $tools[$tool]['uri'] . 'en.conf', $tools[$tool]['name'] );

	if( is_file( $tools[$tool]['uri'] . $lang . '.conf' ) ) {
		$fauxsmarty->config_load( '../../../../..' . $tools[$tool]['uri'] . $lang . '.conf', $tools[$tool]['name'] );
	}
	
	if( $wgRequest->getSafeVal( 'action' ) == 'step1' ) {
		//Load Smarty objects
		$qqqsmarty = new Smarty();
		$ensmarty = new Smarty();
		
		$ensmarty->config_load( '/data/project/xtools/public_html/configs/en.conf', 'main' );
		$ensmarty = $ensmarty->get_config_vars();
		
		if( is_file( $tools[$tool]['uri'] . 'qqq.conf' ) ) {
			$qqqsmarty->config_load( '../../../../..' . $tools[$tool]['uri'] . 'qqq.conf', $tools[$tool]['name'] );
		}
		
		$config_vars = $fauxsmarty->get_config_vars();
		
		foreach( $config_vars as $name => $value ) {
			if( isset( $ensmarty[$name] ) && $tool != "Global" ) { unset( $config_vars[$name] ); continue; }
			
			$config_vars[$name] = array(
				'value' => $value,
				'qqq' => @$qqqsmarty->get_config_vars( $name )
			);
		}
		
		$content->assign( "showvars", true );
			$content->assign( "config_vars", $config_vars );
			$content->assign( "tool", $tool );
			$content->assign( "lang", $lang );
	}
	elseif( $wgRequest->getSafeVal( 'action') == 'step2' ) {
		//Connect to SQL DB
		$dbr = new Database( 
			'tools-db', 
			$toolserver_username, 
			$toolserver_password, 
			'p50380g50570_xtools'
		);
		$dbr->query( "SET NAMES 'utf8'" );
		
		
		$varsToGet = array_keys( $fauxsmarty->get_config_vars() );
		$sentVars = $wgRequest->getValues( $varsToGet );
		
		$diff = "[{$tools[$tool]['name']}]\n";
		foreach( $sentVars as $key => $get ) {
			$diff .= $key . " = \"$get\"\n";
		}
		
		
		try {
			$res = $dbr->insert(
				'translations',
				array(
					'tr_tool' => $tool,
					'tr_lang' => $lang,
					'tr_text' => $diff,
					'tr_date' => date( 'YmdHis' ),
					'tr_ip' => $wgRequest->getIP()
				)
			);
		} catch( DBError $e ) {
			WebTool::toDie( $phptemp->get_config_vars( 'mysqlerror', $e->getMessage() ) );
		}
		
		if( $res ) {
			$content->assign( "success", true );
		}
		
	}
}
else {
	
	if( $wgRequest->getSafeVal( 'password' ) == file_get_contents( '/data/project/xtools/.password' ) ) {
	
		$dbr = new Database( 
			'tools-db', 
			$toolserver_username, 
			$toolserver_password, 
			'p50380g50570_xtools'
		);
		$dbr->query( "SET NAMES 'utf8'" );
			
		if( !$wgRequest->getBool( 'approve' ) ) {

			try {
				$res = $dbr->select(
					'translations',
					'*',
					array(
						'tr_done' => 0,
						'tr_denied' => 0,
					)
				);
				
				if( !count( $res ) ) WebTool::toDie( "No pending translations" );
				
				$submissions = array();
				foreach( $res as $row ) {
					$row['tr_date'] = date( 'r', strtotime( $row['tr_date'] ) );
					$submissions[] = array_merge( $row, array( 'tr_diff' => nl2br( getTextDiff( 'unified', htmlentities( @file_get_contents( $tools[$row['tr_tool']]['uri'] . $row['tr_lang'] . '.conf' ) ), htmlentities( $row['tr_text'] ) ) ) ) );
					
		
				}
				
				
				$content->assign( "adminlist", true );
					$content->assign( "submissionlist", $submissions );
					$content->assign( "password", file_get_contents( '/data/project/xtools/.password' ) );
				
			} catch( DBError $e ) {
				WebTool::toDie( $phptemp->get_config_vars( 'mysqlerror', $e->getMessage() ) );
			}
		
		}
		else {
		
			$post = $wgRequest->getValues();
			
			foreach( $post as $name => $val ) {	
				if( substr( $name, 0, 7 ) != "result-" ) {
					unset( $post[$name] );
				}
			}
			
			foreach( $post as $name => $result ) {
				if( $result == "approve" ) {
					$dbr->update(
						'translations',
						array(
							'tr_done' => '1',
						),
						array(
							'tr_id' => str_replace( substr( $name, 0, 7 ), '', $name )
						)
					);
					
					$res = $dbr->select(
						'translations',
						'tr_text, tr_lang, tr_tool',
						array(
							'tr_id' => str_replace( substr( $name, 0, 7 ), '', $name )
						),
						array( 'LIMIT' => 1 )
					);
					
					foreach( $res as $row ) {
						file_put_contents( $tools[$row['tr_tool']]['uri'] . $row['tr_lang'] . '.conf', $row['tr_text'] );
						
						$content->assign( 'donemsg', "Done. See <a href='" . $tools[$row['tr_tool']]['uri'] . $row['tr_lang'] . '.conf' . ">~soxred93/pcount/configs/" . $row['tr_lang'] . ".conf</a>" );
					}
				}
				else {
					$dbr->update(
						'translations',
						array(
							'tr_done' => '1',
							'tr_denied' => '1',
						),
						array(
							'tr_id' => str_replace( substr( $name, 0, 7 ), '', $name )
						)
					);
					
					
				}
			}
			
		}
	}
	else {
		$content->assign( "passmenu", true );
	}
}

WebTool::finishScript();

