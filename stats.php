<?php

//DDoSing the tools. No user agent, spambots
if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) if( in_array( $_SERVER['HTTP_X_FORWARDED_FOR'], array( '218.24.179.197', '87.106.129.206', '59.46.193.240', '59.46.193.241' ) ) ) die( "Your IP has been disabled as it has been the cause of memory issues. Please contact the administrator");	
if( !@$_SERVER['HTTP_USER_AGENT'] ) die("Access denied");
if(isset( $_SERVER['HTTP_USER_AGENT'] ) && substr( $_SERVER['HTTP_USER_AGENT'], 0, 11 ) == "User-Agent:" ) die("Fuck off");
if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
	if( substr( $_SERVER['HTTP_X_FORWARDED_FOR'], 0, 6 ) == '66.249' ) die( "You're not Google. Go away." );
}

require_once('/data/project/xtools/database.inc');

function addStat( $tool, $fullurl, $referer, $agent ) {
        if( $referer == "" ) { $referrer = "Direct"; }
        global $toolserver_username,$toolserver_password;
        mysql_connect( "tools-db", $toolserver_username,$toolserver_password );
        @mysql_select_db( "p50380g50570_xtools" ) or print mysql_error( );
				$tool = mysql_escape_string( $tool );
        $url = mysql_escape_string( $fullurl );
        $referer = mysql_escape_string( $referer );
        $agent = mysql_escape_string( $agent );
				if ( !isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) $ip = mysql_escape_string( $_SERVER['REMOTE_ADDR'] );
        else $ip = mysql_escape_string( $_SERVER['HTTP_X_FORWARDED_FOR'] );
        $date = mysql_escape_string( date('l jS \of F Y h:i:s A') );
        $query = "INSERT INTO stats (tool, ip, referer, date, agent, url) VALUES ('$tool', '$ip', '$referer', '$date', '$agent', '$url');";
        $result = mysql_query( $query );
        mysql_close( );
        return $result;
}

function addStatV2( $tool ) {
		$surl = "http://tools.wmflabs.org".$_SERVER['REQUEST_URI'];
		addStat( $tool, $surl, @$_SERVER['HTTP_REFERER'], $_SERVER['HTTP_USER_AGENT'] );
}

function addStatV3( $tool ) {
		$fullurl = "http://tools.wmflabs.org".$_SERVER['REQUEST_URI'];
		$referer = @$_SERVER['HTTP_REFERER'];
		$agent = @$_SERVER['HTTP_USER_AGENT'];
	
        if( $referer == "" ) { $referer = "Direct"; }
        global $toolserver_username, $toolserver_password;
        
        $dbr = new Database( 
			'tools-db', 
			$toolserver_username, 
			$toolserver_password, 
			'p50380g40030'
		);
		
		if ( !isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) $ip = mysql_escape_string( $_SERVER['REMOTE_ADDR'] );
    else $ip = mysql_escape_string( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		
		try {
			$res = $dbr->insert(
				'stats',
				array(
					'tool' => $tool,
					'ip' => $ip,
					'referer' => $referer,
					'date' => date('l jS \of F Y h:i:s A'),
					'agent' => $agent,
					'url' => $fullurl
				)
			);
		}
		catch( Exception $e ) {}
}
