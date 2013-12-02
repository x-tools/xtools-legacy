<?php

/*
Soxred93's Edit Counter
Copyright (C) 2010 Soxred93

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <//www.gnu.org/licenses/>.
*/

$time = microtime( 1 );//Calculate time in microseconds to calculate time taken to execute

error_reporting(E_ERROR);
ini_set("display_errors", 1);
ini_set("memory_limit", '64M');

require_once( '/data/project/xtools/stats.php' );

$tool = 'EditCounter';
$surl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
if (isset($_GET['wiki']) && isset($_GET['lang']) && isset($_GET['name'])) {
	addStat( $tool, $surl, $_SERVER['HTTP_REFERER'], $_SERVER['HTTP_USER_AGENT'] );//Stat checking
}
unset($tool, $surl);

require_once( '/data/project/xtools/public_html/phptemp/PHPtemp.php' );
require_once( '/data/project/xtools/public_html/phptemp/Language.php' );
require_once( '/data/project/xtools/public_html/sitenotice.php' );

$phptemp = new PHPtemp( '/data/project/xtools/public_html/templates/main.tpl' );
$content = new PHPtemp( '/data/project/xtools/public_html/pcount/templates/pcount.tpl' );

$language = new Language( array( "en" ) );
$lang = $language->getLang();

$langlinks = $language->generateLangLinks();

$phptemp->load_config( '/data/project/xtools/public_html/configs/'.$lang.'.conf', 'main' );
$phptemp->load_config( '/data/project/xtools/public_html/pcount/configs/'.$lang.'.conf', 'pcount' );
$content->load_config( '/data/project/xtools/public_html/configs/'.$lang.'.conf', 'main' );
$content->load_config( '/data/project/xtools/public_html/pcount/configs/'.$lang.'.conf', 'pcount' );

$phptemp->assign( "header", $phptemp->getConf('tool') );

$phptemp->assign( "curlang", $lang );
$phptemp->assign( "langlinks", $langlinks );
$phptemp->assign( "source2", "//tools.wmflabs.org/xtools/pcount/source.php" );
$phptemp->assign( "translate", "//tools.wmflabs.org/xtools/pcount/translate.php" );

$siteNoticeClass = new siteNotice;
$sitenotice = $siteNoticeClass->checkSiteNoticeHtml();
if( $sitenotice ) {
	$phptemp->assign( "alert", $sitenotice );
}

require_once( '/data/project/xtools/public_html/counter_commons/HTTP.php' );
require_once( '/data/project/xtools/public_html/counter_commons/Database.php' );
require_once( '/data/project/xtools/public_html/counter_commons/Functions.php' );
require_once( '/data/project/xtools/public_html/pcount/counter.php' );
require_once( '/data/project/xtools/Graph.php' );
require_once( '/data/project/xtools/database.inc' );

$wgDBPort = 3306;
$wgDBUser = $toolserver_username;
$wgDBPass = $toolserver_password;

$fnc = new Functions;
$http = new HTTP( '//en.wikipedia.org/w/' );

if( !isset( $_GET['name'] ) ) {
	$content->assign( 'form', 'true' );
	$fnc->assignContent();
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

/*$tdbr = new Database( 
	'sql-toolserver', 
	$wgDBPort, 
	$wgDBUser, 
	$wgDBPass, 
	'toolserver', 
	true
);*/

if( $wiki == 'wikidata' ) {
	$lang = "wikidata";
	$wiki = "wikimedia";
}

$dbInfo = $fnc->getDBInfo( $lang, $wiki );
if( isset( $dbInfo['error'] ) ) {
	$fnc->toDie( $phptemp->getConf( 'nowiki', $url ) );
}

$dbr = new Database( 
	$dbInfo['server'], 
	$wgDBPort, 
	$wgDBUser, 
	$wgDBPass, 
	$dbInfo['dbname'], 
	true
);

$wgNamespaces = $fnc->getNamespaces('enwiki_p');

$cnt = new Counter( $name );

$graphArray = array(
	'colors' => array(
		0 => 'FF5555',
		1 => '55FF55',
		2 => 'FFFF55',
		3 => 'FF55FF',
		4 => '5555FF',
		5 => '55FFFF',
		6 => 'C00000',
		7 => '0000C0',
		8 => '008800',
		9 => '00C0C0',
		10 => 'FFAFAF',
		11 => '808080',
		12 => '00C000',
		13 => '404040',
		14 => 'C0C000',
		15 => 'C000C0',
		100 => '75A3D1',
		101 => 'A679D2',
		102 => '660000',
		103 => '000066',
		104 => 'FAFFAF',
		105 => '408345',
		106 => '5c8d20',
		107 => 'e1711d',
		108 => '94ef2b',
		109 => '756a4a',
		110 => '6f1dab',
		111 => '301e30',
		112 => '5c9d96',
		113 => 'a8cd8c',
		114 => 'f2b3f1',
		115 => '9b5828',
	),
	'names' => $wgNamespaces['names'],
	'monthly' => $cnt->getMonthTotals(),
	'gross' => $cnt->getNamespaceTotals(),
);

$graph = new Graph( $graphArray );

$uniqueEdits = $cnt->getUniqueArticles();

if( !$cnt->getExists() ) {
	$fnc->toDie( $phptemp->getConf( 'nosuchuser', $cnt->getName() ) );
}

$phptemp->assign( "page", $cnt->getName() );
$content->assign( "username", $cnt->getName() );
$content->assign( "usernameurl", urlencode($cnt->getName()) );
$content->assign( "url", $url );
if( count( $cnt->getGroupList() ) ) {
	$content->assign( "groups", implode( ', ', $cnt->getGroupList() ) );
}
if( $cnt->getLive() > 0) {

	$content->assign( "firstedit", $cnt->getFirstEdit() );
	$content->assign( "unique", number_format( count($uniqueEdits['total']) ) );
	$content->assign( "average", $cnt->getAveragePageEdits() );
	$content->assign( "live", number_format( intval( $cnt->getLive() ) ) );
	$content->assign( "deleted", number_format( intval( $cnt->getDeleted() ) ) );
	
	$content->assign( "namespacetotals", $graph->legend() );
	$content->assign( "graph", $graph->pie( $phptemp->getConf( 'namespacetotals' ) ) );
	if( $http->isOptedIn( $cnt->getName() ) ) {
		$content->assign( "monthcounts", $graph->horizontalBar( 600 ) );
	}
	else {
		$content->assign( "nograph", $phptemp->getConf( "nograph", $cnt->getName(), $url ) );
	}
	
	$out = null;
	if( $cnt->getLive() < '45000' ) {
		ksort($uniqueEdits['namespace_specific']);
		
		$num_to_show = 10;
		
		foreach( $uniqueEdits['namespace_specific'] as $namespace_id => $articles ) {
			//$out .= "<h4>" . $wgNamespaces['names'][$namespace_id] . "</h4>\n";
			$out .= '<table class="collapsible collapsed"><tr><th>' . $wgNamespaces['names'][$namespace_id] . '</th></tr><tr><td>';
			$out .= "<ul>\n";
			
			asort( $articles );
			$articles = array_reverse( $articles );
			
			$i = 0;
			foreach ( $articles as $article => $count ) {
				if( $i == $num_to_show ) break;
				if( $namespace_id == 0 ) {
					$nscolon = '';
				}
				else {
					$nscolon = $wgNamespaces['names'][$namespace_id].":";
				}
				$articleencoded = urlencode( $article );
				$articleencoded = str_replace( '%2F', '/', $articleencoded );
				$trimmed = substr($article, 0, 50).'...';
				$out .= '<li>'.$count." - <a href='//$lang.$wiki.org/wiki/".$nscolon.$articleencoded.'\'>';
				if(strlen(substr($article, 0, 50))<strlen($article)) {
					$out .= $trimmed;
				}
				else {
					$out .= $article;
				}
				$out .= "</a></li>\n";
				$i++;
			}
			$out .= "</ul></td></tr></table><br />";
		}

		if( $http->isOptedIn( $cnt->getName() ) ) {
			$content->assign( "topedited", $out );
		}
		else {
			$content->assign( "nograph", $phptemp->getConf( "nograph", $cnt->getName(), $url ) );
		}
	}
	else {
		$content->assign( "notopedit", "" );
	}
}
$content->assign( "total", number_format( intval( $cnt->getTotal() ) ) );

$times = $fnc->calcTimes( $time );

$phptemp->assign( "moreheader", 
	'<link rel="stylesheet" href="//tools.wmflabs.org/xtools/counter_commons/NavFrame.css" type="text/css" />' . "\n\t" . 
	'<script src="//bits.wikimedia.org/skins-1.5/common/wikibits.js?urid=257z32_1264870003" type="text/javascript"></script>' . "\n\t" . 
	'<script src="//tools.wmflabs.org/xtools/counter_commons/NavFrame.js" type="text/javascript"></script>'
);
$content->assign( "popup", true );

$replag = $fnc->getReplag();

if ($replag[0] > 120) {
	$content->assign( 'replag', $phptemp->getConf( 'highreplag', $replag[1] ) );
}

$phptemp->assign( "executedtime", $phptemp->getConf( 'executed', $times['time'] ) );
$phptemp->assign( "content", $content->display( true ) );

$phptemp->display();
