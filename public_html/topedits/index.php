<?php

//error_reporting(E_ALL);
ini_set("display_errors", 1);

$time = microtime( 1 );//Calculate time in microseconds to calculate time taken to execute

include( '/data/project/xtools/public_html/common/header.php' );
include( '/data/project/xtools/stats.php' );
require_once( '/data/project/xtools/database.inc' );

$tool = 'TopEdits';
$surl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
if (isset($_GET['name'])) {
   addStat( $tool, $surl, $_SERVER['HTTP_REFERER'], $_SERVER['HTTP_USER_AGENT'] );//Stat checking
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
   $namespaces = getUrl( 'https://en.wikipedia.org/w/api.php?action=query&meta=siteinfo&siprop=namespaces&format=php' );
   $namespaces = unserialize( $namespaces );
   $namespaces = $namespaces['query']['namespaces'];


   unset( $namespaces[-2] );
   unset( $namespaces[-1] );

   $namespaces[0]['*'] = "Mainspace";

   $namespacenames = array();
   foreach ($namespaces as $value => $ns) {
      $namespacenames[$value] = $ns['*'];
   }
   return array($namespacenames);
}

//Check if the user is an IP address
if( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $oldname ) ) {
   define( 'ISIPADDRESS', true );
}
else {
   define( 'ISIPADDRESS', false );
}

if( !isset( $_GET['name'] ) ) {
   $msg = 'Welcome to X!\'s namespace counter tool!<br /><br />
      <form action="//tools.wmflabs.org/xtools/topedits/index.php" method="get">
      Username: <input type="text" name="name" /><br />
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
      </select><br />
      <input type="submit" value="Submit" />
      </form><br /><hr />';

   toDie( $msg );
}

$oldname = ucfirst( ltrim( rtrim( str_replace( array('&#39;','%20'), array('\'',' '), $_GET['name'] ) ) ) );
$oldname = urldecode($oldname);
$oldname = str_replace('_', ' ', $oldname);
$oldname = str_replace('/', '', $oldname);
$name = mysql_escape_string( $oldname );
$namespace = mysql_escape_string( $_GET['namespace'] );
$nsnames = getNamespaces();

mysql_connect( 'enwiki.labsdb',$toolserver_username,$toolserver_password );
@mysql_select_db( 'enwiki_p' ) or toDie( "MySQL ERROR! ". mysql_error() );


function getReplag() {
   $query = "SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(rc_timestamp) as replag FROM recentchanges_userindex ORDER BY rc_timestamp DESC LIMIT 1";
      $result = mysql_query( $query );
      if( !$result ) toDie( "MySQL ERROR! ". mysql_error() );
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

if( ISIPADDRESS == false ) {//IP addresses don't have a field in the user table, so IPs must be done the old way.
      $query = "SELECT user_editcount, user_id FROM user WHERE user_name = '".$name."';";
      $result = mysql_query( $query );
      if( !$result ) toDie( "MySQL ERROR! ". mysql_error() );
      $row = mysql_fetch_assoc( $result );
      $edit_count_total = $row['user_editcount'];
      $uid = $row['user_id'];
   }
   unset( $row, $query, $result );


   if( ISIPADDRESS == false ) {//IPs don't have user groups!
      $groups = getUrl( 'https://'.$oldlang.'.'.$oldwiki.'.org/w/api.php?action=query&list=users&ususers='.urlencode( $oldname ).'&usprop=groups&format=php', false );
      $groups = unserialize( $groups );
      $groups = $groups['query']['users']['0']['groups'];
      if( $uid == 0 ) {
         toDie( wfMsg('nosuchuser', $name ) );
      }
   }


   if( ISIPADDRESS == false ) {//IPs don't have a user ID
      $query = "SELECT /* SLOW_OK */ rev_timestamp,page_title,page_namespace,rev_comment,page_is_redirect FROM revision_userindex JOIN page ON page_id = rev_page WHERE rev_user = '".$uid."' AND page_namespace = '".$namespace."' ORDER BY rev_timestamp ASC;";
   }
   else {
      $query = "SELECT /* SLOW_OK */ rev_timestamp,page_title,page_namespace,rev_comment,page_is_redirect FROM revision_userindex JOIN page ON page_id = rev_page WHERE rev_user_text = '".$name."' AND page_namespace = '".$namespace."' ORDER BY rev_timestamp ASC;";
   }

   $result = mysql_query( $query );
   if( !$result ) toDie( "MySQL ERROR! ". mysql_error() );
   unset($query);

$unique_articles = array();
$redirects = array();
while ( $row = mysql_fetch_assoc( $result ) ) {
   $unique_articles[$row['page_title']]++;
   //$redirects[$row['page_title']] = $row['page_is_redirect'];
}

asort($unique_articles);
$unique_articles = array_reverse($unique_articles);
array_splice($unique_articles, 100);

echo "<b>Top 100 edits in the ".$nsnames[0][$namespace]." namespace by $oldname.</b><br /><br />\n";
echo "<ul>";
foreach ( $unique_articles as $page => $arts ) {
   if( $namespace == 0 ) {
      $nscolon = '';
   }
   else {
      $nscolon = $nsnames[0][$namespace].":";
   }
   $trimmed = substr($page, 0, 50).'...';
   print '<li>'.$arts." - <a href=\"https://en.wikipedia.org/wiki/".$nscolon.str_replace(array('%2F','_'),array('/',' '),urlencode( $page )).'">';
   if(strlen(substr($page, 0, 50))<strlen($page)) {
      echo str_replace('_',' ',$trimmed);
   }
   else {
      echo str_replace('_',' ',$page);
   }
   /*if( $redirects[$page] == 1 ) {
      echo " (redirect)";
   }*/
   echo "</a></li>\n";
}
echo "</ul>";

//Calculate time taken to execute
$exectime = number_format(microtime( 1 ) - $time, 2, '.', '');
echo "<br /><hr><span style=\"font-size:100%;\">Excecuted in ". $exectime ." seconds.</span>";
echo "<br />Taken ".number_format((memory_get_usage() / (1024 * 1024)), 2, '.', '')." megabytes of memory to execute.";

//Output footer
include( '/data/project/xtools/public_html/common/footer.php' );

?>
