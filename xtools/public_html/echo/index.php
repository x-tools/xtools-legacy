<?php

//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );
	require_once( 'Agent.php' );
	
//Load WebTool class
	$wt = new WebTool( 'echo' );
	$wt->setLimits();
	$wt->content = getPageTemplate( 'result' );
	
	$purge = $wgRequest->getBool('purge', false);
	
	$wi = $wt->wikiInfo;
		$lang = $wi->lang; 
		$wiki = $wi->wiki;
		$domain = $wi->domain;
	
		$list = '<p style="text-align:center"><br/>&nbsp;You are not logged in. <a href="//tools.wmflabs.org/xtools/?login" >Log in</a> with secure Wikimedia OAuth.<br/>If necessary, refresh your browser with F5 afterwards.<br />&nbsp;</p>';
	
//Show form if &article parameter is not set (or empty)
	if( $wt->loggedInUsername ) {
		
		$resArr = getCrossWikiMessage($wt, 'all', 50, $purge);
		
		$wt->statusLink['message'] = $resArr["list"];
		$msgArr = $resArr["msgArr"];
		
		uasort( $msgArr, function($al, $bl) {
			$a = $al["timestamp"];
			$b = $bl["timestamp"];
			if ($a == $b) { return 0; }
			return ($a < $b) ? 1 : -1;
		});
		
		#$perflog->stack[] = $msgArr;
		
		$list = '
				<tr>
				<th>Wiki</th>
				<th>Notification time</th>
				<th>Read time</th>
				<th>Message</th>
				</tr>
			';
		$items = array();
		foreach ($msgArr as $idx => $row){
			$ltp = ( $row["read"] ) ? "read" : "unread";
			
			$unread = ( $row["timestamp"] && !$row["read"] ) ? "danger" : "";
			
			$items[$ltp][] ='
					<tr class="'.$unread.'" >
					<td>'.$row["wiki"].'</td>
					<td style="white-space:nowrap" >'.$wt->dateFmt( $row["timestamp"], true ).'</td>
					<td style="white-space:nowrap" >'.$wt->dateFmt( $row["read"], true ).'</td>
					<td>'.$row["text"].'</td>
					</tr>
				';
		}
		$list .= implode( '', $items["unread"]) . implode( '', $items["read"]);
	}

	$wt->assign( 'reloadpurge', '<a title="reload & purge" href="//'.XTOOLS_BASE_WEB_DIR.'/echo/?purge=1" ><img height=15px src="//upload.wikimedia.org/wikipedia/commons/d/dd/Reload_Icon_Red.svg" /></a>'  );
	$wt->assign( 'list', $list );
	
unset( $list, $result, $items );	
$wt->showPage(true);
	

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){
	
	$templateResult = '
		<div class="panel panel-primary" style="text-align:center">
			<div class="panel-heading">
				<p class="xt-heading-top" >
					<span>XEcho</span>
					<small> &nbsp;&bull; Global Cross-Wiki Notifications</small>
				</p>
			</div>
			<p>
			<br />
			Show: Max. 50 per wiki. &nbsp;&nbsp; {$reloadpurge}
			</p>
			<div class="panel-body xt-panel-body-top" >	
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4  class="topcaption" >{#result#} <span class="showhide" onclick="javascript:switchShow( \'generalstats\', this )">[{#hide#}]</span></h4>
					</div>
					<div class="panel-body" id="generalstats">
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

