<?php

$time = microtime( 1 );//Calculate time in microseconds to calculate time taken to execut

ini_set('memory_limit', '256M');

//error_reporting(E_ALL);
ini_set("display_errors", 1);

include( '/data/project/xtools/stats.php' );

$tool = 'Blame';
$surl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
if (isset($_GET['name'])) {
    if( isset($_SERVER['HTTP_REFERER']) ) $refer = $_SERVER['HTTP_REFERER'];
    else $refer = "none";
    addStat( $tool, $surl, $refer, $_SERVER['HTTP_USER_AGENT'] );//Stat checking
}
unset($tool, $surl);

require_once( "/data/project/xtools/public_html/Smarty/languages.class.php" );
require_once( "/data/project/xtools/public_html/Smarty/Smarty.class.php" );
require_once( '/data/project/xtools/public_html/sitenotice.php' );
echo "<!--";
require_once( '/data/project/xtools/Peachy/Init.php' );
echo "-->";
require_once( '/data/project/xtools/database.inc' );

$phptemp = new Smarty();
$content = new Smarty();

$language = new Language( array( "en" ) );
$lang = $language->getLang();

$langlinks = $language->generateLangLinks();

$phptemp->config_load( $lang . '.conf', 'blame' );
$content->config_load( $lang . '.conf', 'blame' );

$siteNoticeClass = new siteNotice;
$sitenotice = $siteNoticeClass->checkSiteNoticeRaw();
if( $sitenotice ) {
   $phptemp->assign( "alert", $sitenotice );
}

if( !isset( $_GET['article'] ) || !isset( $_GET['text'] ) || empty( $_GET['article'] ) || empty( $_GET['text'] ) ) {
   $content->assign( 'form', $lang );
   assignContent();
}

flush();

$article = ucfirst( ltrim( rtrim( str_replace( array('&#39;','%20'), array('\'',' '), $_GET['article'] ) ) ) );
$article = urldecode($article);
$article = str_replace('_', ' ', $article);
$article = str_replace('/', '', $article);
$wiki = $_GET['wiki'];
$lang = $_GET['lang'];
$lang = str_replace('/', '', $lang);
$wiki = str_replace('/', '', $wiki);
$url = $lang.'.'.$wiki.'.org';

//Load database
/*$dbr = new Database( 
   'sql-toolserver', 
   $toolserver_username, 
   $toolserver_password, 
   'toolserver'
);

$res = $dbr->select(
   'wiki',
   array( 'dbname', 'server', ),
   array( 'domain' => "$lang.$wiki.org" )
);
   
if( !count( $res ) ) {
   toDie( $phptemp->get_config_vars( 'nowiki', $url ) );
}*/

if( $wiki == 'wikipedia' || $wiki == 'wikimedia' ) $wiki = "wiki";
		$res['server'] = $lang.$wiki.".labsdb";
		$res['dbname'] = $lang.$wiki."_p";
		
		if ($wiki == 'wikidata') {
    $res['dbname'] = 'wikidatawiki_p';
    $res['server'] = 'wikidatawiki.labsdb';
		}

$dbr = new Database( 
   $res['server'], 
   $toolserver_username, 
   $toolserver_password, 
   $res['dbname']
);

$pgVerbose = array();
$site = Peachy::newWiki( null, null, null, 'http://'.$url.'/w/api.php' );

$pageClass = $site->initPage( $article, null, !isset( $_GET['nofollowredir'] ) );
$phptemp->assign( "page", $pageClass->get_title() );

$history = $pageClass->history( null, 'older', true );

$revs = array();
foreach( $history as $id => $rev ) {
   if( ( $id + 1 ) == count( $history ) ) {
      if( in_string( $_GET['text'], $rev['*'] , true ) ) $revs[] = parseRev( $rev );
   }
   else {
      if( in_string( $_GET['text'], $rev['*'], true ) && !in_string( $_GET['text'], $history[$id+1]['*'], true ) ) $revs[] = parseRev( $rev );
   }
   unset( $history[$id] );//Saves memory
}

$content->assign( "revs", $revs );

//Calculate time taken to execute
$exectime = number_format(microtime( 1 ) - $time, 2, '.', '');
$phptemp->assign( "excecutedtime", "Executed in $exectime seconds" );
$phptemp->assign( "memory", "Taken ". number_format((memory_get_usage() / (1024 * 1024)), 2, '.', '')." megabytes of memory to execute." );

$phptemp->assign( "curlang", $lang );
$phptemp->assign( "langlinks", $langlinks );
$phptemp->assign( "source", "ipcalc" );
assignContent();
   
//If there is a failure, do it pretty.
function toDie( $msg ) {
   global $content;
   //echo $msg;
   //include( '/data/project/xtools/public_html/common/footer.php' );
   //die();
   $content->assign( "error", $msg );
   assignContent();
}

//Debugging stuff
function pre( $array ) {
   echo "<pre>";
   print_r( $array );
   echo "</pre>";
}

function assignContent() {
   global $phptemp, $content;
   $phptemp->assign( "content", $content->fetch( 'blame.tpl' ) );
   $phptemp->display( '../../templates/mainSmarty.tpl' ); 
   die();
}

function parseRev( $rev ) {
   global $url, $pageClass;
   
   $urltitle = urlencode($pageClass->get_title());
   $timestamp = $rev['timestamp'];
    $date = date('M d, Y H:i:s', strtotime( $timestamp ) );
   
   $list = '(<a href="//'.$url.'/w/index.php?title='.$urltitle.'&amp;diff=prev&amp;oldid='.urlencode($rev['revid']).'" title="'.$title.'">diff</a>) ';

   $list .= '(<a href="//'.$url.'/w/index.php?title='.$urltitle.'&amp;action=history" title="'.$pageClass->get_title().'">hist</a>) . . ';
   
   if( isset( $rev['minor'] ) ) {
      $list .= '<span class="minor">m</span>  ';
   }
   
   $list .= '<a href="//'.$url.'/wiki/'.$urltitle.'" title="'.$title.'">'.$title.'</a>‎; ';
   
   $list .= $date . ' . . ';
   
   $list .= '<a href="//'.$url.'/wiki/User:'.$rev['user'].'" title="User:'.$rev['user'].'">'.$rev['user'].'</a> ';
   
   $list .= '(<a href="//'.$url.'/wiki/User_talk:'.$rev['user'].'" title="User talk:'.$rev['user'].'">talk</a>) ';
   
   if( isset( $rev['comment'] ) ) $list .= '('.$rev['comment'].')';
   
   $list .= "<hr />\n</li>\n";
   
   return $list;
}



