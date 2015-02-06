<?php

//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );
	require_once( 'Counter.php' );

//Load WebTool class
	$wt = new WebTool( 'Automated Edits', 'autoedits', array() );
	$wt->setLimits();
	$wt->getPageTemplate( "form" );

	$wi = $wt->wikiInfo;
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;
	
	$ui = $wt->getUserInfo();
		$user = $ui->user;
	
	$begin = $wt->checkDate( $wgRequest->getVal( 'begin' ) );
	$end   = $wt->checkDate( $wgRequest->getVal( 'end' ) );

//Show form if &article parameter is not set (or empty)
	if( !$lang || !$wiki || !$user ) {
		$wt->showPage();
	}
	
	$dbr = $wt->loadDatabase( $lang, $wiki );
	$cnt = new Counter( $dbr, $user, $domain, true );
	
	
//Start doing the DB request
	$data = $cnt->calcAutoEditsDB( $dbr, $begin, $end );
	
	$list = '';
	foreach ( $data["tools"] as $toolname => $count  ){
		$list .= '
				<tr>
				<td><a href="//en.wikipedia.org/wiki/'.Counter::$AEBTypes[$toolname]["shortcut"].'">'.$toolname.'</a></td>
				<td class="tdnum" >'.$wt->numFmt($count).'</td>
				</tr>
			';
	}
	
	$wt->content = getPageTemplate( "result" );
	
	$wt->assign( 'list', $list);
	$wt->assign( 'username', $user);
	$wt->assign( 'usernameurl', rawurlencode($user) );
	$wt->assign( 'domain', $domain);
	$wt->assign( 'lang', $lang);
	$wt->assign( 'wiki', $wiki);
	$wt->assign( 'start', $data['start'] );
	$wt->assign( 'end',  $data['end'] );
	$wt->assign( 'totalauto', $wt->numFmt( $data['total'] ) );
	$wt->assign( 'totalall', $wt->numFmt( $data['editcount'] ) );
	$wt->assign( 'pct', $wt->numFmt( $data['pct'], 1 ) );
	

unset( $cnt, $data, $list );
$wt->showPage();



/**************************************** templates ****************************************
 * 
 */
function getPageTemplate( $type ){

	$templateForm = '..old..';
	
	$templateResult = '
	
	<div class="panel panel-primary" style="text-align:center">
		<div class="panel-heading">
			<p class="xt-heading-top" >
				<a href="http://{$domain}/wiki/User:{$usernameurl}">{$username}</a>
				<small><span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span></small>
			</p>
		</div>
		<div class="panel-body xt-panel-body-top" >	
			<p>
				<a href="//{$domain}/w/index.php?title=Special%3ALog&type=block&user=&page=User%3A{$usernameurl}&year=&month=-1&tagfilter=" >Block log</a> &middot;
				<a href="//tools.wmflabs.org/guc/?user={$usernameurl}" >Global user contributions</a> &middot;
				<a href="//meta.wikimedia.org/w/index.php?title=Special%3ACentralAuth&target={$usernameurl}" >Global Account Manager</a> &middot;
				<a href="//tools.wmflabs.org/wikiviewstats/?lang={$lang}&wiki={$wiki}&page={$userprefix}:{$usernameurl}*" >Pageviews in userspace</a> &middot;
			</p>
			
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4  class="topcaption" >{#autoedits#} <span class="showhide" onclick="javascript:switchShow( \'generalstats\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="generalstats">
					<table class="table-condensed  xt-table">
						<tr><th>Tool</th><th>{#count#}</th></tr>
						{$list}
					</table>
				</div>
			</div>
			
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4  class="topcaption" >{#summary#} <span class="showhide" onclick="javascript:switchShow( \'summary\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="summary">
					<table class="leantable table-condensed  xt-table">
						<tr><td>{#start#}</td>		<td>{$start}</td></tr>
						<tr><td>{#end#}</td>		<td>{$end}</td></tr>	
						<tr><td>{#autoedits#}</td>	<td class="tdnum" >{$totalauto}</td></tr>
						<tr><td>{#total#}</td>		<td class="tdnum" >{$totalall}</td></tr>
						<tr><td>{#percentage#}</td>	<td class="tdnum" >{$pct}%</td></tr>
					</table>
				</div>
			</div>
		</div>
	</div>
	';
				
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
}
