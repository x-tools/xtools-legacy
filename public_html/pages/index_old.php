<?php

//error_reporting(E_ALL);
ini_set("display_errors", 1);

$time = microtime( 1 );//Calculate time in microseconds to calculate time taken to execute

include( '/data/project/xtools/public_html/common/header.php' );
include( '/data/project/xtools/stats.php' );
include( '/data/project/xtools/public_html/pages/i18n.php');
require_once( '/data/project/xtools/database.inc' );
global $tools2;

$tool = 'PagesCreated';
$surl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
if( isset($_SERVER['HTTP_REFERER'])) $refer = $_SERVER['HTTP_REFERER'];
else $refer = "none";
if (isset($_GET['name'])) {
 addStat( $tool, $surl, $refer, $_SERVER['HTTP_USER_AGENT'] );//Stat checking
}
unset($tool, $surl);


//Debugging stuff
function pre( $array ) {
   echo "<pre>";
   print_r( $array );
   echo "</pre>";
}

//Output header
echo '<div id="content">
   <table class="cont_table" style="width:100%;">
   <tr>
   <td class="cont_td" style="width:75%;">
   <h2 class="table">Top Namespace Edits</h2>';
   
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
    curl_setopt($ch,CURLOPT_USERAGENT,'Here is your stupid user agent. I am still adding it to tools a month after the change was made.');
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

//Get array of namespaces
function getNamespaces() {
   global $oldlang, $oldwiki;
   $namespaces = getUrl( "//$oldlang.$oldwiki.org/w/api.php?action=query&meta=siteinfo&siprop=namespaces&format=php" );
   $namespaces = unserialize( $namespaces );
   $namespaces = $namespaces['query']['namespaces'];
   
   unset( $namespaces[-2] );
   unset( $namespaces[-1] );

   $namespaces[0]['*'] = "Mainspace";
   
   $namespacenames = array();
   foreach ($namespaces as $value => $ns) {
      $namespacenames[$value] = $ns['*'];
   }

   return $namespacenames;
}

if( !isset( $_GET['name'] ) ) {
   $msg = 'Welcome to X!\'s create pages tool!<br /><br />
      <form action="//tools.wmflabs.org/xtools/pages/index.php" method="get">
      Username: <input type="text" name="name" /><br />
      Wiki: <input type="text" value="';
      if (isset($_GET['uselang'])) {
         $msg .= $_GET['uselang'];
      }
      else {
         $msg .= 'en';
      }
   $msg .= '" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org<br />
      Namespace: <select name="namespace">
      <option value="0">Main</option>
      <option value="1">Talk</option>
      <option value="2">User</option>
      <option value="3">User talk</option>
      <option value="4">Wikipedia</option>
      <option value="5">Wikipedia talk</option>
      <option value="6">File</option>
      <option value="7">File talk</option>
      <option value="8">MediaWiki</option>
      <option value="9">MediaWiki talk</option>
      <option value="10">Template</option>
      <option value="11">Template talk</option>
      <option value="12">Help</option>
      <option value="13">Help talk</option>
      <option value="14">Category</option>
      <option value="15">Category talk</option>
      <option value="100">Portal</option>
      <option value="101">Portal talk</option>
      <option value="108">Book</option>
      <option value="109">Book talk</option>
      </select><br />
      Redirects: <select name="redirects">
      <option value="none">Include redirects and non-redirects</option>
      <option value="onlyredirects">Only include redirects</option>
      <option value="noredirects">Exclude redirects</option>
      </select><br />
      <input type="submit" value="Submit" />
      </form><br /><hr />';
      
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
$namespace = mysql_escape_string( $_GET['namespace'] );

//Connect to database (kudos to SQL for the code)
require_once( '/data/project/xtools/database.inc' );

if( $oldwiki.$oldlang == "" ) { $oldwiki = $wiki = "wikipedia"; $oldlang = $land = "en"; }
if( $oldwiki.$oldlang != 'wikipediaen' ) {
   $wiki = str_replace( 'wikipedia', 'wiki', $wiki );
   //Support for non-standerd database names
   if ( $lang == 'www' && $wiki == 'mediawiki' ) {
      $lang = 'mediawiki';
      $wiki = 'wiki';
   }
   elseif ( $lang == '' && $wiki == 'mediawiki' ) {
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
   $dbh = $lang . $wiki . "-p";
   $dbni = $lang . $wiki . "_p";
   /*mysql_connect( 'sql',$toolserver_username,$toolserver_password );
   @mysql_select_db( 'toolserver' ) or toDie( wfMsg('mysqlerror', mysql_error() ) );
   $query = "select * from wiki where dbname = '$dbni';";
   $result = mysql_query( $query );
   $row = mysql_fetch_assoc( $result );*/
    if( $wiki == 'wikipedia' || $wiki == 'wikimedia' ) $wiki = "wiki";
            $row['server'] = $lang.$wiki.".labsdb";
            $row['dbname'] = $lang.$wiki."_p";
        
        if ($wiki == 'wikidata') {
            $row['dbname'] = 'wikidatawiki_p';
            $row['server'] = 'wikidatawiki.labsdb';
        }
   $dbnm = $row['dbname'];
   $dbhs = $row['server'];
   $dbn = $dbnm;
   $dbh = $dbhs;
   //mysql_close($tools2);
   mysql_connect( "$dbh",$toolserver_username,$toolserver_password );
   @mysql_select_db( $dbn ) or print mysql_error();
   unset($dbh, $dbni, $toolserver_username, $toolserver_password, $query, $result, $row, $dbnm, $dbhs, $dbn, $dbh, $tools2);
}
else {
   mysql_connect( "enwiki.labsdb",$toolserver_username,$toolserver_password );
   @mysql_select_db( "enwiki_p" ) or print mysql_error();
}

function getReplag() {
   global $mysql;
   $query = "SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(rc_timestamp) as replag FROM recentchanges ORDER BY rc_timestamp DESC LIMIT 1";
      $result = mysql_query( $query );
      if( !$result ) toDie( "MySQLj ERROR! ". mysql_error( $mysql ) );
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
global $mysql;
$query = "SELECT user_editcount, user_id FROM user WHERE user_name = '".$name."';";
$result = mysql_query( $query );
if( !$result ) toDie( "MySQL ERROR! ". mysql_error() );
$row = mysql_fetch_assoc( $result );
$edit_count_total = $row['user_editcount'];
$uid = $row['user_id'];
unset( $row, $query, $result );

if( $uid == 0 ) {
   toDie( wfMsg('nosuchuser', $name ) );
}

if( $_GET['redirects'] == "onlyredirects" ) {
   $redirectstatus = "AND page_is_redirect = '1'";
}
elseif( $_GET['redirects'] == "noredirects" ) {
   $redirectstatus = "AND page_is_redirect = '0'";
}
else {
   $redirectstatus = null;
}

$query = "/* SLOW_OK *//* CREATED */ SELECT distinct 
page_title,page_is_redirect,page_id FROM page JOIN revision_userindex AS r on 
page_id = r.rev_page WHERE r.rev_user_text = '$name' AND page_namespace = '$namespace' $redirectstatus ORDER BY rev_timestamp DESC;";
$result = mysql_query($query);

$nsnames = getNamespaces();

if(!$result) Die("ERROR: No result returned.");
echo "<h2>Pages created by $oldname:</h2>\n<ol>\n";
$i = 1;
while ($row = mysql_fetch_assoc($result, $mysql)) {
    $pagename = $row['page_title'];
    $first = mysql_query("SELECT rev_user_text, rev_timestamp FROM revision_userindex where rev_page = '".$row['page_id']."' order by rev_id ASC limit 1;");
    $first = mysql_fetch_assoc($first);
    if($first['rev_user_text'] != $oldname) { continue; }
    if( $nsnames[$namespace] != "Mainspace" ) {
      echo "<li><a href=\"//$oldlang.$oldwiki.org/wiki/".$nsnames[$namespace].":$pagename\">$pagename</a>";
    }
    else {
      echo "<li><a href=\"//$oldlang.$oldwiki.org/wiki/$pagename\">$pagename</a>";
    }
    if( $row['page_is_redirect'] == 1 ) {
      echo " (redirect)";
    }
    echo "</li>\n";
    $i++;
    if( $i > 100 && !isset( $_GET['getall'] ) ) { break; }
}

if( $i > 100 && !isset( $_GET['getall'] ) ) { 
   echo "<br /><br /><b>Trunctuated to 100 pages</b>"; 
   echo "<br /><i>To see all results, please go to <a href=\"//tools.wmflabs.org".$_SERVER['REQUEST_URI']."&getall=1\">this link</a>";
}
echo "</ol>\n";

//Calculate time taken to execute

$exectime = number_format(microtime( 1 ) - $time, 2, '.', '');
echo "<br /><hr><span style=\"font-size:100%;\">Excecuted in ". $exectime ." seconds.</span>";
echo "<br />Taken ".number_format((memory_get_usage() / (1024 * 1024)), 2, '.', '')." megabytes of memory to execute.";

//Output footer
include( '/data/project/xtools/public_html/common/footer.php' );

?>
