<?php 

try{
$gtime = microtime(true);
$ptime = microtime(true);
# throw new Exception('db-stuff again');
	//Requires		
	require_once( '/data/project/xtools/modules/WebTool.php' ); 
	require_once( 'ArticleInfo.php' );
	
$b1 = number_format(microtime(true)-$ptime,3);
$ptime = microtime(true);

	$wt = new WebTool( 'api');
	$wt->setLimits( 500, 5 );
	
$b2 = number_format(microtime(true)-$ptime,3);
$ptime = microtime(true);
	
	//get webrequest data
	$db        = $wgRequest->getVal( 'db' );
	$pageid    = (int)$wgRequest->getVal( 'pageid' );
	$pagetitle = $wgRequest->getVal( 'pagetitle' );
	$uselang   = $wgRequest->getVal( 'uselang' );
	$nsid      = (int)$wgRequest->getVal( 'nsid' );
	$wditemid  = $wgRequest->getVal( 'wditemid' );
	
	$test = $wgRequest->getVal( 'test' );
	$mode = $wgRequest->getVal( 'mode' );
	
	//get xtools cookie val; if 3rd party cookies are deactivated in browser this will be null
	$reenable = $wgRequest->getVal('reenable', null);
	
	$wditemid = ( substr($wditemid, 0,1) == 'Q' ) ? $wditemid : null;
	
	//get config data
	$conf = new stdClass();
		$conf->status		  = 'enabled';
		$conf->mode   		  = 'verbose';
		$conf->showRevision   = 1;
		$conf->showAuthor     = 1;
		$conf->showWatcher    = 1;
		$conf->showPageview   = 1;
		$conf->showCreatedby  = 1;
		$conf->showMainauthor = 0;
		
	if ( $lc = $redis->get('xagconfig'.session_id() ) ){
		$confread = json_decode( $lc );
		
		$conf->status = @$confread->status;
		$conf->mode = @$confread->mode;
		$conf->showRevision = @$confread->showRevision;
		$conf->showAuthor = @$confread->showAuthor;
		$conf->showWatcher = @$confread->showWatcher;
		$conf->showPageview = @$confread->showPageview;
		$conf->showCreatedby = @$confread->showCreatedby;
		$conf->showMainauthor = @$confread->showMainauthor;
		
		if ( $confread->uselang != 'default' ) {
			$uselang = $confread->uselang;
			$I18N->setLang( $uselang );
		}
		if ( $reenable ) { 
			$confread->status = 'enabled'; 
			$redis->setex( 'xagconfig'.session_id(), 86400, json_encode( $confread ) );
		}
	}
	
	
	$wi = $wt->getWikiInfo( null, null, $db );
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;
		$rlm = $wi->rlm;

	$dbr = $wt->loadDatabase( null, null, $db );
	$ai = new ArticleInfo($dbr, $wi, $pagetitle, null, null, false, $pageid, $conf, $nsid, $wditemid );
	
	if ( !$ai->data['editor_count']){
		throw new Exception('nodata');
	}
	
$b3 = number_format(microtime(true)-$ptime,3);
$ptime = microtime(true);	
	
	$style = ($wi->lang == "ru") ? ";display:block;padding-top:15px;" : ""; 
	$outerspan = '<span style="line-height:1.5em;'.$style.'" >';

	if ( in_array($lang, array('he','fa','ar')) ) {
		$outerspan = '<span class="mw-content-rtl" dir="rtl" >';
	}
	
	$checkcolor = $ai->checkResults['color'];

	$linkData = array(
			'<table>',
			'<tr><td colspan=2 >Results:</td></tr>',
			'<tr><td>Checkwiki:</td><td>'.$ai->checkResults['sources']['checkwiki'].'</td></tr>',
			'<tr><td><a href="//www.wikidata.org/wiki/'.$wditemid.'" >Wikidata:</a></td><td>'.$ai->checkResults['sources']['wikidata'].'</td></tr>',
			'<tr><td><a href="//tools.wmflabs.org/languagetool/pageCheck/index?url='.rawurlencode($pagetitle).'&lang='.$wi->lang.'" >LanguageTool:</a></td><td>'.$ai->checkResults['sources']['languagetool'].'</td></tr>',
			'<tr><td>Dead links:</td><td style="white-space:nowrap;">'.$ai->deadlinks.'</td></tr>',
			'</table>'
		);
	$checktitle = '<div id="xt-gadget-summary" class="menu" style="top:1.3em;padding:1em;z-index:20; direction: '.$wi->direction.'" >'.implode('', $linkData).'</div>';
	
	$linkData = array(
				"pageid" => $pageid,
				"project" => $domain,
				"uselang" => $uselang,
		);
	$style = '<style>div.vectorMenu {direction: '.$wi->direction.';float: left;cursor: pointer;position: relative;} div.vectorMenu:hover div.menu {display:block;} div.menu {min-width: 100%;position: absolute;top: 2.5em;left: -1px;background-color: white;border: solid 1px silver;border-top-width: 0;clear: both;text-align: left;display: none;} .xt-badge{display:inline-block;min-width:10px;padding:3px 5px;font-size:12px;font-weight:bold;color:rgb(255,255,255);line-height:1;vertical-align:baseline;white-space:nowrap;text-align:center;background-color:rgb(210,210,210);border-radius:2px;cursor:pointer}.xt-badge > div{display:none;text-align:left;top:15px;left:0px}.xt-badge:hover > div{display:block;}.xt-badge-unread{background-color:rgb(204,0,0);}</style>';
	
	
	$xtoolsPhLink = '&nbsp;&middot;&nbsp;'.$style.'<div class="vectorMenu" style="float:none;display:inline-block" ><a style=" '.$checkcolor.'" title="" href="//tools.wmflabs.org/xtools/articleinfo/index.php?'.http_build_query( $linkData ).'" >'.htmlspecialchars($I18N->msg('see_full_page_stats'),ENT_QUOTES).'</a>'.$checktitle.'</div>';
	
	$linkData = array(
			"user" => $ai->data['first_edit']['user'],
			"lang" => $lang,
			"wiki" => $wiki,
			"uselang" => $uselang,
		);
	$xtoolsEcLink = '<a style="border-bottom:1px dotted " title="SUL '.$I18N->msg('editcounter', array("escape"=>"htmlentities")).' ('.htmlspecialchars($I18N->msg('approximate'),ENT_QUOTES).'). '.htmlspecialchars($I18N->msg('see_full_edit_counts'),ENT_QUOTES).'." href="//tools.wmflabs.org/xtools/ec/index.php?'.http_build_query( $linkData ).'" >'.$wt->numFmt( $ai->data['first_edit']['user_count'] ).'</a>';
	
	if ( $lang== "commons" ) { $wiki = "m"; }
	$linkData = array(
			"lang" => $lang,
			"project" => $wiki,
			"locale" => $uselang,
			"page" => $pagetitle,
			"latest" => "30"
		);
	$pageviewsText = $I18N->msg('pageviews', array("escape"=>"htmlentities")).' (30 '.$I18N->msg('days', array("variables"=>array(60), "escape"=>"htmlentities")).')';
	$pageviewsLink = '<a title="Wiki ViewStats" href="//tools.wmflabs.org/wikiviewstats/?'.http_build_query( $linkData ).'" >'.$pageviewsText.'</a>';
	
	$diff = $wt->datediff( new DateTime($ai->latestRev->timestamp) );
	$diffspan = '<span title="'.htmlspecialchars($I18N->msg('latestedit'),ENT_QUOTES).'" style="color:'.$diff->diffcolor.'" ><small> (<a style="color:inherit" href="//'.$wi->domain.'/w/index.php?diff='.$ai->latestRev->revid.'" >'.$diff->difftxt.'</a>)</small></span>';
	
	$messageLink = str_replace("\n", " ", @$wt->statusLink['message']) ;
	$messageLink = ( !$messageLink ) ? str_replace('{$linktext}', '', $wt->statusLink['agentconfig']) .'&nbsp;' : $messageLink .'&nbsp;&nbsp;' ;
	
	$mainAuthor = (isset($ai->mainAuthor)) ? ', <a title="WikiHistory" href="//tools.wmflabs.org/wikihistory/wh.php?page_id='.$pageid.'" >Hauptautor</a>: '. $ai->mainAuthor : '';
	$data = array(
			( !$conf->showRevision)   ? null : '<em>'.$wt->numFmt( $ai->data['count'] ).'</em> '.htmlspecialchars($I18N->msg('revisions', array("variables"=>array( $ai->data['count'] ))),ENT_QUOTES) . $diffspan ,
			( !$conf->showAuthor)     ? null : '<em>'.$wt->numFmt( $ai->data['editor_count'] ).'</em> '.htmlspecialchars($I18N->msg('authors', array("variables"=>array( $ai->data['editor_count'] ))),ENT_QUOTES) ,
			( !$conf->showWatcher)    ? null : '<em>'.$wt->numFmt( $ai->pagewatchers ).'</em> '.htmlspecialchars($I18N->msg('pagewatchers'),ENT_QUOTES) ,
			( !$conf->showPageview)   ? null : '<em>'.$wt->numFmt( $ai->pageviews->sumhits ).'</em> '.$pageviewsLink,
			( !$conf->showCreatedby)  ? null :  $I18N->msg('createdby').': <a href="//'.$wi->domain.'/wiki/User:'.$ai->data['first_edit']['user'].'">'.$ai->data['first_edit']['user'].'</a> ('. $rlm . $xtoolsEcLink.')'.$rlm,
			( !$conf->showMainauthor) ? null :  $mainAuthor,
		);

	
	
	if ($test == "test"){
		if ($mode == 'silent'){
			$style = 'min-width: 7px;border-radius: 2px;padding: 0.25em 0.45em 0.2em 0.45em;margin-left: -4px;text-align: center;background-color: #d2d2d2;font-weight: bold;color: white;cursor: pointer;text-decoration: none;';
			$html = '<a style="'.$style.'" class="mw-echo-notifications-badge" href="//tools.wmflabs.org/xtools/echo/" >1</a>';
		}
		else{
			$html = $outerspan . $messageLink . implode(", ", array_filter($data) ) . $xtoolsPhLink. "</span>";
		}
		
		$res = array(
			'sessid' => session_id(),
			'html' => $html,
			'cfg' => json_encode( $conf ),
		);
#		sleep(5);
		header("Access-Control-Allow-Origin: *"); 
		echo "xtoolsagent.resultloaded({resp:".json_encode( json_encode($res) )."})";
#		echo json_encode( json_encode($res) );
	}
	else{
		echo "xpagehistory.resultloaded('". $outerspan . $messageLink . implode(", ", array_filter($data) ) . $xtoolsPhLink. "</span>')";
	}
	
$b4 = number_format(microtime(true)-$ptime,3);
$bges = number_format(microtime(true)-$gtime,3);
#file_put_contents('/data/project/xtools/api_test', "\t$db\t$pagetitle\t$b1\t$b2\t$b3\t$b4\t$bges\n\n", FILE_APPEND );
if ($lang = "de"){
	#file_put_contents('/data/project/xtools/api_test', session_id()."\t$pagetitle".json_encode($_COOKIE)."\t$cookie\n\n", FILE_APPEND );
}
}
catch(Exception $e){
	echo "xpagehistory.resultloaded('maintenance')";
	file_put_contents('/data/project/xtools/api_test', session_id()."\t$pagetitle".json_encode($e)."\t$cookie\n\n", FILE_APPEND );
}

unset($wt, $ai, $wi, $linkData, $checkcolor);
if (isset($dbr) ){ $dbr->close(); unset($dbr); }
if (isset($redis) ){ $redis->close(); unset($redis); }


	