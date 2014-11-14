<?php
	
//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );

//Load WebTool class
	$wt = new WebTool( 'adminstats' );
	$wt->setLimits();
	$wt->getPageTemplate( "form" );

	$datenow = new DateTime();
	$datefromdefault = date_format($datenow->sub(new DateInterval("P100D")), "Y-m-d");
	$wt->assign( 'defaultBegin', $datefromdefault );
	
	$datefrom = $wgRequest->getVal('begin', null );
	$dateto = $wt->checkDate( $wgRequest->getVal('end') );
	
	$wi = $wt->getWikiInfo();
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;
	
		
		$perflog->add('domain', 0, $domain );
		$perflog->add('datefrom', 0, $datefrom );
		
//Show form if domain parameter is not set (or empty)
	if( !$domain || !$datefrom ) {
		$wt->showPage();
		exit;
	}
	
	if( $datefrom == 'default' ) {$datefrom = $datefromdefault; }
	$datefrom = $wt->checkDate( $datefrom );
	
	$datediff = '–';
	if ($datefrom){
		$dt = new DateTime( $dateto );
		$df = new DateTime( $datefrom );
		$datediff = $df->diff($dt, true)->format('%a').' '. $I18N->msg('days', array("variables"=>array(2)));
	}

	$dbr = $wt->loadDatabase($lang, $wiki);
	$list = getAdminStats( $dbr, $wi, $datefrom, $dateto );
	
	$wt->content = getPageTemplate( 'result' );
	$wt->assign( 'list', $list );
	$wt->assign( 'numcuradmins', $wt->numFmt( count($curAdmins) ) );
	$wt->assign( 'noactionadmins', $wt->numFmt( $noActionAdmin ) );
	$wt->assign( 'pctnoaction', $wt->numFmt( $pctnoaction, 1 ) );
	$wt->assign( 'domain', $domain );
	$wt->assign( 'datefrom', $wt->dateFmt($datefrom.' 00:00:00') );
	$wt->assign( 'numdays', $datediff);
	
unset($list, $curAdmins );
$wt->showPage();
	

function getCurrentAdmins( $wi ){
	global $wt, $perflog;
	$stime = microtime(true);
	
	$res = array();
	
	$apibase = "http://$wi->domain/w/api.php?";
	$data = array(
			"action" => "query",
			"list" => "allusers",
			"format" => "json",
			"augroup" => "sysop|bureaucrat|steward|oversight|checkuser",
			"auprop" => "groups|editcount",
			"aufrom" => "",
			"aulimit" => "500"
		);
	
	$continue = true;
	$i=0;
	while ( $continue ){
		if ($i >0 ) { $data["aufrom"] = $continue; }
		$apiret = json_decode( $wt->gethttp( $apibase.http_build_query( $data ) ) );
		$apiret2 = @$apiret->query->allusers;
		$continue = @$apiret->{'query-continue'}->allusers->aufrom;
		if( is_array($apiret2) ){
			foreach ( $apiret2 as $u => $obj ){
				$groups= array();
				if (in_array("sysop", $obj->groups)) {$groups[] = "A";}
				if (in_array("bureaucrat", $obj->groups)) { $groups[] = "B"; }
				if (in_array("steward", $obj->groups)) { $groups[] = "S" ; }
				if (in_array("checkuser", $obj->groups)) { $groups[] = "CU"; }
				if (in_array("oversight", $obj->groups)) { $groups[] = "OS"; }
				if (in_array("bot", $obj->groups)) { $groups[] = "Bot"; }
				$res[ $obj->name ] = array( "editcount" => $obj->editcount, "groups" => implode('/', $groups) );
			} 
		}
		
		$i++; if($i>20 ) break; 
#		$perflog->stack[] = $apiret;
	}
	#$perflog->stack[] = $i;
	$perflog->add('admins', (microtime(true) - $stime), '...' ); 
	
	return $res;
}
	
function getAdminStats( $dbr, $wi, $datefrom, $dateto ){
	global $wt, $perflog, $curAdmins, $noActionAdmin, $pctnoaction;
	
	$datefrom = str_replace(array("-", ":"), array("",""), $datefrom );
	$dateto = str_replace(array("-", ":"), array("",""), $dateto );
	$datefrom = ( $datefrom ) ? $datefrom : '1';
	$dateto = ( $dateto ) ? $dateto : '99999999';
	
	$curAdmins = getCurrentAdmins($wi);
	
	// Get admin ID's
	$query = "
		Select ug_user as user_id
		FROM user_groups 
		WHERE ug_group = 'sysop'
		UNION
		SELECT ufg_user as user_id
		FROM user_former_groups 
		WHERE ufg_group = 'sysop'
		";
	
	$res = $dbr->query( $query );
	
	foreach ($res as $i => $row ){
		$adminIdArr[] = $row["user_id"] ;
	}
	$adminIds = implode(',', $adminIdArr);
	
	
	// Get lokal pagetitles AfD (Q22897) and AnitVandalim (Q10817957)
	$dbrwd = $wt->loadDatabase(null, null, "wikidatawiki");
	$query = "
		SELECT ips_item_id, ips_site_page
		FROM wb_items_per_site
		WHERE ips_item_id IN ('22897', '10817957') and ips_site_id = 'dewiki'
		"; 
	$res = $dbrwd->query( $query );
	$pageAfD = "";
	$pageAiV = "";
	foreach ($res as $i => $row){
		$pagetitle = substr( $row["ips_site_id"], strpos( $row["ips_site_id"], ':') ); 
		if ($row["ips_item_id"]  == "22897" ) { $pageAfD = $pagetitle; }
		if ($row["ips_item_id"]  == "10817957" ) { $pageAiV = $pagetitle; }
	}
	
	$query = "
		SELECT user_name, user_id
		,SUM(IF( (log_type='delete'  AND log_action != 'restore'),1,0)) as mdelete
		,SUM(IF( (log_type='delete'  AND log_action  = 'restore'),1,0)) as mrestore
		,SUM(IF( (log_type='block'   AND log_action != 'unblock'),1,0)) as mblock
		,SUM(IF( (log_type='block'   AND log_action  = 'unblock'),1,0)) as munblock
		,SUM(IF( (log_type='protect' AND log_action !='unprotect'),1,0)) as mprotect
		,SUM(IF( (log_type='protect' AND log_action  ='unprotect'),1,0)) as munprotect
		,SUM(IF( log_type='rights',1,0)) as mrights
		,SUM(IF( log_type='import',1,0)) as mimport
		,SUM(IF(log_type !='',1,0)) as mtotal
		FROM logging_logindex
		JOIN user ON user_id = log_user
		WHERE  log_timestamp > '$datefrom' AND log_timestamp <= '$dateto'
			AND log_type IS NOT NULL 
			AND log_action IS NOT NULL
			AND log_type in ('block', 'delete', 'protect', 'import', 'rights') 
			/*AND log_user in ( $adminIds )*/
		GROUP BY user_name
		HAVING mdelete > 0 OR user_id in ( $adminIds )
		ORDER BY mtotal DESC
		
		";
	
	$res = $dbr->query( $query );
	
	$stime = microtime(true);
	
	
	
// 	$query = "
// 			SELECT rev_user_text, count(*) as count
// 			FROM revision_userindex
// 			JOIN page on page_id = rev_page
// 			WHERE page_namespace=4 and page_title like 'Löschkandidaten/%' 
// 				AND rev_user in ( $users )
// 				AND rev_timestamp > '$datefrom' AND rev_timestamp < '$dateto'
// 			GROUP BY rev_user_text
// 		";
	
// 	$lk = $dbr->query( $query );
	
	$perflog->add('admin_AfD', (microtime(true)-$stime), $lk );
	
	$list='
			<tr>
				<th>#</th>
				<th>Username</th>
				<th>grp</th>
				<th>links</th>
				<th>total</th>
				<th>delete</th>
				<th>restore</th>
				<th>(re)block</th>
				<th>unblock</th>
				<th>(re)protect</th>
				<th>unprotect</th>
				<th>import</th>
				<th>rights</th>
			</tr>
		';
	$listedAdmins = array();
	foreach ($res as $i => $row){
		$list .='
			<tr>
			<td>'.($i+1).'.</td>
			<td style="max-width:150px;"><a style="max-width:120px;" href="//{$domain}/wiki/User:'.$row["user_name"].'">'.$row["user_name"].'</a></td>
			<td>'.@$curAdmins[ $row["user_name"] ]["groups"].'</td>
			<td style="white-space:nowrap;">
				<a title="Edit Counter" href="//tools.wmflabs.org/xtools/ec/?project={$domain}&user='.$row["user_name"].'" >ec</a> &middot; 
				<a title="Global User Contributions" href="//tools.wmflabs.org/guc/?user='.$row["user_name"].' " >guc</a> &middot;
				<a title="Log" href="//{$domain}/w/index.php?title=Special:Log&user='.$row["user_name"].' " >log</a>
			</td>
			<td class="tdnum" >'.$wt->numFmt( $row["mtotal"] ).'</td>
			<td class="tdnum" >'.$wt->numFmt( $row["mdelete"] ).'</td>
			<td class="tdnum" >'.$wt->numFmt( $row["mrestore"] ).'</td>
			<td class="tdnum" >'.$wt->numFmt( $row["mblock"] ).'</td>
			<td class="tdnum" >'.$wt->numFmt( $row["munblock"] ).'</td>
			<td class="tdnum" >'.$wt->numFmt( $row["mprotect"] ).'</td>
			<td class="tdnum" >'.$wt->numFmt( $row["munprotect"] ).'</td>
			<td class="tdnum" >'.$wt->numFmt( $row["mimport"] ).'</td>
			<td class="tdnum" >'.$wt->numFmt( $row["mrights"] ).'</td>
			</tr>
		';
		$listedAdmins[] = $row["user_name"];
	}
	$u=$i+2;
	$noActionAdmin = 0;
	foreach ( $curAdmins as $user => $row){
		if ( in_array($user, $listedAdmins) ) continue;
		$list .='
			<tr>
			<td>'.$u.'.</td>
			<td><a href="//{$domain}/wiki/User:'.$user.'">'.$user.'</a></td>
			<td>'.@$curAdmins[ $user ]["groups"].'</td>
			<td>
				<a title="Edit Counter" href="//tools.wmflabs.org/xtools/ec/?project={$domain}&user='.$user.'" >ec</a> &middot;
				<a title="Global User Contributions" href="//tools.wmflabs.org/guc/?user='.$user.' " >guc</a> &middot;
				<a title="Log" href="//{$domain}/w/index.php?title=Special:Log&user='.$user.' " >log</a>
			</td>
			<td class="tdnum">0</td>
			<td class="tdnum">0</td>
			<td class="tdnum">0</td>
			<td class="tdnum">0</td>
			<td class="tdnum">0</td>
			<td class="tdnum">0</td>
			<td class="tdnum">0</td>
			<td class="tdnum">0</td>
			<td class="tdnum">0</td>
			</tr>
		';
		$u++;
		$noActionAdmin++;
	}
	$pctnoaction = ($noActionAdmin / $u) *100 ;
	
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
				<span>AdminStats</span>
				<small><span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span></small>
			</p>
		</div>
			
		<div class="panel-body xt-panel-body-top"  >
			<p>
			<table class="table-condensed xt-table" style="text-align:left">
				<tr><td>{#start#}:</td><td>{$datefrom} &middot; ({$numdays})</td></tr>
				<tr><td>Log types:</td><td>delete, block, protect, import, rights</td></tr>
				<tr><td>Current Admins (grp):</td><td><a href="//{$domain}/w/index.php?title=Special:ListUsers&group=sysop&creationSort=1&limit=50" >{$numcuradmins}</a></td></tr>
				<tr><td>Admins without action:</td><td>{$noactionadmins} ({$pctnoaction}%)</td></tr>
			</table>
			</p>
			<br />

			<div class="panel panel-default">
				<div class="panel-heading">
					<h4  class="topcaption" >{#summary#} <span class="showhide" onclick="javascript:switchShow( \'generalstats\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="generalstats">
					<table class="table-striped table-condensed table-hover sortable xt-table">
						{$list}
					</table>
				</div>
			</div>
		</div>
	</div>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
}
		
		
		