<?php
//error_reporting(E_ALL);
ini_set("display_errors", 1);

$time = microtime( 1 );//Calculate time in microseconds to calculate time taken to execute

include( '/data/project/xtools/public_html/common/header.php' );
include( '/data/project/xtools/wikibot.classes.php' );
include( '/data/project/xtools/stats.php' );
include( '/data/project/xtools/public_html/common/rfalib2.php');
$tool = 'RFAP';
$surl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
if (isset($_GET['name'])) {
    if( isset($_SERVER['HTTP_REFERER']) ) $refer = $_SERVER['HTTP_REFERER'];
    else $refer = "none";
    addStat( $tool, $surl, $refer, $_SERVER['HTTP_USER_AGENT'] );//Stat checking
}
unset($tool, $surl);

//Debugging stuff
//error_reporting(E_ALL);
ini_set("display_errors", 1);
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
	<h2 class="table">How did a user vote? (clone of SQL\'s tool)</h2>';
	
	
//If there is a failure, do it pretty.
function toDie( $msg ) {
	echo $msg;
	include( '/data/project/xtools/public_html/common/footer.php' );
	die();
}


//Tell footer.php to output source
/*function outputSource( $msg ) {
	echo "<li>
	<a href=\"https://svn.cluenet.org/viewvc/soxred93/trunk/bots/Tools/editsummary/index.php?view=markup\">View source</a>
	</li>";
}*/

if( !isset( $_GET['name'] ) ) {
	toDie( 'Welcome to the RfA Vote Calculator!<br />Kudos to SQL for the code!<br />
		<form action="index.php" method="get">
		Username: <input type="text" name="name" /><br />
		<input type="submit" />
		</form>' );
}

$http = new http;
$wpq = new wikipediaquery;
$wpapi = new wikipediaapi;
$name = mysql_escape_string( ucfirst( ltrim( rtrim( $_GET['name'] ) ) ) );
$oldname = $_GET['name'];
$names_old = array();

//Check if the user is an IP address
if( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $name ) ) {
	toDie('Cannot be an IP.');
}

$wlh = $wpapi->backlinks('User:'.$oldname,500,$continue,'redirects',false);
foreach($wlh as $bl) {
	$names_old[] = $bl['title'];
}

//Connect to database
require_once( '/data/project/xtools/database.inc' );

$mysql = mysql_connect( 'enwiki.labsdb',$toolserver_username,$toolserver_password );
@mysql_select_db( 'enwiki_p', $mysql ) or toDie( "MySQL error, please report to X! using <a href=\"//en.wikipedia.org/wiki/User:X!/Bugs\">the bug reporter.</a> Be sure to report the following SQL error when reporting:<br /><pre>".mysql_error()."</pre>" );

$tools = mysql_connect("tools-db",$toolserver_username,$toolserver_password);
@mysql_select_db("p50380g50570_xtools", $tools) or toDie( "MySQL error, please report to X! using <a href=\"//en.wikipedia.org/wiki/User:TParis/Bugs\">the bug reporter.</a> Be sure to report the following SQL error when reporting:<br /><pre>".mysql_error()."</pre>" );
unset($toolserver_username, $toolserver_password);
//Done

function getSuccessfulRfAs() {
	global $wpapi;
	
	$titles = array();
	$continue = null;
	$ei = $wpapi->categorymembers('Successful requests for adminship',500,$continue);
	foreach ($ei as $data) { $titles[] = $data['title']; }
	while (isset($ei[499])) {
		$ei = $wpapi->categorymembers('Successful requests for adminship',500,$continue);
		foreach ($ei as $data) { $titles[] = $data['title']; }
	}
		
	return( $titles );

}

$successful = getSuccessfulRfAs();

function updaterfa($title, $name) {
	global $wpq;
    $results['support']=array();
    $results['oppose']=array();
    $results['neutral']=array();
        $myRFA = new RFA();
    $title = stripslashes($title);
    if(isset($_GET['debug'])) { echo "title = $title\n<br />\n"; }
    $buffer = $wpq->getpage("Wikipedia:$title",false);
        $result = $myRFA->analyze($buffer);
        $d_support =$myRFA->support;
        $d_oppose = $myRFA->oppose;
        $d_neutral = $myRFA->neutral;
    foreach ( $d_support as $support ) {
        if( !isset($support['name']) ) {
            if( isset($support['error']) ) $support['name'] = "Error: Unable to parse signature";
            else $support['name'] = "";
        }    
        array_push($results['support'], $support['name'] );
    }
    foreach ( $d_neutral as $neutral ) {
        if( !isset($neutral['name']) ) {
            if( isset($neutral['error']) ) $neutral['name'] = "Error: Unable to parse signature";
            else $neutral['name'] = "";
        }
        array_push($results['neutral'], $neutral['name'] );
    }
    foreach ( $d_oppose as $oppose ) {
        if( !isset($oppose['name']) ) {
            if( isset($oppose['error']) ) $oppose['name'] = "Error: Unable to parse signature";
            else $oppose['name'] = "";
        }
        array_push($results['oppose'], $oppose['name'] );
    }
    return($results);
}

$query = "SELECT user_id,user_editcount FROM user WHERE user_name = '$name';";
$result = mysql_query($query, $mysql);
$uinfo = mysql_fetch_assoc($result); 

if($uinfo['user_id'] == "") {
    toDie("<br />Invalid user!<br />");
}
if($uinfo['user_editcount'] > 150000) {
    toDie("<br />For technical reasons, this tool cannot be used on users with more than 150,000 edits.<br />\n");
}

$query = "SELECT ug_group FROM user_groups JOIN user ON ug_user = user_id WHERE user_name = '$name' AND ug_group = 'bot';";
$result = mysql_query($query, $mysql);
$isbot = mysql_fetch_assoc($result);  
if($isbot['ug_group'] == "bot") {
    toDie("<br />Why would a bot comment at RFA?<br />Not wasting server time with this query.<br />");
}
$query = "select page_latest,page_title,COUNT(*) from revision_userindex join page on page_id = rev_page where 
rev_user_text = '$name' AND page_namespace = '4'  AND page_title LIKE 'Requests_for_adminship/%' AND page_title NOT LIKE '%$name%' AND page_title != 
'Requests_for_adminship/RfA_and_RfB_Report' AND page_title != 'Requests_for_adminship/BAG' AND page_title NOT LIKE 
'Requests_for_adminship/Nomination_cabal%' AND page_title != 'Requests_for_adminship/Front_matter' AND page_title != 
'Requests_for_adminship/RfB_bar' AND page_title NOT LIKE 'Requests_for_adminship/%/%' AND page_title != 'Requests_for_adminship/nominate'  AND 
page_title != 'Requests_for_adminship/desysop_poll' AND page_title != 'Requests_for_adminship/Draft' AND page_title != 
'Requests_for_adminship/' AND page_title != 'Requests_for_adminship/Sample_Vote_on_sub-page_for_User:Jimbo_Wales' AND page_title != 'Requests_for_adminship/Promotion_guidelines' AND page_title != 'Wikipedia:Requests_    for_adminship/Standards'  GROUP by page_title order by COUNT(*) asc;";
$result = mysql_query($query, $mysql);
$allrfa = 0;
$rfastoupdate = array();
$thisrfas = array();
while ( $rfas = mysql_fetch_assoc($result) ) {
    $updated = 0;
    $allrfa++;
    $count = $rfas['COUNT(*)'];
    $title = utf8_decode($rfas['page_title']);
    $ts = "SELECT rev_timestamp FROM revision_userindex WHERE rev_id = '".$rfas['page_latest']."';";
    $resultrts = mysql_query($ts, $mysql);
    $rts = mysql_fetch_assoc($resultrts);
    $tenagos = time() - 864000;
    $tenago = date("YmdHis", $tenagos);
    array_push($thisrfas, $title);
    $title = mysql_real_escape_string($title);
    $indb = "SELECT * FROM rfap WHERE name = '$title';";
        $isindb = mysql_query($indb, $tools);
        $indb = mysql_fetch_assoc($isindb);
    if($indb['id'] == "" && $updated == 0) { array_push($rfastoupdate, $title); $updated = 1; }
    if($updated == 0) {
        $pullsten = $indb['pulls'] / 10;
        $pullsfivehund = $indb['pulls'] / 500;
        if($tenago < $rts['rev_timestamp'] && is_int($pullsten)) { array_push($rfastoupdate, $title); $updated = 1; }
        if(is_int($pullsfivehund) && $updated == 0) { array_push($rfastoupdate, $title); $updated = 1; }
        if($_GET['force'] == "1" && $updated == 0) { array_push($rfastoupdate, $title); $updated = 1; }    
        if(isset($_GET['debug'])) { 
            echo "$title - $updated<br />\n";
        }
    }
}
$upd = "RFAs updated this run:";
$nupd = 0;
foreach ($rfastoupdate as $rfatoup) {
    $nupd++;
    $title = htmlentities($rfatoup);
    $how = updaterfa($title, $name);
    $how2db = mysql_real_escape_string(serialize($how));
    $md5 = md5($how2db);
    $existq = "DELETE FROM p50380g50570_xtools.rfap WHERE name = '$rfatoup';";
    $existr = mysql_query($existq, $tools);
    $insert = "INSERT INTO p50380g50570_xtools.rfap (name , md5 , pulls , data ) VALUES ( '$rfatoup' , '$md5', '0' , '$how2db' );";
           $foo = mysql_query($insert, $tools);
    if(!$foo) toDie("ERROR: No result returned.<br />$insert");
    $upd .= " $title";
}
if($nupd == 0) { $upd .= " None!"; }
$ns = 0;
$no = 0;
$nn = 0;
$nu = 0;
$name_s = stripslashes($name);
$name_a = rawurldecode(preg_replace('/ /', '_', $name_s));
$name_b = rawurldecode(preg_replace('/_/', ' ', $name_s));

$query = "SELECT ug_group FROM user_groups JOIN user ON ug_user = user_id WHERE user_name = '$name' AND ug_group = 'bot';";
$result = mysql_query($query, $mysql);
$isbot = mysql_fetch_assoc($result);  

if(isset($_GET['debug'])) { 
    echo "<pre>\n";
    echo "user = $name , user_s = $name_s , user_a = $name_a\n";
    print_r($thisrfas);
    echo "</pre>\n";
}        
echo "<h2>Supported:</h2><ol>\n";
foreach ($thisrfas as $key => $arfa) {
    $arfa_s = mysql_real_escape_string($arfa);
    //echo $arfa_s."\n\n";
    $query = "SELECT * FROM rfap WHERE name = '$arfa_s';";
        $result = mysql_query($query, $tools);
        if(!$result) toDie("ERROR: No result returned.");
    $rfad = mysql_fetch_assoc($result);
    $data = unserialize(stripslashes($rfad['data']));
    $views = $rfad['pulls'];
    foreach($data['support'] as $od) {
        $od = ucfirst($od);
        if( preg_match( '/(.*)\#.*/' , $od , $matchme ) > 0 ) { $od = $matchme[1];} //A fix just for keeper :)
        if($od == $name_s || $od == $name_a || $od == $name_b || in_array('User:'.$od, $names_old)) {
            $stripped_arfa = preg_replace("/Requests_for_adminship\//i", "" ,$arfa);
            if(isset($_GET['debug'])) { $viewout = " (Views: $views|Key: $key)"; } else { $viewout = ""; }
           	
           	if(in_array("Wikipedia:Requests for adminship/$stripped_arfa", $successful ) ) { 
           		$endresult = "<b>(successful)</b>"; 
           	} 
           	else { 
           		$endresult = ""; 
           	}
            echo "<li><a href = \"//en.wikipedia.org/wiki/Wikipedia:$arfa\">$stripped_arfa</a>{$viewout} {$endresult}</li>\n";

            $pid = $rfad['id'];
            $pulls = $rfad['pulls'];
            $pullsnew = $pulls + 1;
            $ud = "UPDATE rfap SET pulls = '$pullsnew' WHERE id = '$pid';";
                $udr = mysql_query($ud, $tools);
                if(!$udr) toDie("ERROR: No result returned.");

            unset($thisrfas[$key]);
            $ns++;
        }
    }
}
if(isset($_GET['debug'])) { 
    echo "<pre>\n";
    print_r($thisrfas);
    echo "</pre>\n";
}        
echo "</ol><h2>Neutral:</h2><ol>\n";
foreach ($thisrfas as $key => $arfa) {
    $arfa_s = mysql_real_escape_string($arfa);
    $query = "SELECT * FROM rfap WHERE name = '$arfa_s';";
        $result = mysql_query($query, $tools);
        if(!$result) toDie("ERROR: No result returned.");
    $rfad = mysql_fetch_assoc($result);
    $data = unserialize(stripslashes($rfad['data']));
    $views = $rfad['pulls'];
    foreach($data['neutral'] as $od) {
        $od = ucfirst($od);
        if( preg_match( '/(.*)\#.*/' , $od , $matchme ) > 0 ) { $od = $matchme[1]; } //A fix just for keeper :)
        if($od == $name_s || $od == $name_a || $od == $name_b || in_array('User:'.$od, $names_old)) {
            $stripped_arfa = preg_replace("/Requests_for_adminship\//i", "" ,$arfa);
            if(isset($_GET['debug'])) { $viewout = " (Views: $views|Key: $key)"; } else { $viewout = ""; }
            
            if(in_array("Wikipedia:Requests for adminship/$stripped_arfa", $successful ) ) { 
           		$endresult = "<b>(successful)</b>"; 
           	} 
           	else { 
           		$endresult = ""; 
           	}
            echo "<li><a href = \"//en.wikipedia.org/wiki/Wikipedia:$arfa\">$stripped_arfa</a>{$viewout} {$endresult}</li>\n";
            
            $pid = $rfad['id'];
            $pulls = $rfad['pulls'];
            $pullsnew = $pulls + 1;
            $ud = "UPDATE rfap SET pulls = '$pullsnew' WHERE id = '$pid';";
                $udr = mysql_query($ud, $tools);
                if(!$udr) toDie("ERROR: No result returned.");

            unset($thisrfas[$key]);
            $nn++;
        }
    }
}
if(isset($_GET['debug'])) { 
    echo "<pre>\n";
    print_r($thisrfas);
    echo "</pre>\n";
}        
echo "</ol><h2>Opposed:</h2><ol>\n";
foreach ($thisrfas as $key => $arfa) {
    $arfa_s = mysql_real_escape_string($arfa);
    $query = "SELECT * FROM rfap WHERE name = '$arfa_s';";
        $result = mysql_query($query, $tools);
        if(!$result) toDie("ERROR: No result returned.");
    $rfad = mysql_fetch_assoc($result);
    $data = unserialize(stripslashes($rfad['data']));
    $views = $rfad['pulls'];
    foreach($data['oppose'] as $od) {
        $od = ucfirst($od);
        if( preg_match( '/(.*)\#.*/' , $od , $matchme ) > 0 ) { $od = $matchme[1]; } //A fix just for keeper :)
        if($od == $name_s || $od == $name_a || $od == $name_b || in_array('User:'.$od, $names_old)) {
            $stripped_arfa = preg_replace("/Requests_for_adminship\//i", "" ,$arfa);
            if(isset($_GET['debug'])) { $viewout = " (Views: $views|Key: $key)"; } else { $viewout = ""; }
            
            if(in_array("Wikipedia:Requests for adminship/$stripped_arfa", $successful ) ) { 
           		$endresult = "<b>(successful)</b>"; 
           	} 
           	else { 
           		$endresult = ""; 
           	}
            echo "<li><a href = \"//en.wikipedia.org/wiki/Wikipedia:$arfa\">$stripped_arfa</a>{$viewout} {$endresult}</li>\n";
            
            $pid = $rfad['id'];
            $pulls = $rfad['pulls'];
            $pullsnew = $pulls + 1;
            $ud = "UPDATE rfap SET pulls = '$pullsnew' WHERE id = '$pid';";
                $udr = mysql_query($ud, $tools);
                if(!$udr) toDie("ERROR: No result returned.");

            unset($thisrfas[$key]);
            $no++;
        }
    }
}
if(isset($_GET['debug'])) { 
    echo "<pre>\n";
    print_r($thisrfas);
    echo "</pre>\n";
}        
echo "</ol><h2>Did not comment / Could not parse:</h2><ol>\n";
foreach ($thisrfas as $key => $arfa) {
    $arfa_s = mysql_real_escape_string($arfa);
    $query = "SELECT * FROM rfap WHERE name = '$arfa_s';";
        $result = mysql_query($query, $tools);
        if(!$result) toDie("ERROR: No result returned.");
    $rfad = mysql_fetch_assoc($result);
    $stripped_arfa = preg_replace("/Requests_for_adminship\//i", "" ,$arfa);
    $views = $rfad['pulls'];
    if(isset($_GET['debug'])) { $viewout = " (Views: $views|Key: $key)"; } else { $viewout = ""; }
    echo "<li><a href = \"//en.wikipedia.org/wiki/Wikipedia:$arfa\">$stripped_arfa</a>$viewout</li>\n";
    $pid = $rfad['id'];
    $pulls = $rfad['pulls'];
    $pullsnew = $pulls + 1;
    $ud = "UPDATE rfap SET pulls = '$pullsnew' WHERE id = '$pid';";
        $udr = mysql_query($ud, $tools);
        if(!$udr) toDie("ERROR: No result returned.");

    $nu++;
}
echo "</ol>\n";
$ar = $ns + $nn + $no;

if($ns > 0) {
    $sp = round($ns / $ar, 3) * 100;
} else {
    $sp = 0;
}
if($nn > 0) {
    $np = round($nn / $ar, 3) * 100;
} else {
    $np = 0;
}
if($no > 0) {
    $op = round($no / $ar, 3) * 100;
} else {
    $op = 0;
}
echo "<br />$name has edited $allrfa RFA's! (Supported: $ns [$sp%], Neutral: $nn [$np%], Opposed: $no [$op%], Unknown $nu)<br />\n<small><center>$upd</small></center>\n";


//Calculate time taken to execute
$exectime = microtime( 1 ) - $time;
echo "<br /><hr><span style=\"font-size:100%;\">Executed in $exectime seconds.</span>";
echo "<br />Taken ".(memory_get_usage() / (1024 * 1024))." megabytes of memory to execute.";

//Output footer
include( '/data/project/xtools/public_html/common/footer.php' );

?>