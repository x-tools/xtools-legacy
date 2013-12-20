<?php
$final_array = array();

error_reporting(E_ALL);
//ini_set("display_errors", 1);

require( '/data/project/xtools/API.php' );

$API = new API;

$format = $API->getFormat();
$API->setHeaders();

$prop = $API->getProp( array( 'username', 'is_ip', 'user_exists', 'user_id', 'opted_in', 'counts', 'groups', 'firstedit', 'averagepagedits' ) );

include( '/data/project/xtools/stats.php' );
$tool = 'ECAPI';
$surl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
if (isset($_GET['name'])) {
    addStat( $tool, $surl, @$_SERVER['HTTP_REFERER'], $_SERVER['HTTP_USER_AGENT'] );//Stat checking
}
unset($tool, $surl);

require_once( '/data/project/xtools/public_html/counter_commons/HTTP.php' );
require_once( '/data/project/xtools/public_html/counter_commons/Database.php' );
require_once( '/data/project/xtools/public_html/counter_commons/Functions.php' );
require_once( '/data/project/xtools/public_html/pcount/counter.php' );
require_once( '/data/project/xtools/database.inc' );

$wgDBPort = 3306;
$wgDBUser = $toolserver_username;
$wgDBPass = $toolserver_password;

$fnc = new Functions;

if( !isset( $_GET['name'] ) ) {
    toDie( "No username given", "missingusername" );
}
if( !isset( $_GET['lang'] ) ) {
    toDie( "No language given", "missinglanguage" );
}
if( !isset( $_GET['wiki'] ) ) {
    toDie( "No wiki given", "missingwiki" );
}

$name = ucfirst( ltrim( rtrim( str_replace( array('&#39;','%20'), array('\'',' '), $_GET['name'] ) ) ) );
$name = urldecode($name);
$name = str_replace('_', ' ', $name);
$name = str_replace('/', '', $name);
$wiki = $_GET['wiki'];
$lang = $_GET['lang'];
$lang = str_replace('/', '', $lang);
$wiki = str_replace('/', '', $wiki);
$url = $lang.'.'.$wiki.'.org';
$http = new HTTP( 'https://'.$url.'/w/' );

/*THIS NEEDS UPDATING AS SOON AS LABS IS READY
$tdbr = new Database( 
    'sql-toolserver', 
    $wgDBPort, 
    $wgDBUser, 
    $wgDBPass, 
    'toolserver', 
    true
);*/

$dbInfo = $fnc->getDBInfo( $lang, $wiki );
if( isset( $dbInfo['error'] ) ) {
    toDie( $phptemp->getConf( 'nowiki', $url ) );
}


 $wgDBname = $dbInfo['dbname'];
 $wgDBserver = $dbInfo['server'];

$dbr = new Database(
   $wgDBserver,
   $wgDBPort,
   $wgDBUser,
   $wgDBPass,
   $wgDBname,
   true
);

$wgNamespaces = $fnc->getNamespaces('enwiki_p');

$cnt = new Counter( $name );

$final_array = array(
    'query' => array(
        'count' => array(
        )
    )
);

$retUser = $cnt->getName();
$retIP = ( $cnt->getIP() ) ? "true" : "false";
$retExists = ( $cnt->getExists() ) ? "true" : "false";
$retUID = $cnt->getUID();
$retDeleted = intval( $cnt->getDeleted() );
$retLive = intval( $cnt->getLive() );
$retTotal = intval( $cnt->getTotal() );
$retGroupList = $cnt->getGroupList();
$retGroupList['_element'] = 'g';
//$retUniqueArticles = $cnt->getUniqueArticles();
//$retUniqueArticleCount = number_format( count($retUniqueArticles['total']) );
$retFirstEdit = $cnt->getFirstEdit();
$retAveragePageEdits = $cnt->getAveragePageEdits();
$retMonthTotals = $cnt->getMonthTotals();
$retNamespaceTotals = $cnt->getNamespaceTotals();
$retOptedIn = $http->getWhichOptIn( $cnt->getName() );

$final_array['query']['count']['username'] = $retUser;
$final_array['query']['count']['is_ip'] = $retIP;
$final_array['query']['count']['user_exists'] = $retExists;
$final_array['query']['count']['user_id'] = $retUID;
$final_array['query']['count']['opted_in'] = $retOptedIn;
$final_array['query']['count']['counts'] = array(
    'deleted' => $retDeleted,
    'live' => $retLive,
    'total' => $retTotal,
);
$final_array['query']['count']['groups'] = $retGroupList;
$final_array['query']['count']['firstedit'] = $retFirstEdit;
$final_array['query']['count']['averagepagedits'] = $retAveragePageEdits;

if( in_array( 'topedits', $prop ) && $retOptedIn != "false" ) {
}

if( in_array( 'namespacetotals', $prop ) ) {
}

if( in_array( 'monthtotals', $prop ) && $retOptedIn != "false" ) {
    $final_array['query']['count']['monthtotals'] = $cnt->getMonthTotals();
    foreach( $final_array['query']['count']['monthtotals'] as $month => $counts ) {
        $final_array['query']['count']['monthtotals'][$month]['_element'] = 'ns';
    }
    $final_array['query']['count']['monthtotals']['_element'] = 'm';
}

if( in_array( 'uniquearticles', $prop ) ) {
}

//var_dump( $final_array);

foreach( $final_array['query']['count'] as $k => $v ) {
    if( !in_array( $k, $prop ) ) {
        unset($final_array['query']['count'][$k]);
    }
}

$API->showArray( $final_array );

function toDie( $msg, $code = 'generror' ) {
    global $API;
    $array = array( 'error' => array( 'code' => $code, 'info' => $msg ) );
    $API->showArray( $array );
    die();
} 

