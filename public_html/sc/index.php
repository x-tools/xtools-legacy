<?php

$time = microtime( 1 );//Calculate time in microseconds to calculate time taken to execute

define( 'NOSERVER', 'false' );

//error_reporting(E_ALL);
ini_set("display_errors", 1);

include( '/data/project/xtools/public_html/common/header.php' );
include( '/data/project/xtools/stats.php' );
require( '/data/project/xtools/public_html/sc/i18n.php' );
$tool = 'SimpleEditCounter';
$surl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
if (isset($_GET['name'])) {
	addStat( $tool, $surl, $_SERVER['HTTP_REFERER'], $_SERVER['HTTP_USER_AGENT'] );//Stat checking
}
unset($tool, $surl);

//Output header
echo '<div id="content">
	<table class="cont_table" style="width:100%;">
	<tr>
	<td class="cont_td" style="width:75%;">
	<h2 class="table">Quick, Dirty, Simple Edit Counter</h2>';
flush();

//Debugging stuff
function pre( $array ) {
	echo "<pre>";
	print_r( $array );
	echo "</pre>";
}
	
//Access to the wiki
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
	
//If there is a failure, do it pretty.
function toDie( $msg ) {
	echo $msg;
	include( '/data/project/xtools/public_html/common/footer.php' );
	die();
}


//Tell footer.php to output source
function outputSource() {
	echo "<li>
	<a href=\"//svn.cluenet.org/viewvc/soxred93/trunk/bots/Tools/count/index.php?view=markup\">".wfMsg('viewsource')."</a>
	</li>";
}

if( NOSERVER == 'true' ) {
	toDie( wfMsg( 'nosql' ) );
}


if( !isset( $_GET['name'] ) ) {
	$msg = wfMsg('welcome').'<br /><br />
		<form action="//tools.wmflabs.org/xtools/sc/index.php" method="get">
		'.wfMsg('username').': <input type="text" name="name" /><br />
		'.wfMsg('wiki').': <input type="text" value="';
		if (isset($_GET['uselang'])) {
			$msg .= $_GET['uselang'];
		}
		else {
			$msg .= 'en';
		}
		$msg .= '" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org<br />
		<input type="submit" value="'.wfMsg('submit').'" />
		</form><br /><hr />';
		
	$msg .= wfMsg('otherlang');
	$msg .= "<ul>";
	foreach( $messages as $lang => $msgs ) {
		$msg .= "<li><a href=\"//tools.wmflabs.org/xtools/sc/index.php?uselang=$lang\">".$canonical[$lang]." ";
		if( count( $messages[$lang] ) < count( $messages['en'] ) ) {
			$msg .= wfMsg('incomplete', count( $messages['en'] ) - count( $messages[$lang] ) );
		}
		$msg .= "</a></li>\n";
		//foreach( $messages['en'] as $code => $msg ) {
		//	if ( !isset($messages[$lang][$code]) ) {
		//		echo $code;
		//	}
		//}
	}
	$msg .= "</ul>";
	$msg .= "<br />".wfMsg('helptrans' );
	toDie( $msg );
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

if( isset( $_GET['debug'] ) ) {
	echo "<span style=\"font-size:100%;\">".wfMsg('executed', number_format(microtime( 1 ) - $time, 2, '.', ''))."</span>";
}

//Check if the user is an IP address
if( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $oldname ) ) {
	define( 'ISIPADDRESS', true );
}
else {
	define( 'ISIPADDRESS', false );
}

function PLURAL( $num, $str ) {
	if( $num == 1 ) {
		$str = str_replace('$1', '', $str);
	}
	else {
		$str = str_replace('$1', 's', $str);
	}
	return $str;
}


//Format numbers based on language
function numberformat( $num ) {
	global $lang;
	if ( $lang == 'www' || $lang == 'meta' || $lang == 'commons' || $lang == 'en' || $lang == 'incubator' ) {
		return number_format($num);
	}
	elseif ( $lang == 'fr' ) {
		return number_format($num, 0, ',', ' ');
	}
	else {
		return number_format($num);
	}
}

//Connect to database (kudos to SQL for the code)
require_once( '/data/project/xtools/database.inc' );

if( $oldwiki.$oldlang != 'wikipediaen' ) {
	
	$dbh = $lang . $wiki . ".labsdb";
	$dbni = $lang . $wiki . "_p";
	mysql_connect( "$dbh",$toolserver_username,$toolserver_password );
	@mysql_select_db( $dbni ) or print mysql_error();
	unset($dbh, $dbni, $toolserver_username, $toolserver_password);
}
else {
	//toDie( "In order to both discourage editcountitis and give the English Wikipedia editors a chance to realize what editing is all about and why I created this tool in the first place, I have disabled my edit counters (pcount, simplecount, autoedits) until August 17 2010. Please use this time to reflect on why I made this tool in the first place: To serve curiosity, not to create false judgement descisions about editors. -X! 13 August 2010" );
	mysql_connect( "enwiki.labsdb",$toolserver_username,$toolserver_password );
	@mysql_select_db( "enwiki_p" ) or print mysql_error();
}

//Done

if( isset( $_GET['debug'] ) ) {
	echo "<span style=\"font-size:100%;\">".wfMsg('executed', number_format(microtime( 1 ) - $time, 2, '.', ''))."</span>";
}


function getReplag() {
	$query = "SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(rc_timestamp) as replag FROM recentchanges_userindex ORDER BY rc_timestamp DESC LIMIT 1";
		$result = mysql_query( $query );
		if( !$result ) toDie( wfMsg('mysqlerror', mysql_error() ) );
		$row = mysql_fetch_assoc( $result );
		$replag = $row['replag'];
		
		$seconds = floor($replag);
		$text = formatReplag($seconds);
	    
	    return array($seconds,$text);
}

function formatReplag($secs) {
	$second = 1;
	$minute = $second * 60;
	$hour = $minute * 60;
	$day = $hour * 24;
	$week = $day * 7;
	
	$r = '';
	if ($secs > $week) {
		$r .= floor($secs/$week) . wfMsg( 'w' );
		$secs %= $week;
	}
	if ($secs > $day) {
		$r .= floor($secs/$day) . wfMsg( 'd' );
		$secs %= $day;
	}
	if ($secs > $hour) {
		$r .= floor($secs/$hour) . wfMsg( 'h' );
		$secs %= $hour;
	}
	if ($secs > $minute) {
		$r .= floor($secs/$minute) . wfMsg( 'm' );
		$secs %= $week;
	}
	if ($secs > $second) {
		$r .= floor(($secs/$second)/100) . wfMsg( 's' );
	}
	
	return $r;
}

function getEditCounts() {
	global $name, $namespaces, $http, $oldwiki, $oldlang, $oldname, $time;

	if( isset( $_GET['debug'] ) ) {
		echo "<span style=\"font-size:100%;\">A".wfMsg('executed', number_format(microtime( 1 ) - $time, 2, '.', ''))."</span>";
		flush();
	}
	
	//Get total edits
	if( ISIPADDRESS == false ) {//IP addresses don't have a field in the user table, so IPs must be done the old way.
		$query = "SELECT user_editcount, user_id FROM user WHERE user_name = '".$name."';";
		$result = mysql_query( $query );
		if( !$result ) toDie( wfMsg('mysqlerror', mysql_error() ) );
		$row = mysql_fetch_assoc( $result );
		$uid = $row['user_id'];
		if( $uid == 0 ) {
			toDie( wfMsg('nosuchuser', $name ) );
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
	
	if( isset( $_GET['debug'] ) ) {
		echo "<span style=\"font-size:100%;\">A".wfMsg('executed', number_format(microtime( 1 ) - $time, 2, '.', ''))."</span>";
	}

	if( ISIPADDRESS == false ) {//IPs don't have user groups!
		/*$groups = getUrl( '//'.$oldlang.'.'.$oldwiki.'.org/w/api.php?action=query&list=users&ususers='.urlencode( $oldname ).'&usprop=groups&format=php', false );
		$groups = unserialize( $groups );
		$groups = $groups['query']['users']['0']['groups'];*/
		
		$query = "select * from user_groups where ug_user = '".$uid."';";
		$result = mysql_query( $query );
		if( !$result ) toDie( wfMsg('mysqlerror', mysql_error() ) );
		$groups = array();
		while( $row = mysql_fetch_assoc( $result ) ) {
			$groups[] = $row['ug_group'];
		}
	}
	
	if( isset( $_GET['debug'] ) ) {
		echo "<span style=\"font-size:100%;\">A".wfMsg('executed', number_format(microtime( 1 ) - $time, 2, '.', ''))."</span>";
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

if( isset( $_GET['debug'] ) ) {
	echo "<span style=\"font-size:100%;\">".wfMsg('executed', number_format(microtime( 1 ) - $time, 2, '.', ''))."</span>";
}

if ($replag[0] > 120) {
	echo '<h2 class="replag">'.wfMsg('highreplag', $replag[1]).'</h2>';
}
unset( $replag );

//Output general stats
echo '<h2>'.wfMsg('generalinfo').'</h2>';

echo wfMsg('username').": <a href=\"https://$oldlang.$oldwiki.org/wiki/User:$oldname\">$oldname</a><br />";

flush();
$temp = getEditCounts();
$replag = getReplag();
$total = $temp[0];
$live = $temp[1];
$deleted = $temp[2];
$groups = $temp[3];
$uid = $temp[4];

unset( $temp );//Just neatness

if( isset( $groups[0] ) ) {//Because IPs and other new users don't have groups
	echo wfMsg('usergroups').": ";
	echo implode( ', ', $groups );//Why is this in 3 lines?
	echo "<br />";
}
unset( $groups );

if( $deleted > 0 ) {//Users who have edited before 2006 (when the editcount field was added) have negative deleted edits
	echo wfMsg('total').": ".numberformat($total)."<br />";
	echo wfMsg('deleted').": ".numberformat($deleted)."<br />";
}

echo "<b>".wfMsg('live').": ".numberformat($live)."</b><br />";//The end all answer!!!

if( $live == 0 ) {
	toDie( "<br /><br />User does not have any contribs.\n");
}

if( isset( $_GET['debug'] ) ) {
	echo "<span style=\"font-size:100%;\">".wfMsg('executed', number_format(microtime( 1 ) - $time, 2, '.', ''))."</span>";
}

//Calculate time taken to execute
$exectime = number_format(microtime( 1 ) - $time, 2, '.', '');
echo "<br /><hr><span style=\"font-size:100%;\">".wfMsg('executed', $exectime)."</span>";
echo "<br />".wfMsg('memory', number_format((memory_get_usage() / (1024 * 1024)), 2, '.', ''));

//Output footer
include( '/data/project/xtools/public_html/common/footer.php' );

?>
