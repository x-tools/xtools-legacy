<?php
$time = microtime( 1 );//Calculate time in microseconds to calculate time taken to execute


include( '/data/project/xtools/public_html/common/header.php' );
include( '/data/project/xtools/wikibot.classes.php' );
include( '/data/project/xtools/stats.php' );
$tool = 'EditSummary';
$surl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
if (isset($_GET['name'])) {
   addStat( $tool, $surl, $_SERVER['HTTP_REFERER'], $_SERVER['HTTP_USER_AGENT'] );//Stat checking
}
unset($tool, $surl);

//Debugging stuff
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
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
   <h2 class="table">Edit Summary calculator</h2>';
   
   
//If there is a failure, do it pretty.
function toDie( $msg ) {
   echo $msg;
   include( '/data/project/xtools/public_html/common/footer.php' );
   die();
}


//Tell footer.php to output source
function outputSource( $msg ) {
   echo "<li>
   <!--<a href=\"//svn.cluenet.org/viewvc/tparis/trunk/bots/Tools/editsummary/index.php?view=markup\">-->View source<!--</a>-->
   </li>";
}


//Get array of namespaces
function getNamespaces() {
   global $http, $name, $lang, $wiki, $oldlang, $oldwiki;

   $namespaces = $http->get( 'https://'.$oldlang.'.'.$oldwiki.'.org/w/api.php?action=query&meta=siteinfo&siprop=namespaces&format=php', false );
   $namespaces = unserialize( $namespaces );
   $namespaces = $namespaces['query']['namespaces'];
   if( !$namespaces[0] ) { toDie( 'Not a valid wiki.' ); };


   unset( $namespaces[-2] );
   unset( $namespaces[-1] );

   $namespaces[0]['*'] = 'Article';

   return $namespaces;
}


if( !isset( $_GET['name'] ) ) {
   toDie( 'Welcome to Cyberpower678\'s summary calculator!<br /><br />
      <form action="index.php" method="get">
      Username: <input type="text" name="name" /><br />
      Wiki: <input type="text" value="en" name="lang" size="9" />.      <input type="text" value="wikipedia" size="10" name="wiki" />.    org<br />
      <input type="submit" />
      </form>' );
}

$http = new http;
$name = mysql_escape_string( ucfirst( ltrim( rtrim( $_GET['name'] ) ) ) );
$oldwiki = $_GET['wiki'];
$oldlang = $_GET['lang'];
$lang = mysql_escape_string( $oldlang );
$wiki = mysql_escape_string( $oldwiki );
$namespaces = getNamespaces();


//Check if the user is an IP address
if( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $name ) ) {
   define( 'ISIPADDRESS', true );
}
else {
   define( 'ISIPADDRESS', false );
}


//Connect to database (kudos to SQL for the code)
require_once( '/data/project/xtools/database.inc' );
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
@mysql_select_db( $dbni ) or print mysql_error();
unset($dbh, $dbni, $toolserver_username, $toolserver_password );
//Done



function getEditCounts() {
   global $name, $namespaces, $http, $oldwiki, $oldlang;

   //Get total edits
   if( ISIPADDRESS == false ) {//IP addresses don't have a field in the user table, so IPs must be done the old way.
      $query = "SELECT user_id FROM user WHERE user_name = '".$name."';";
      $result = mysql_query( $query );
      if( !$result ) toDie( "MySQL error, please report to Cyberpower678 using <a href=\"//en.wikipedia.org/wiki/User talk:Cyberpower678\">the bug reporter.</a> Be sure to report the following SQL error when reporting:<br /><pre>".mysql_error()."</pre>" );
      $row = mysql_fetch_assoc( $result );
      $uid = $row['user_id'];
   }
   unset( $row, $query, $result );
   

   if( ISIPADDRESS == false ) {//IPs don't have user groups!
      if( $uid == 0 ) {
         toDie( "User does not exist." );
      }
   }


   $edit_sum_maj = 0;
   $edit_sum_min = 0;
   $maj = 0;
   $minn = 0;
   $rmaj = 0;
   $rmin = 0;
   $redit_sum_maj = 0;
   $redit_sum_min = 0;
   $month_totals = array();
   $month_editsummary_totals = array();
   
   $query = "SELECT rev_comment,rev_timestamp,rev_minor_edit FROM revision_userindex JOIN page ON page_id = rev_page WHERE rev_user_text = '$name' AND page_namespace = 0 ORDER BY rev_timestamp DESC";
   $result = mysql_query($query);
   if(!$result) toDie( "MySQL error, please report to Cyberpower678 using <a href=\"//en.wikipedia.org/wiki/User talk:Cyberpower678\">the bug reporter.</a> Be sure to report the following SQL error when reporting:<br /><pre>".mysql_error()."</pre>" );

   while ($row = mysql_fetch_assoc($result)) {
      preg_match('/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/', $row['rev_timestamp'], $d);
      list($arr,$year,$month,$day,$hour,$min,$sec) = $d;
      //print_r($d);
      
      $monthkey = $year."/".$month;
      $first_month = strtotime("$year-$month-$day");
      $month_totals[$monthkey]++;
      if ($row['rev_minor_edit'] == 0) {
         if ($row['rev_comment'] !== '') {
            $month_editsummary_totals[$monthkey]++;
            $edit_sum_maj++;
         }

         $maj++;
         if ($rmaj <= 149) {
            $rmaj++;
            if ($row['rev_comment'] != '') {
               $redit_sum_maj++;
            }
         }
      }
      else {
         if ($row['rev_comment'] !== '') {
            $month_editsummary_totals[$monthkey]++;
            $edit_sum_min++;
            $minn++;
         }
else {
   $minn++;
}
         //$min++;
         if ($rmin <= 149) {
            $rmin++;
            if ($row['rev_comment'] != '') {
               $redit_sum_min++;
            }
         }
      }
   }
   
   /*$query = "SELECT rev_comment,rev_timestamp FROM revision JOIN page ON page_id = rev_page WHERE rev_user_text = '$name' AND page_namespace = 0 AND rev_minor_edit = 1 ORDER BY rev_timestamp DESC";
   $result = mysql_query($query);
   if(!$result) toDie( "MySQL error, please report to Cyberpower678 using <a href=\"//en.wikipedia.org/wiki/User talk:Cyberpower678\">the bug reporter.</a> Be sure to report the following SQL error when reporting:<br /><pre>".mysql_error()."</pre>" );

   while ($row = mysql_fetch_assoc($result)) {
      preg_match('/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/', $row['rev_timestamp'], $d);
      list($arr,$year,$month,$day,$hour,$min,$sec) = $d;
      //print_r($d);
      
      $monthkey = $year."/".$month;
      if ($first_month > strtotime("$year-$month-$day") ) {
         $first_month = strtotime("$year-$month-$day");
      }
      $month_totals[$monthkey]++;
      if ($row['rev_comment'] !== '') {
         $month_editsummary_totals[$monthkey]++;
         $edit_sum_min++;
      }
      
      $min++;
      if ($rmin <= 149) {
         $rmin++;
         if ($row['rev_comment'] != '') {
            $redit_sum_min++;
         }
      }
   }*/
   
   $last_month = strtotime( date( 'Ymd' ) );
   
   return array($edit_sum_maj, $edit_sum_min, $maj, $minn, $redit_sum_maj, $redit_sum_min, $rmaj, $rmin, $month_totals, $month_editsummary_totals, $first_month, $last_month);
}


$temp = getEditCounts();
$edit_sum_maj = $temp[0];
$edit_sum_min = $temp[1];
$maj = $temp[2];
$min = $temp[3];
$redit_sum_maj = $temp[4];
$redit_sum_min = $temp[5];
$rmaj = $temp[6];
$rmin = $temp[7];
$month_totals = $temp[8];
$month_editsummary_totals = $temp[9];
$first_month = $temp[10];
$last_month = $temp[11];

unset( $temp );//Just neatness


//Output general stats
echo '<h2>General user info</h2>';

echo "Username: <a href=\"//$oldlang.$oldwiki.org/wiki/User:$name\">$name</a><br />";
echo "Edit summary for all major edits: ". (sprintf( '%.2f', $edit_sum_maj ? $edit_sum_maj / $maj : 0 ) * 100). "%<br />";
echo "Edit summary for all minor edits: ". (sprintf( '%.2f', $edit_sum_min ? $edit_sum_min / $min : 0 ) * 100). "%<br />";
echo "Edit summary for last $rmaj major edits: ". (sprintf( '%.2f', $redit_sum_maj ? $redit_sum_maj / $rmaj : 0 ) * 100). "%<br />";
echo "Edit summary for last $rmin minor edits: ". (sprintf( '%.2f', $redit_sum_min ? $redit_sum_min / $rmin : 0 ) * 100). "%<br />";

$months = array();
   for ($date=$first_month; $date<=$last_month; $date+=10*24*60*60) {
      $monthkey = date('Y/m', $date);
      if ($monthkey != $last_monthkey) {
         array_push($months, $monthkey);
         $last_monthkey = $monthkey;
      }
   }
   $monthkey = date('Y/m', $last_month);
   if ($monthkey != $last_monthkey) {
      array_push($months, $monthkey);
      $last_monthkey = $monthkey;
   }

echo "<small>Note: This is only a representation of the Main namespace.</small>";
echo "<table class=months>\n";
   $max_width = max($month_editsummary_totals);
   foreach ($months as $key) {
      $total = $month_editsummary_totals[$key];
      $no_summary = bcsub($month_totals[$key],$total,0);
      if (!$month_totals[$key]) {
         $month_totals[$key] = 0;
      }
      print "<tr><td class=date>$key</td><td>".$month_totals[$key]."</td>";
      print "<td><div class=green style='width:".bcdiv(bcmul(500, $total, 0), $max_width, 0)."px'></div>\n";
      print "<div class=red style='width:".bcdiv(bcmul(500, $no_summary, 0), $max_width, 0)."px'></div>\n";
   }

   echo "</table>";



//Calculate time taken to execute
$exectime = microtime( 1 ) - $time;
echo "<br /><hr><span style=\"font-size:100%;\">Executed in $exectime seconds.</span>";
echo "<br />Taken ".(memory_get_usage() / (1024 * 1024))." megabytes of memory to execute.";

//Output footer
include( '/data/project/xtools/public_html/common/footer.php' );

?>
