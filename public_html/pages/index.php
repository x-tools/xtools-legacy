<?php

//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );
	require_once( 'Counter.php' );
	require_once( 'Graph.php' );
	
//Load WebTool class
	$wt = new WebTool( 'pages' );
	$wt->setLimits();
	$wt->getPageTemplate( 'form' );


	$namespace = $wgRequest->getVal('namespace');
	$redirects = $wgRequest->getVal('redirects');
	$limit = intval( $wgRequest->getVal('limit', 100) );
	$movedeletes = $wgRequest->getBool('movedeletes', false);
	
	$wi = $wt->wikiInfo;
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;

	$ui = $wt->getUserInfo();
		$user = $ui->user;

//Show form if &article parameter is not set (or empty)
	if( !$user || !$wiki || !$lang ) {
		$wt->showPage();
	}
	
//Get username & userid, quit if not exist
	
	$ttl = 120;
	$hash = "xtoolsPages".XTOOLS_REDIS_FLUSH_TOKEN.'002'.hash('crc32', $lang.$wiki.$user);
	$hash2 = "xtoolsPages".XTOOLS_REDIS_FLUSH_TOKEN.'002'.hash('crc32', $user.$domain.$namespace.$redirects.$movedeletes);
	$lc = $redis->get($hash);
	$lc2 = $redis->get($hash2);
	
	if ($lc === false || $lc2 === false){
		$dbr = $wt->loadDatabase( $lang, $wiki );
		$cnt = new Counter( $dbr, $user, $domain, true );
		$items = $cnt->getCreatedPages( $dbr, $ui, $domain, $namespace, $redirects, $movedeletes );
		if( $ui->editcount > 100000 ) { $ttl = 1800; }
		if( $ui->editcount > 300000 ) { $ttl = 86400; }
		if( !$cnt->error && is_array($items) ) { 
			$redis->setex( $hash, $ttl, serialize( $cnt ) );
			$redis->setex( $hash2, $ttl, serialize( $items ) );
		}
	}
	else{
		$cnt = unserialize( $lc );
		$items = unserialize( $lc2 );
		unset( $lc, $lc2 );
		$perflog->add('Pages', 0, 'from Redis');
	}
	
	#$perflog->add('items', 0, $items );
//Construct output
	
	$result = makeList( $items, $wt->namespaces, xGraph::GetColorList(), $wi, $ui, $namespace, $redirects, $limit );
	
//Output stuff
	$filtertextNS = ( $namespace == "all" ) ? $I18N->msg('all') : @$wt->namespaces["names"][ $namespace ]." ($namespace)";
	
	$wt->content = getPageTemplate( 'result' );
		$wt->assign( 'username', $ui->user );
		$wt->assign( 'lang', $lang );
		$wt->assign( 'wiki', $wiki );
		$wt->assign( 'usernameurl', $ui->userUrl );
		$wt->assign( 'userprefix', $wt->namespaces["names"][2] );
		$wt->assign( 'xtoolsbase', XTOOLS_BASE_WEB_DIR );
		$wt->assign( 'domain', $wi->domain ); 
		$wt->assign( "redirFilter", $I18N->msg('redirfilter_'.$redirects ) );
		$wt->assign( "nsFilter", $filtertextNS );
		$wt->assign( "namespace_overview", $result->listnamespaces );
		$wt->assign( "nschart", $result->nschart );
		$wt->assign( "resultDetails", $result->list );

unset( $cnt, $items, $result );
$wt->showPage();


/**************************************** stand alone functions ****************************************
 *
*/
	
function makeList( $items , $nsnames, $nscolors, $wi, $ui, $namespace, $redirects, $limit ){
	global $wt;
#print_r($nsnames);
	$lang = $wi->lang;
	$wiki = $wi->wiki;
	$domain = $wi->domain;
	
	$rowLimit = $limit; //( $namespace == "all" ) ? 100 : 5000;
	
	$result = new stdClass(
			$filter 	 = null,
			$namespaces  = null,
			$list 		 = null
	);

	$currentNamespace = "-1";
	$currentNumber = 0;

	foreach ( $items as $i => $item ){
		
		$page 	  = str_replace("_", " ", $item["page_title"]);
		$date 	  = date("Y-m-d", strtotime($item["timestamp"]));
		$ns 	  = $item["namespace"];
		$prefix   = ($ns) ? $nsnames["names"][$ns].":" : "";
		$pageurl  = rawurlencode( str_replace(" ", "_", $prefix.$item["page_title"] ) );
		$redirect = ( $item["page_is_redirect"] == 1 ) ? "<small> &middot; (redirect)</small>" : "";
		$deleted  = ( $item["type"] == "arc" ) ? "<small style='color:red' > &middot; ({#deleted#}) </small>" : "";
	
	
		//create a new header if namespace changes
		if( $ns != $currentNamespace){
	
			$result->list .= "<tr ><td colspan=22 ><h3 id=$ns >".$nsnames["names"][$ns]."</h3></td></tr>";
			$result->namespaces[$ns]["name"] = $nsnames["names"][$ns];
	
			$currentNamespace = $ns;
			$currentNumber = 0;
			$currentLimit = false;
		}
		if ( !isset($result->namespaces[$ns]["num"]) ) { $result->namespaces[$ns]["num"] = 0 ; }
		if ( !isset($result->namespaces[$ns]["redir"]) ) { $result->namespaces[$ns]["redir"] = 0 ; }
		if ( !isset($result->namespaces[$ns]["deleted"]) ) { $result->namespaces[$ns]["deleted"] = 0 ; }
		
		$result->namespaces[$ns]["num"] += 1;
		if ($redirect) { $result->namespaces[$ns]["redir"]  += 1; }
		if ($deleted) { $result->namespaces[$ns]["deleted"]  += 1; }
		
		$currentNumber++;
		
		if ( $currentNumber > $rowLimit ){
			if ( $currentLimit ) { continue; }
			$newlimit = 10 * $limit;	
			$result->list .= '
					<tr><td colspan=22 style="padding-left:50px; ">
					<a href="//'.XTOOLS_BASE_WEB_DIR."/pages/?user=$ui->userUrl&lang=$lang&wiki=$wiki&namespace=$ns&redirects=$redirects&limit=$newlimit".'" ><strong>-{#more#}-</strong></a>
					</td></tr>
				';
			$currentLimit = true;
		}
		else{

			$result->list .= "
					<tr>
					<td>$currentNumber.</td>
					<td style='max-width:50%; white-space:wrap; word-wrap:break-word' ><a href=\"//$domain/wiki/$pageurl?redirect=no\" >$page</a> $redirect $deleted</td>
					<td style='white-space: nowrap; font-size:95%; padding-right:10px;' >$date</td>
					<td style='white-space: nowrap' ><a href=\"//$domain/w/index.php?title=Special:Log&type=&page=$pageurl\" ><small>log</small></a> &middot; </td>
					<td style='white-space: nowrap' ><a href=\"//".XTOOLS_BASE_WEB_DIR."/articleinfo/?lang=$lang&wiki=$wiki&page=$pageurl\" ><small>page history</small></a> &middot; </td>
					<td style='white-space: nowrap' ><a href=\"//".XTOOLS_BASE_WEB_DIR."/topedits/?lang=$lang&wiki=$wiki&user=$ui->userUrl&page=$pageurl\" ><small>topedits</small></a></td>
					
					</tr>
				";
		}
	}

	$result->filterns = $namespace;
	$result->filterredir = $redirects;
	$result->total = count($items);
	unset($items, $nsnames);

	//make serialized lists for graphics & toptable
	$sum["num"] = 0;
	$sum["redir"] = 0;
	$sum["deleted"] = 0;
	$chLabels = array();
	$chValues = array();
	$chColors = array();
	
	foreach ( $result->namespaces as $num => $ns ){
			
		$result->listnamespaces .='
			<tr>
			<td style="padding-right:10px">
				<span class=legendicon style="background-color:'.$nscolors[$num].'"> </span>
				<a href="#'.$num.'" >'.$ns["name"].'</a>
			</td>
			<td class=tdnum >'.$ns["num"].'</td>
			<td class=tdnum >'.$ns["redir"].'</td>
			<td class=tdnum >'.$ns["deleted"].'</td>
			</tr>
		';
		$sum["num"] += isset($ns["num"]) ? $ns["num"] : 0;
		$sum["redir"] += isset($ns["redir"]) ? $ns["redir"] : 0;
		$sum["deleted"] += isset($ns["deleted"]) ? $ns["deleted"] : 0;
		
		$chValues[$num] = $ns["num"];
	}
	$result->listnamespaces .='
			<tr>
			<td style="border-top:3px double silver;" ></td>
			<td class=tdnum style="border-top:3px double silver" ><strong>'.$wt->numFmt( $sum["num"], 0, true ).'</strong></td>
			<td class=tdnum style="border-top:3px double silver" >'.$wt->numFmt( $sum["redir"], 0, true ).'</td>
			<td class=tdnum style="border-top:3px double silver" >'.$wt->numFmt( $sum["deleted"], 0, true ).'</td>
			</tr>
			';
	
	$result->nschart = '<img height="140px" src="'.xGraph::makePieGoogle( $chValues ).'" alt="some graph" />';

	return $result;
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
				<a href="http://{$domain}/wiki/User:{$usernameurl$}">{$username$}</a>
				<small><span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span></small>
			</p>
		</div>
		<div class="panel-body xt-panel-body-top" >	
			<p>
				<a href="//{$domain}/w/index.php?title=Special%3ALog&type=block&user=&page=User%3A{$usernameurl}&year=&month=-1&tagfilter=" >block log</a> &middot;
				<a href="//tools.wmflabs.org/xtools-ec/?user={$usernameurl}&lang={$lang}&wiki={$wiki}" >Edit Counter</a> &middot;
				<a href="//tools.wmflabs.org/guc/?user={$usernameurl}" >Global user contributions</a> &middot;
				<a href="//tools.wmflabs.org/wikiviewstats/?lang={$lang}&wiki={$wiki}&page={$userprefix}:{$usernameurl}*" >Pageviews in userspace</a> &middot;
			</p>
	
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4>{#namespacetotals#} <span class="showhide" onclick="javascript:switchShow( \'nstotals\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="nstotals">
					<p style="margin-top: 0px;" >
					<table class="table-condensed xt-table">
						<tr><td>{#namespace#}:</td><td>{$nsFilter}</td></tr> 
						<tr><td>{#redirects#}:</td><td>{$redirFilter}</td></tr>
					</table>
					</p>
					<table>
						<tr>
						<td>
						<table class="leantable table-condensed xt-table"  >
							<tr>
							<th>{#namespace#}</th>
							<th>{#pages#}</th>
							<th style="padding_left:5px">{#redirects#}</th>
							<th style="padding_left:5px">{#deleted#}</th>
							</tr>
							{$namespace_overview}
						</table>
						</td>
						<td style="padding-left:50px;">
							{$nschart}
						</td>
						</tr>
					</table>
					<br />
				</div>
			</div>
				
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4>{#pages_created#} <span class="showhide" onclick="javascript:switchShow( \'pagescreated\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="pagescreated">
					<table class="table-condensed xt-table" >
						{$resultDetails}
					</table>
				</div>
			</div>
		</div>
	</div>
	';
	
	
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }

}
