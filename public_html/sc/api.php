<?php

$final_array = array();

//error_reporting(E_ALL);
ini_set("display_errors", 1);

require( '/data/project/xtools/API.php' );
include( '/data/project/xtools/stats.php' );
$tool = 'ECAPI';
$surl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
if (isset($_GET['name'])) {
	addStat( $tool, $surl, @$_SERVER['HTTP_REFERER'], $_SERVER['HTTP_USER_AGENT'] );//Stat checking
}
unset($tool, $surl);

function getUrl($url) {
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_MAXCONNECTS,100);
	curl_setopt($ch,CURLOPT_CLOSEPOLICY,CURLCLOSEPOLICY_LEAST_RECENTLY_USED);
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_MAXREDIRS,10);
	curl_setopt($ch,CURLOPT_HEADER,0);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_TIMEOUT,30);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,10);
	curl_setopt($ch,CURLOPT_HTTPGET,1);
	$data = curl_exec($ch);
	curl_close($ch);
	
	return $data;
}

$API = new API;

$format = $API->getFormat();
$API->setHeaders();

if( !isset( $_GET['name'] ) ) {
	toDie( "No username given", "missingusername" );
}
if( !isset( $_GET['lang'] ) ) {
	toDie( "No language given", "missinglanguage" );
}
if( !isset( $_GET['wiki'] ) ) {
	toDie( "No wiki given", "missingwiki" );
}

$oldname = ucfirst( ltrim( rtrim( str_replace( array('&#39;','%20'), array('\'',' '), $_GET['name'] ) ) ) );
$oldname = urldecode($oldname);
$oldname = str_replace('_', ' ', $oldname);
$oldname = str_replace('/', '', $oldname);
$oldwiki = $_GET['wiki'];
$oldlang = $_GET['lang'];
$name = mysql_escape_string( $oldname );
$lang = mysql_escape_string( $oldlang );
$wiki = mysql_escape_string( $oldwiki );
$lang = str_replace('/', '', $lang);
$wiki = str_replace('/', '', $wiki);
$oldlang = str_replace('/', '', $oldlang);
$oldwiki = str_replace('/', '', $oldwiki);

if( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $oldname ) ) {
	define( 'ISIPADDRESS', true );
}
else {
	define( 'ISIPADDRESS', false );
}

//Connect to database (kudos to SQL for the code)
require_once( '/data/project/xtools/database.inc' );

if( $oldwiki.$oldlang != 'wikipediaen' ) {
	$wiki = str_replace( 'wikipedia', 'wiki', $wiki );
	//Support for non-standerd database names
	if ( $lang == 'www' && $wiki == 'mediawiki' ) {
		$lang = 'mediawiki';
		$wiki = 'wiki';
	}
	elseif ( $lang == 'meta' && $wiki == 'wikimedia' ) {
		$lang = 'meta';
		$wiki = 'wiki';
	}
	elseif ( $lang == 'commons' && $wiki == 'wikimedia' ) {
		$lang = 'commons';
		$wiki = 'wiki';
	}
	elseif ( $lang == 'www' && $wiki == 'wikimediafoundation' ) {
		$lang = 'foundation';
		$wiki = 'wiki';
	}
	elseif ( $lang == 'incubator' && $wiki == 'wikimedia' ) {
		$lang = 'incubator';
		$wiki = 'wiki';
	}
	else {
	}
	$dbh = $lang . $wiki . ".labsdb";
	$dbni = $lang . $wiki . "_p";
	mysql_connect( "$dbh",$toolserver_username,$toolserver_password );
	@mysql_select_db( $dbni ) or toDie( mysql_error(), 'mysqlerror' );
	unset($dbh, $dbni, $toolserver_username, $toolserver_password);
}
else {
	mysql_connect( "enwiki.labsdb",$toolserver_username,$toolserver_password );
	@mysql_select_db( "enwiki_p" ) or toDie( mysql_error(), 'mysqlerror' );
}
//Done

function getReplag() {
	$query = "SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(rc_timestamp) as replag FROM recentchanges_userindex ORDER BY rc_timestamp DESC LIMIT 1";
		$result = mysql_query( $query );
		if( !$result ) toDie( mysql_error(), 'mysqlerror' );
		$row = mysql_fetch_assoc( $result );
		$replag = $row['replag'];
		
		$seconds = floor($replag);
		$text = formatReplag($seconds);
		
		return $text;
}

function formatReplag($secs) {
	$units = array(
		"w" => 7 * 24 * 60 * 60,
		"d" =>	   24 * 60 * 60,
		"h" =>		    60 * 60,
		"m" =>			     60,
		"s" =>				  1,
	);
	
	$r = array('w' => 0, 'd' => 0, 'h' => 0, 'm' => 0, 's' => 0, '*' => $secs);
	if( $secs == 0 ) return $r;
	
	foreach ( $units as $name => $divisor ) {
		if ( $quot = intval($secs / $divisor) ) {
			$r[$name] = $quot;
			$secs -= $quot * $divisor;
		}
	}
	
	return $r;
}


function getEditCounts() {
	global $name, $namespaces, $http, $oldwiki, $oldlang, $oldname;
	
	//Get total edits
	if( ISIPADDRESS == false ) {//IP addresses don't have a field in the user table, so IPs must be done the old way.
		$query = "SELECT user_editcount, user_id FROM user WHERE user_name = '".$name."';";
		$result = mysql_query( $query );
		if( !$result ) toDie( mysql_error(), 'mysqlerror' );
		$row = mysql_fetch_assoc( $result );
		$uid = $row['user_id'];
		if( $uid == 0 ) {
			toDie( "User does not exist", "nosuchuser" );
		}
	}
	unset( $row, $query, $result );
	
	$query = 'SELECT COUNT(*) AS count FROM archive_userindex WHERE ar_user_text = \''.$name.'\'';
	$result = mysql_query( $query );
	$row = mysql_fetch_assoc( $result );
	$edit_count_deleted = $row['count'];
	unset( $row, $query, $result );
	
	$query = 'SELECT COUNT(*) AS count FROM revision_userindex WHERE rev_user_text = \''.$name.'\'';
	$result = mysql_query( $query );
	$row = mysql_fetch_assoc( $result );
	$edit_count_live = $row['count'];
	unset( $row, $query, $result );

	if( ISIPADDRESS == false ) {//IPs don't have user groups!
		/*$groups = getUrl( '//'.$oldlang.'.'.$oldwiki.'.org/w/api.php?action=query&list=users&ususers='.urlencode( $oldname ).'&usprop=groups&format=php', false );
		$groups = unserialize( $groups );
		$groups = $groups['query']['users']['0']['groups'];*/
		
		$query = "select * from user_groups where ug_user = '".$uid."';";
		$result = mysql_query( $query );
		if( !$result ) toDie( mysql_error(), 'mysqlerror' );
		$groups = array();
		while( $row = mysql_fetch_assoc( $result ) ) {
			$groups[] = $row['ug_group'];
		}
	}

	$edit_count_total = $edit_count_live + $edit_count_deleted;

	return array(
		$edit_count_total, //$temp[0];
		$edit_count_live, //$temp[1];
		$edit_count_deleted, //$temp[2];
		$groups, //$temp[3];
		$uid, //$temp[4];
	);

}

$temp = getEditCounts();
$replag = getReplag();
$total = $temp[0];
$live = $temp[1];
$deleted = $temp[2];
$groups = $temp[3];
$groups['_element'] = 'g';
$uid = $temp[4];

$final_array = array(
	'query' => array(
		'count' => array(
			'replag' => $replag,
			'groups' => $groups,
			'counts' => array(
				'live' => $live,
				'deleted' => $deleted,
				'total' => $total
			)
		)
	)
);

//var_dump( $final_array);

$API->showArray( $final_array );

function toDie( $msg, $code = 'generror' ) {
	global $API;
	$array = array( 'error' => array( 'code' => $code, 'info' => $msg ) );
	$API->showArray( $array );
	die();
} 

