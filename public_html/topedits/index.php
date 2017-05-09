<?php

//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );
	require_once( 'Counter.php' );
	require_once( PEACHY_BASE_SYS_DIR . '/Peachy/Init.php' );

//Load WebTool class
	$wt = new WebTool( 'topedits' );
	$wt->setLimits();
	$wt->getPageTemplate( 'form' );

	$namespace = $wgRequest->getVal('namespace');

	//kompatibility
	$page = $wgRequest->getVal('page');
	$page = $wgRequest->getVal('article', $page );

	$wi = $wt->wikiInfo;
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;

	$uinput = explode("|", $wgRequest->getVal('user') );
	foreach ($uinput as $uitem){
		$tmpui = $wt->getUserInfo($lang, $wiki, $uitem );
		if ( $tmpui->userid || $tmpui->isIP ){
			$users["list"][] = $tmpui;
			$users["names"][] = "'".$tmpui->userDb."'";
		}
	}
	$perflog->stack[] = $users;

	$ui = $users["list"][0];
		$user = $ui->user;


//Show form if &article parameter is not set (or empty)
	if( !$user && ( !$page || !$lang || !$wiki || (strval($namespace) == "")  ) ) {
		$wt->showPage();
	}


	$dbr = $wt->loadDatabase( $lang, $wiki );


	if ($page){

		$site = $wt->loadPeachy( $lang, $wiki );
		try{
			$pageObj = new Page( $site , $page );
			$nsname = $pageObj->get_namespace(false);
			$nsid = $pageObj->get_namespace();
			$nscolon = ($nsid) ? $nsname.":" : "";
			$page_title = $pageObj->get_title(false);
			$page_id = $pageObj->get_id();
		}
		catch(Exception $e){
			$wt->error = $e->getMessage()." (topedits::pageObj)";
			$wt->showPage();
		}

		if ( !$page_id || $page_id == 0 )
				$wt->toDie('nosuchpage', $page." ($domain)");

		$list = getPageEditsPerUser($dbr, $page_id, $domain, $users, $wi );
		$wt->content = getPageTemplate( 'resultpage' );
		$userprefix='';
	}
	else{
		$cnt = new Counter( $dbr, $user, $domain, true  );

		if ( $cnt->optin ) {
			$wt->content = getPageTemplate( 'resultns' );
			$nscolon = $page_title = "";

			$nsnames = $cnt->getNamespaces();
			$list = getTopEditsByNamespace( $dbr, $wi, $ui, $nsnames, $namespace );
		}
		else {
			$nsnames = $cnt->getNamespaces();
			$wt->content = getPageTemplate( 'resultns' );
			$list = $I18N->msg( "nograph", array( "variables"=> array( $cnt->getOptinLinkLocal(), $cnt->getOptinLinkGlobal() ) ))
				. "<br /> " . $I18N->msg('nograph2', array( "variables" => array($wt->linkOauthHelp) ));
		}
		$userprefix = $nsnames["names"][2];
	}

	$wt->assign( 'list', $list );
	$wt->assign( 'page', $nscolon.$page_title );
	$wt->assign( 'urlencodedpage', rawurlencode( str_replace(" ", "_", $nscolon.$page_title ) ) );
	$wt->assign( 'xtoolsbase', XTOOLS_BASE_WEB_DIR );

	$wt->assign( 'lang', $lang );
	$wt->assign( 'wiki', $wiki );
	$wt->assign( 'domain', $domain );
	$wt->assign( 'username', $user );
	$wt->assign( 'userprefix', $userprefix );
	$wt->assign( 'usernameurl', $ui->userUrl );

unset( $cnt, $list );
$wt->showPage();



/**************************************** stand alone functions ****************************************
 *
*/

function getPageEditsPerUser( $dbr, $page_id, $domain, $users, $wi ){
	global $wt, $perflog;

	$revs = new stdClass();
		$user = array();
		$parent = array();

	$where = " rev_user_text in (".implode(",", $users["names"]). ") AND rev_page = '$page_id' ";

	//Get da revisions
	$query = "
		SELECT /* SLOW_OK */  rev_id, rev_parent_id, rev_user_text, rev_timestamp, rev_minor_edit, rev_len, rev_comment
		FROM revision_userindex
		WHERE  $where
		ORDER BY rev_timestamp DESC
	";

	$revs->user = $dbr->query ( $query );


	//Get all da parentID's revs for length calc
	$query = "
		SELECT b.rev_id, b.rev_len
		FROM revision_userindex as b
		WHERE  rev_id in  ( SELECT rev_parent_id from revision_userindex WHERE  $where )
		ORDER BY b.rev_id
	";

	$res = $dbr->query ( $query );

	foreach ( $res as $i => $row ){
		$revs->parent[ $row["rev_id"] ] = $row["rev_len"];
	}
	unset($res);

	$totaladd = 0;
	$totaldelete = 0;

	$list = '<table class="table-striped sortable table-condensed xt-table" >';
	$list .= '
			<tr>
			<th>{#Date#}</th>
			<th>{#username#}</th>
			<th>Diff</th>
			<th>Hist</th>
			<th>Size</th>
			<th>{#comment#}</th>
			</tr>
		';
	foreach ( $revs->user as $i => $row ){

		$date = date('Y-m-d, H:i ', strtotime( $row['rev_timestamp']) );
		$year = date('Y', strtotime( $row['rev_timestamp']) );
		$month = date('m', strtotime( $row['rev_timestamp']) );
		$minor = ( $row['rev_minor_edit'] == '1' ) ? '<span class="minor" >m</span>' : '';
		$difflen = $row["rev_len"] - @$revs->parent[ $row["rev_parent_id"] ];

		if ( $difflen >= 0 ) {
			$color = "green";
			$totaladd += $difflen;
		}
		else {
			$color = "red";
			$totaldelete += $difflen;
		}

		$list .= '
			<tr>
			<td style="font-size:95%; white-space:nowrap;">'.$date.' &middot; </td>
			<td>'.$row["rev_user_text"].'</td>
			<td>(<a href="//'.$domain.'/w/index.php?title={$urlencodedpage}&amp;diff=prev&amp;oldid='.urlencode($row['rev_id']).'" >diff</a>)</td>
			<td>(<a href="//'.$domain.'/w/index.php?title={$urlencodedpage}&amp;action=history&amp;year='.$year.'&amp;month='.$month.' " >hist</a>)</td>
			<td style="text-align:right;padding-right:5px;color:'.$color.'" >'.$wt->numFmt($difflen).'</td>
			<td style="font-size:85%" >'.$row['rev_comment'].'</td>
			</tr>
		';
		if (!isset($numedits[ $row["rev_user_text"] ]) ) {$numedits[ $row["rev_user_text"] ] = 0; }
		$numedits[ $row["rev_user_text"] ]++;
	}
	$list .= "</table>";

	foreach($users["list"] as $i => $ui){
		$userlinks[] = '
				<a href="//'.$domain.'/wiki/User:'.$ui->userUrl.'" >'.$ui->user.'</a>
				<a title="Edit Counter" href="//'.XTOOLS_BASE_WEB_DIR."-ec/?user=$ui->userUrl&amp;lang=$wi->lang&amp;wiki=$wi->wiki".'" >(ec)  </a>
				<span>'.$numedits[$ui->user].'&nbsp;</span>
			';
	}

	$totaledits = array_sum($numedits);
	$info = '
			<table class="table-condensed xt-table" >
			<tr><td>{#users#}:</td><td colspan=4 >'.implode(' &bull; ', $userlinks).'</td></tr>
			<tr><td>{#count#}:</td><td><span class="tdnum" colspan=1 >'.$totaledits.'</span></td></tr>
			<tr><td>{#added#}:</td><td><span class="tdnum" colspan=1 style="color:green" >+'.$wt->numFmt($totaladd).'</span></td></tr>
			<tr><td>{#deleted#}:</td><td><span class="tdnum" colspan=1 style="color:red" >'.$wt->numFmt($totaldelete).'</span></td></tr>
			</table>
			<br />
		';

	return $info.$list;
}


function getTopEditsByNamespace( $dbr, $wi, $ui, $nsnames, $namespace){

	if ( !$ui->userDb ) return null;

	$namespace = intval($namespace);
	$domain = $wi->domain;
	$lang = $wi->lang;
	$wiki = $wi->wiki;
	$user = $ui->user;

	$query = "
		SELECT /* SLOW_OK */ page_namespace, page_title, page_is_redirect, count(page_title) as count
		FROM page
		JOIN revision_userindex ON page_id = rev_page
		WHERE rev_user_text = '$ui->userDb' AND page_namespace = '$namespace'
		GROUP BY page_namespace, page_title
		ORDER BY count DESC
		LIMIT 100
	";

	$res = $dbr->query( $query );

	$list = '<table class="table-condensed xt-table" ><tr><td colspan=22 ><h3>' . $nsnames['names'][$namespace] . '</h3></td></tr>';

	foreach ( $res as $i => $row ) {

		$nscolon = '';
		if( $row["page_namespace"] != 0 ) {
			$nscolon = $nsnames['names'][ $row["page_namespace"] ].":";
		}

		$articleencoded = rawurlencode( str_replace(" ", "_", $nscolon.$row["page_title"] ) );
		$articleencoded = str_replace( array('%2F', '%3A'), array('/', ':'), $articleencoded );
		$article = str_replace("_", " ", $nscolon.$row["page_title"] );

		$list .= "
			<tr>
			<td class=tdnum >".$row["count"]."</td>
			<td><a href=\"//$domain/wiki/$articleencoded\" >$article</a></td>
			<td><a href=\"//$domain/w/index.php?title=Special:Log&type=&page=$articleencoded\" ><small>log</small></a> &middot; </td>
			<td><a href=\"//".XTOOLS_BASE_WEB_DIR."/articleinfo/?lang=$lang&wiki=$wiki&page=$articleencoded\" ><small>page history</small></a> &middot; </td>
			<td><a href=\"//".XTOOLS_BASE_WEB_DIR."/topedits/?lang=$lang&wiki=$wiki&user=${user}&page=$articleencoded\" ><small>topedits</small></a></td>
			</tr>
		";
	}

	$list .= "</table>";

	return  $list;
}

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '..old..';

	$templateResultNS = '

	<div class="panel panel-primary" style="text-align:center">
		<div class="panel-heading">
			<p class="xt-heading-top" >
				<a href="//{$domain}/wiki/User:{$usernameurl$}">{$username$}</a>
				<span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span>
			</p>
		</div>
		<div class="panel-body xt-panel-body-top" >
			<p>
				<a href="//{$domain}/w/index.php?title=Special%3ALog&type=block&user=&page=User%3A{$usernameurl}&year=&month=-1&tagfilter=" >Block log</a> &middot;
				<a href="//tools.wmflabs.org/xtools-ec/?user={$usernameurl}&lang={$lang}&wiki={$wiki}" >Edit Counter</a> &middot;
				<a href="//tools.wmflabs.org/guc/?user={$usernameurl}" >Global user contributions</a> &middot;
				<a href="//tools.wmflabs.org/pageviews/?project={$lang}.{$wiki}.org&pages={$userprefix}:{$usernameurl}" >Userpage pageviews</a>
			</p>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4>{#topedits_per_namespace#} <span class="showhide" onclick="javascript:switchShow( \'nstotals\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="nstotals">
					{$list}
				</div>
			</div>
		</div>
	</div>
	';

	$templateResultPage = '

	<div class="panel panel-primary" style="text-align:center">
		<div class="panel-heading">
			<p class="xt-heading-top" >
				<a href="//{$domain}/wiki/{$urlencodedpage}">{$page}</a>
				<span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span>
			</p>
		</div>

		<div class="panel-body xt-panel-body-top" >
			<p>
				<a href="//{$domain}/w/index.php?title=Special:Log&type=&page={$urlencodedpage}" >Page log</a> &middot;
				<a href="//{$xtoolsbase}-articleinfo/?article={$urlencodedpage}&amp;lang={$lang}&amp;wiki={$wiki}" >Page history</a>
			</p>

			<div class="panel panel-default">
				<div class="panel-heading">
					<h4>{#edits_per_page#} <span class="showhide" onclick="javascript:switchShow( \'nstotals\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="nstotals">
					{$list}
				</div>
			</div>
		</div>
	</div>
	';

	if( $type == "form" ) { return $templateForm; }
	if( $type == "resultns" ) { return $templateResultNS; }
	if( $type == "resultpage" ) { return $templateResultPage; }

}
