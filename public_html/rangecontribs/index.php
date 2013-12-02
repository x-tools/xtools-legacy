<?php
//die('This tool is currently broken. I'll do my best to get this back in a reasonable amoutn of time, but no promises');
$time = microtime( 1 );//Calculate time in microseconds to calculate time taken to execut

//error_reporting(E_ALL);
ini_set("display_errors", 1);

include( '/data/project/xtools/public_html/common/header.php' );
include( '/data/project/xtools/stats.php' );
include( '/data/project/xtools/wikibot.classes.php' );
$tool = 'CidrContribs';
$surl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
if (isset($_GET['name'])) {
	if( isset($_SERVER['HTTP_REFERER']) ) $refer = $_SERVER['HTTP_REFERER'];
    else $refer = "none";
    addStat( $tool, $surl, $refer, $_SERVER['HTTP_USER_AGENT'] );//Stat checking
}
unset($tool, $surl);

require_once( '/data/project/xtools/public_html/phptemp/PHPtemp.php' );
require_once( '/data/project/xtools/public_html/phptemp/Language.php' );
require_once( '/data/project/xtools/public_html/sitenotice.php' );

$phptemp = new PHPtemp( '/data/project/xtools/public_html/templates/main.tpl' );
$content = new PHPtemp( '/data/project/xtools/public_html/rangecontribs/templates/rangecontribs.tpl' );
$language = new Language( array( "en" ) );
$lang = $language->getLang();

$langlinks = $language->generateLangLinks();

$phptemp->load_config( '/data/project/xtools/public_html/configs/'.$lang.'.conf', 'main' );
$phptemp->load_config( '/data/project/xtools/public_html/rangecontribs/configs/'.$lang.'.conf', 'rangecontribs' );
$content->load_config( '/data/project/xtools/public_html/rangecontribs/configs/'.$lang.'.conf', 'rangecontribs' );

$namespaces = array(
   '0' => '',
   '1' => 'Talk:',
   '2' => 'User:',
   '3' => 'User talk:',
   '4' => 'Wikipedia:',
   '5' => 'Wikipedia talk:',
   '6' => 'File:',
   '7' => 'File talk:',
   '8' => 'MediaWiki:',
   '9' => 'MediaWiki talk:',
   '10' => 'Template:',
   '11' => 'Template talk:',
   '12' => 'Help:',
   '13' => 'Help talk:',
   '14' => 'Category:',
   '15' => 'Category talk:',
   '100' => 'Portal:',
   '101' => 'Portal talk:',
	 '108' => 'Book:',
	 '109' => 'Book talk:',
	 '446' => 'Education Program:',
	 '447' => 'Education Program talk:',
	 '710' => 'TimedText:',
	 '711' => 'TimedText talk:',
	 '828' => 'Module:',
	 '829' => 'Module talk:'
);

$phptemp->assign( "header", $phptemp->getConf('tool') );

if( !isset( $_GET['ips'] ) ) {
   $content->assign( "form", 'Welcome to X!\'s automated edits counter!<br /><br />
   There are two ways to use this tool. 
   <ol>
   <li>IP range: Enter a CIDR range into the box, in the format 0.0.0.0/0</li>
   <li>IP list: Enter a list of IPs into the box, separated by newlines.</li>
   </ol><br />
      <form action="//tools.wmflabs.org/xtools/rangecontribs/index.php" method="get">
      <table>
      <tr>
      <td align="center">IP range: <input type="radio" name="type" value="range" /></td> 
      <td align="center">IP list: <input type="radio" name="type" value="list" /></td> 
      </tr>
      <tr>
      <td colspan="2" align="center"><textarea name="ips" rows="10" cols="40"></textarea></td>
      </tr>
      <tr>
      <td align="center">Limit: <input type="text" name="limit" value="50" /></td> 
      </tr>
      <tr>
      <td colspan="2" align="center"><input type="submit" /></td>
      </tr>
      </table>
      </form><br />' );
   assignContent();
}

flush();

$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file("/data/project/xtools/replica.my.cnf");

$mysql = mysql_connect( 'enwiki.labsdb',$ts_mycnf['user'], $ts_mycnf['password'] );
@mysql_select_db( 'enwiki_p', $mysql ) or toDie( "MySQL error, please report to xlabs by using <a href=\"https://webchat.freenode.net/?channels=#xlabs\">IRC.</a> Be sure to report the following SQL error when reporting: \"".mysql_error()."\"" );

$cidr = $_GET['ips'];
$cidr = mysql_real_escape_string( $cidr, $mysql );
$cidr = str_replace('\r\n','\n',$cidr);
$limit = 50;
if( isset( $_GET['limit'] ) ) {
   $limit = $_GET['limit'];
}

if( !preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/', $cidr ) && $_GET['type'] == 'range' ) {
   toDie( "Not a valid CIDR range." );
}

$replag = getReplag();
if ($replag[0] > 120) {
   $content->assign( "replag", "Replag is high, change in the last {$replag[1]} will nto be shown." );
}
unset( $replag );

//Start the calculation

if( $_GET['type'] == 'range' ) {
   $cidr_info = calcCIDR( $cidr );
}
elseif( $_GET['type'] == 'list' ) {
   $cidr = explode( '\n', $cidr );
   $cidr_info = calcRange( $cidr );
}
else {
   toDie( 'Invalid type selected.' );
}
$ip_prefix = findMatch( $cidr_info['begin'], $cidr_info['end'] );

//echo "<b>CIDR:</b> {$cidr_info['begin']}/{$cidr_info['suffix']}<br />\n";
//echo "<b>Starting IP:</b> {$cidr_info['begin']}<br />\n";
//echo "<b>Ending IP:</b> {$cidr_info['end']}<br />\n";
//echo "<b>Number of possible IPs:</b> {$cidr_info['count']}<br />\n";

$content->assign( "showstats", "1" );
$content->assign( "cidr", "{$cidr_info['begin']}/{$cidr_info['suffix']}" );
$phptemp->assign( "page", "{$cidr_info['begin']}/{$cidr_info['suffix']}" );
$content->assign( "ip_start", $cidr_info['begin'] );
$content->assign( "ip_end", $cidr_info['end'] );
$content->assign( "ip_number", $cidr_info['count'] );
if (!isset($ip_prefix)) {
   $content->assign( "nocontribs", "1");
} else {
  $query = "SELECT page_title,rev_id,rev_user_text,rev_timestamp,rev_minor_edit,rev_comment,page_namespace FROM revision_userindex JOIN page ON page_id = rev_page WHERE rev_user_text LIKE '{$ip_prefix}%' AND rev_user = '0' ORDER BY rev_timestamp DESC;";
  $result = mysql_query( $query, $mysql );
  if( !$result ) toDie( mysql_error() );

  if( mysql_num_rows( $result ) == 0 ) {
     $content->assign( "nocontribs", "1" );
  }
  else {
     //echo "<ul>\n";
     $c = 0;
     $list = "";
     while( $row = mysql_fetch_assoc( $result ) ) {
        if( $c >= $limit ) { $continue = $row['rev_timestamp'];break; }
        if( isset( $_GET['continue'] ) && $_GET['continue'] < $row['rev_timestamp'] ) continue;
        $tmp1 = substr( addZero( decbin( ip2long( $row['rev_user_text'] ) ) ), 0, $cidr_info['suffix'] );
        $tmp2 = $cidr_info['shortened'];
        if( $tmp1 !== $tmp2 ) {
           continue;
        }
        $title = $namespaces[$row['page_namespace']].$row['page_title'];
        $urltitle = $namespaces[$row['page_namespace']].urlencode($row['page_title']);
        $timestamp = $row['rev_timestamp'];
        $year = substr($timestamp, 0, 4);
         $month = substr($timestamp, 4, 2);
         $day = substr($timestamp, 6, 2);
         $hour = substr($timestamp, 8, 2);
         $minute = substr($timestamp, 10, 2);
         $second = substr($timestamp, 12, 2);
         $date = date('M d, Y H:i:s', mktime($hour, $minute, $second, $month, $day, $year));

        $list .= "<li>\n";
        $list .= '(<a href="//en.wikipedia.org/w/index.php?title='.$urltitle.'&amp;diff=prev&amp;oldid='.urlencode($row['rev_id']).'" title="'.$title.'">diff</a>) ';

        $list .= '(<a href="//en.wikipedia.org/w/index.php?title='.$urltitle.'&amp;action=history" title="'.$title.'">hist</a>) . . ';

        if( $row['rev_minor_edit'] == '1' ) {
           $list .= '<span class="minor">m</span>  ';
        }

        $list .= '<a href="//en.wikipedia.org/wiki/'.$urltitle.'" title="'.$title.'">'.$title.'</a>‎; ';

        $list .= $date . ' . . ';

        $list .= '<a href="//en.wikipedia.org/wiki/User:'.$row['rev_user_text'].'" title="User:'.$row['rev_user_text'].'">'.$row['rev_user_text'].'</a> ';

        $list .= '(<a href="//en.wikipedia.org/wiki/User_talk:'.$row['rev_user_text'].'" title="User talk:'.$row['rev_user_text'].'">talk</a>) ';

        $list .= '('.$row['rev_comment'].')';

        $list .= "<hr />\n</li>\n";
        $c++;
     }
     //echo "</ul>\n";

     if( $c == 0 ) {
        $content->assign( "nocontribs", "1" );
     }

     $content->assign( "list", $list );
  }
}

if( isset( $continue ) ) {
   $content->assign( "nextlist", "<a href=\"//tools.wmflabs.org".$_SERVER['REQUEST_URI']."&continue={$continue}\">See next $limit contributions</a>" );
}

//Calculate time taken to execute
$exectime = number_format(microtime( 1 ) - $time, 2, '.', '');
$phptemp->assign( "excecutedtime", "Executed in $exectime seconds" );
$phptemp->assign( "memory", "Taken ". number_format((memory_get_usage() / (1024 * 1024)), 2, '.', '')." megabytes of memory to execute." );

//Output footer
//include( '/data/project/xtools/public_html/common/footer.php' );

$phptemp->assign( "content", $content->display( true ) );
$phptemp->assign( "curlang", $lang );
$phptemp->assign( "langlinks", $langlinks );
$phptemp->assign( "source", "rangecontribs" );
$phptemp->display();

function calcCIDR( $cidr ) {
   $cidr = explode('/', $cidr);

   $cidr_base = $cidr[0];
   $cidr_range = $cidr[1];

   $cidr_base_bin = addZero( decbin( ip2long( $cidr_base ) ) );

   $cidr_shortened = substr( $cidr_base_bin, 0, $cidr_range );
   $cidr_difference = 32 - $cidr_range;

   $cidr_begin = $cidr_shortened . str_repeat( '0', $cidr_difference );
   $cidr_end = $cidr_shortened . str_repeat( '1', $cidr_difference );

   $ip_begin = long2ip( bindec( removeZero( $cidr_begin ) ) );
   $ip_end = long2ip( bindec( removeZero( $cidr_end ) ) );
   $ip_count = bindec( $cidr_end ) - bindec( $cidr_begin ) + 1;
   
   return array( 'begin' => $ip_begin, 'end' => $ip_end, 'count' => $ip_count, 'shortened' => $cidr_shortened, 'suffix' => $cidr_range );
}

function calcRange( $iparray ) {
   //print_r($iparray);
   $iparray = array_unique($iparray);
   $iparray = array_map("ip2long",$iparray);
   sort($iparray);
   $iparray = array_map("long2ip",$iparray);
   
   $ip_begin = $iparray[0];
   $ip_end = $iparray[ count($iparray) - 1 ];
   
   $ip_begin_bin = addZero( decbin( ip2long( $ip_begin ) ) );
   $ip_end_bin = addZero( decbin( ip2long( $ip_end ) ) );
   
   $ip_shortened = findMatch( $ip_begin_bin, $ip_end_bin );
   $cidr_range = strlen( $ip_shortened );
   $cidr_difference = 32 - $cidr_range;
   
   $cidr_begin = $ip_shortened . str_repeat( '0', $cidr_difference );
   $cidr_end = $ip_shortened . str_repeat( '1', $cidr_difference );
   
   $ip_count = bindec( $cidr_end ) - bindec( $cidr_begin ) + 1;

   return array( 'begin' => $ip_begin, 'end' => $ip_end, 'count' => $ip_count, 'shortened' => $ip_shortened, 'suffix' => $cidr_range );
}

function getReplag() {
   $query = "SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(rc_timestamp) as replag FROM recentchanges ORDER BY rc_timestamp DESC LIMIT 1";
      $result = mysql_query( $query );
      //if( !$result ) toDie( wfMsg('mysqlerror', mysql_error() ) );
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
      $r .= floor($secs/$week) . 'w';
      $secs %= $week;
   }
   if ($secs > $day) {
      $r .= floor($secs/$day) . 'd';
      $secs %= $day;
   }
   if ($secs > $hour) {
      $r .= floor($secs/$hour) . 'h';
      $secs %= $hour;
   }
   if ($secs > $minute) {
      $r .= floor($secs/$minute) . 'm';
      $secs %= $week;
   }
   if ($secs > $second) {
      $r .= floor(($secs/$second)/100) . 's';
   }
   
   return $r;
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
   global $content;
   //echo $msg;
   //include( '/data/project/xtools/public_html/common/footer.php' );
   //die();
   $content->assign( "error", $msg );
   assignContent();
}

function addZero ( $string ) {
    $count = 32 - strlen( $string );   
    for( $i = $count; $i>0; $i-- ) {
      $string = "0" . $string;
   }
   return $string;
}

function removeZero ( $string ) {
   $string = str_split( $string, 1 );
   foreach( $string as $val => $strchar ) {
      if( $strchar == 1 ) break;
      
      unset( $string[$val] );
   }
   
   $string = implode( "", $string );
   return $string;
}

function findMatch( $ip1, $ip2 ) {
   $ip1 = str_split( $ip1, 1 );
   $ip2 = str_split( $ip2, 1 );
   
   $match = null;
   foreach ( $ip1 as $val => $char ) {
      if( $char != $ip2[$val] ) break;
      
      $match .= $char;
   }
   
   return $match;
}

//Debugging stuff
function pre( $array ) {
   echo "<pre>";
   print_r( $array );
   echo "</pre>";
}

function assignContent() {
   global $phptemp, $content;
   $phptemp->assign( "content", $content->display( true ) );
   $phptemp->display(); 
   die();
}

?>
