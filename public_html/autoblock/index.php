<?php

//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );
	
//Load WebTool class
	$wt = new WebTool( 'autoblock' );
	$wt->setLimits();
	$wt->getPageTemplate( 'form' );
	
	$wt->assign( 'defaultUser', '%' );
	
	$wi = $wt->wikiInfo;
		$lang = $wi->lang; 
		$wiki = $wi->wiki;
		$domain = $wi->domain;

	$user = $wgRequest->getVal('user');
#	$ui = $wt->getUserInfo();
#		$user = $ui->user;
	
//Show form if &article parameter is not set (or empty)
	if( !$user ) {
		$wt->showPage();
	}
	if (!$domain ){
		$wt->error = "No wiki or project specified - default project set to: en.wikipedia";
		$lang = "en";
		$wiki = "wikipedia";
		$domain = "en.wikipedia.org";
	}
	
	$dbr = $wt->loadDatabase($lang, $wiki);
	
	$userdb = $dbr->strencode ($user);
	$query = "
   		SELECT ipb_id, ipb_by_text, UNIX_TIMESTAMP(ipb_expiry) as ipb_expiry, ipb_user 
   		FROM ipblocks 
   		WHERE ipb_auto = 1 AND ipb_reason LIKE '%$userdb%'
   	";
	
	$result = $dbr->query( $query );
	
	$list = '<tr><th>#</th><th>id</th><th>expiry</th><th>admin</th><th>action</th></tr>';
	if ( count($result) >0 ){
		foreach( $result as $i => $out ) {
			$list .= '
					<tr>
					<td>'.($i+1).'.</td>
					<td><strong>#' . $out['ipb_id'] . '</strong></td>
					<td class="tddate" style="vertical-align:middle">'.date('Y-m-d H:i',$out["ipb_expiry"]).'</td>
					<td>- blocked by <a href="//'.$domain.'/wiki/User:' . htmlspecialchars( $out['ipb_by_text'] ) . '">' . htmlspecialchars( $out['ipb_by_text'] ) . '</a></td>
					<td><a href="//'.$domain.'/w/index.php?title=Special:BlockList&action=unblock&id=' . $out['ipb_id'] . '">Lift block</a></td>
					</tr>
				';
		}
	}
	else{
		$list = $I18N->msg('noresult', array("variables" => array($user) ) ).'. Try % instead.';
	}
	$wt->content = getPageTemplate( 'result' );
	$wt->assign( 'list', $list );
	$wt->assign( 'domain', $domain );
	$wt->assign( 'searchtext', $user );
	
unset( $list, $result );	
$wt->showPage();
	

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){
	
	$templateResult = '
		<div class="panel panel-primary" style="text-align:center">
			<div class="panel-heading">
				<p class="xt-heading-top" >
					<span>Autoblock</span>
					<small><span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span></small>
				</p>
			</div>
			<div class="panel-body xt-panel-body-top" >	
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4  class="topcaption" >{#searchresult#} <span class="showhide" onclick="javascript:switchShow( \'generalstats\', this )">[{#hide#}]</span></h4>
					</div>
					<div class="panel-body" id="generalstats">
						<p>{#tosearch#}: {$searchtext}</p>
						<table class="table-striped sortable table-condensed xt-table" >	
							{$list}
						</table>
					</div>
				</div>
			</div>
		</div>
	';
	
	if( $type == "result" ) { return $templateResult; }
}

