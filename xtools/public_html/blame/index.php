<?php

//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );
	require_once( PEACHY_BASE_SYS_DIR . '/Peachy/Init.php' );
	
//Load WebTool class
	$wt = new WebTool( 'blame' );
	$wt->setLimits();
	$wt->getPageTemplate( 'form' );

//Get params from query string	
	$article = $wgRequest->getVal( 'article' );
	$article = $wgRequest->getVal( 'page' , $article );
	$nofollowredir = $wgRequest->getBool( 'nofollowredir' );
	$text = $wgRequest->getText( 'text' );

	$wi = $wt->wikiInfo;
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;
	
	
//Show form if &article parameter is not set (or empty)
	if( $lang == "" || $wiki == "" || $article == "" || $text == "" ) {
		$wt->showPage();
	}

// execute the main logic
	$pgVerbose = array();
	$site = Peachy::newWiki( null, null, null, "http://$wi->domain/w/api.php" );
	$pageClass = $site->initPage( $article, null, !$nofollowredir );
	$title = $pageClass->get_title();
	
	$list = getBlameResult( $pageClass, $text);
	
	$wt->content = getPageTemplate( 'result' );
	$wt->assign( 'list', $list );
	$wt->assign( 'domain', $domain );
	$wt->assign( 'lang', $lang );
	$wt->assign( 'wiki', $wiki );
	$wt->assign( 'searchtext', htmlspecialchars( $text ));
	$wt->assign( 'page', htmlspecialchars($title));
	$wt->assign( 'urlencodedpage', rawurlencode( str_replace(" ", "_", $prefix.$title ) ) );
	$wt->assign( 'xtoolsbasedir', XTOOLS_BASE_WEB_DIR );


unset( $base, $revs, $result);
$wt->showPage();



function getBlameResult( &$pageClass, $text ){

	$history = $pageClass->history( null, 'older', true );
	
	$list = '';
	$anz = count( $history );
	foreach( $history as $id => $rev ) {
		
		if( in_string( $text, $rev['*'], true ) && ( ($id +1 == $anz) || !in_string( $text, $history[$id+1]['*'], true ) ) ){

			$date = date('Y-m-d, H:i ', strtotime( $rev['timestamp']) );
			$year = date('Y', strtotime( $rev['timestamp']) );
			$month = date('m', strtotime( $rev['timestamp']) );
			$minor = ( $row['rev_minor_edit'] == '1' ) ? '<span class="minor" >m</span>' : '';
			
			$list .= '
				<tr>
				<td style="font-size:95%; white-space:nowrap;">'.$date.' &middot; </td>
				<td>(<a href="//{$domain}/w/index.php?title={$urlencodedpage}&amp;diff=prev&amp;oldid='.$rev['revid'].'" title="'.$title.'">diff</a>)</td>
				<td>(<a href="//{$domain}/w/index.php?title={$urlencodedpage}&amp;action=history&amp;year='.$year.'&amp;month='.$month.' " title="'.$title.'">hist</a>)</td>
				<td><a href="//{$domain}/wiki/User:'.$rev['user'].'" title="User:'.$rev['user'].'">'.$rev['user'].'</a> </td>
				<td style="font-size:85%" > &middot; '.htmlspecialchars( $rev['comment'] ).'</td>
				</tr>
			';
		}
		
	}
	
	return $list;
}

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '..old..';
	
	$templateResult = '
	<div class="panel panel-primary" style="text-align:center">
		<div class="panel-heading">
			<p class="xt-heading-top" >
				<a href="http://{$domain}/wiki/{$urlencodedpage}">{$page}</a>
				<small><span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span></small> 
			</p>
		</div>
		<div class="panel-body xt-panel-body-top"  >
			<p>
				<a href="//{$domain}/w/index.php?title=Special:Log&type=&page={$urlencodedpage}" >Page log</a> &middot;
				<a href="//{$xtoolsbasedir}/articleinfo/?lang={$lang}&wiki={$wiki}&page={$urlencodedpage}" >Page history</a> &middot;
			</p>
			
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4  class="topcaption" >{#searchresult#} <span class="showhide" onclick="javascript:switchShow( \'generalstats\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="generalstats">
					<table>
						<tr><td>{#tosearch#}: </td><td style="padding-left:1em; font-style:italic" >{$searchtext}</td></tr>
					</table>
					<br />
					<table class="leantable sortable table-condensed xt-table" >
						<tr>
						<th>{#date#}</th>
						<th>Diff</th>
						<th>Hist</th>
						<th>{#username#}</th>
						<th>{#comment#}</th>
						</tr>
						{$list}
					</table>
					<br />
				</div>
			</div>
		</div>
	</div>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
}