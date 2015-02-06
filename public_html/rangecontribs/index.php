<?php

//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );
	require_once( 'RangeContribs.php' );

//Load WebTool class
	$wt = new WebTool( 'rangecontribs' );
	$wt->setLimits();
	$wt->getPageTemplate( "form" );
	
	$wt->assign( 'defaultBegin', date('Y')."-01-01");
	$wt->content = str_replace('{#tosearch#}', 'CIDR /<br />List', $wt->content );
	
//Checks for alternative requests for compatibility (ips = legacy)
	$list  = $wgRequest->getText( 'ips' );
	$list  = $wgRequest->getText( 'list', $list ); 
	$list  = $wgRequest->getText( 'text', $list );
	
	$limit = $wgRequest->getVal( 'limit', '20');
	$display = $wgRequest->getVal('display');
	$begin = $wt->checkDate( $wgRequest->getVal('begin') );
	$end   = $wt->checkDate( $wgRequest->getVal('end') );
	$namespace = $wgRequest->getVal('namespace');
	
	
	$wi = $wt->wikiInfo;
		$lang  = $wi->lang;
		$wiki  = $wi->wiki;
		$domain = $wi->domain;

		
	if( !$list || !$wiki || !$lang ){
		$wt->showPage();
	}
	
	if( $begin == 'error' || $end == 'error'){
		$wt->toDie( 'invalid_date' );
	}

	
//Create exec object
	$dbr = $wt->loadDatabase( $lang, $wiki );
	$rc = new RangeContribs( $dbr, $wt, $list, $begin, $end, $limit, $namespace );
	

//Make output
	
	$listsum = makeListSum( $rc->getItems() ); 
	if ( $display != "bytime" ){
		$listbyName = makeListRevs( "byname", $rc->getContribs(), $wt->namespaces, $limit );
	}
	$listbyTime = makeListRevs( "bytime", $rc->getContribs(), $wt->namespaces, $limit );
	
	
//Output stuff	
	$wt->content = getPageTemplate( "result" );
	
	$wt->assign( "listsum", $listsum );
	$wt->assign( "listbyname", $listbyName );
	$wt->assign( "listbytime", $listbyTime );
	
	$wt->assign( "begin", $begin );
	$wt->assign( "end", $end );
	$wt->assign( "namespace", $namespace );
	$wt->assign( 'xtoolsbase', XTOOLS_BASE_WEB_DIR );
	$wt->assign( 'domain', $domain );
	$wt->assign( 'lang', $lang );
	$wt->assign( 'wiki', $wiki );
	
unset( $base, $ipList, $listbyName, $listbyTime, $site );
$wt->showPage();




function makeListSum( $items ){
	global $wt;
	
	$list = "";
	
	if( count($items["cidr"]) > 0 ){
		$list = "<table><tr>";
		foreach( $items["cidr"] as $i => $cidr ){
			$list .= '
				<td style="padding-right:15px;" >
				<table class="leantable table-condensed xt-table">
				<tr><td>CIDR: 	  </td><td>'.$i.'</td></tr>
				<tr><td>{#ip_start#}: </td><td>'.$cidr["cidrinfo"]["begin"].'</td></tr>
				<tr><td>{#ip_end#}:   </td><td>'.$cidr["cidrinfo"]["end"].'</td></tr>
				<tr><td>{#ip_number#}:</td><td>'.$cidr["cidrinfo"]["count"].'</td></tr>
				</table>
				</td>
			';
		}
		$list .= "</tr></table>";
	}
	
	$list .= '
			<table class="leantable table-condensed xt-table" >
			<tr><td>{#wiki#}:</td><td>{$domain}</td></tr>
			<tr><td>{#start#}:</td><td>{$begin}</td></tr>
			<tr><td>{#end#}:</td><td>{$end}</td></tr>
			<tr><td>{#namespace#}:</td><td>{$namespace}</td></tr>
			<table>
		';
	
	
	ksort( $items["byrange"] );
	
	foreach ( $items["byrange"] as $group => $item ){
		
		$header = '<p style="margin:0.5em 0.3em 0.2em 0.3em" ><b>'.ucfirst($group).':</b></p>';
		if ( isset($item["rangeinfo"]) ) {
			$range = $item["rangeinfo"];
			$header = "
				<p style=\"margin-bottom:0.5em \" ><b>Range: &nbsp;</b> $range->inetnum &nbsp; Provider: $range->netname &middot; $range->descr &middot; $range->country &nbsp;<img style='vertical-align:inherit;' src=../static/images/flags/png/".strtolower($range->country).".png /></p>
			";
		}
		$list .= $header;
		
		$list .= '<table class="leantable table-condensed xt-table" >';
		foreach ( $item["list"] as $user => $count ){
			
			$usernameurl = rawurlencode( $user );
			
			$list .= '
				<tr>
				<td><a href="#'.$usernameurl.'" >'.$user.'</a></td>
				<td class="tdnum" style="padding-left:1em; padding-right:1em" >'.$wt->numFmt( $count ).'</td>
				<td><small>
					<a href="//{$domain}/w/index.php?title=Special:Log&type=block&user=&page=User:'.$usernameurl.'&year=&month=-1&tagfilter=" >block log</a> &middot; 
					<a href="//{$xtoolsbase}/ec/?lang={$lang}&wiki={$wiki}&user='.$usernameurl.'" >edit counter</a> &middot; 
					<a href="//tools.wmflabs.org/guc/?user='.$usernameurl.'" >guc</a> &middot; 
				</td></small>
				</tr>
			';
		}
		$list .= "</table>";
	}
	
	return $list;
}

function makeListRevs( $display, $contribs, $namespaces, $limit ){
	global $perflog;
	#$perflog->add('mlist', $contribs); 	

#	if( count( $contribs ) == 0 ) { return "no results"; }

	if( $display == "byname" ){
		$res = $contribs["byname"];
		$nameheader = true; 
	}
	else {
		$res = $contribs["bytime"];
		$nameheader = false;
	}

	$c = 0;
	$list = "";
	$oldip = "";
	$seccount = 0;

	foreach ( $res as $ds => $sortkey ){
		$row = $contribs["data"][$ds];
		
		$ns = ($row['page_namespace'] == 0) ? "" : $namespaces['names'][ $row['page_namespace'] ].":";
		$title = str_replace("_", " ", $ns.$row['page_title'] );
		$urltitle = rawurlencode( str_replace(" ", "_", $title ) );
		$userurl = rawurlencode( $row['rev_user_text'] );
		$date = date('Y-m-d, H:i ', strtotime( $row['rev_timestamp'] ) );

		//create a new header if namespace changes
		if( $nameheader && ($oldip != $row['rev_user_text']) ){

			$list .= "<tr ><td colspan=8 ><h5 id='".$row['rev_user_text']."' style='font-size:1.2em; margin:15 0 5 0;'>";
			$list .= '<a href="//{$domain}/wiki/User:'.$userurl.'" >'.$row['rev_user_text'].'</a>';
			$list .= ' (<a href="//{$domain}/wiki/User_talk:'.$userurl.'" title="User talk:'.$row['rev_user_text'].'">talk</a>)';
			$list .= ' <span style="font-weight:normal"> &middot; total: '.$row["sum"].'</span>';
			$list .= '</h5></td></tr>';

			$oldip = $row['rev_user_text'];
			$seccount = 0;
		}
			
		$list .= "<tr>";
		$list .= '<td style="vertical-align:top;" >'.($seccount +1).'.</td>';
		$list .= '<td style="font-size:95%; white-space:nowrap; vertical-align:top;">'.$date.'</td> ';
		$list .= '<td style="white-space:nowrap; vertical-align:top;" > &middot; <a href="//{$domain}/wiki/User:'.$userurl.'" >'.$row['rev_user_text'].'</a> &middot; </td>';
		$list .= '<td style="white-space:nowrap; vertical-align:top;" >(<a href="//{$domain}/w/index.php?title='.$urltitle.'&amp;diff=prev&amp;oldid='.urlencode($row['rev_id']).'" title="'.$title.'">diff</a>)</td>';
		$list .= '<td style="white-space:nowrap; vertical-align:top;" >(<a href="//{$domain}/w/index.php?title='.$urltitle.'&amp;action=history" title="'.$title.'">hist</a>)</td>';
		//if( $row['rev_minor_edit'] == '1' ) { $list .= '<span class="minor">m</span>'; }
		$list .= '<td> &middot; <a href="//{$domain}/wiki/'.$urltitle.'" title="'.$title.'">'.$title."</a>‎ &middot; <small> (".htmlspecialchars($row['rev_comment']).")</small></td> ";
		$list .= "</tr>";

		$seccount++;
		if ( $nameheader && ($seccount == $limit && $row["sum"] > $limit) ){
			$list .= '<tr><td colspan=5 style="text-align:center; font-weight:bolder ">
					<a href="//{$xtoolsbase}/rangecontribs/?lang={$lang}&wiki={$wiki}&limit=300&display=bytime&ips='.$userurl.'" >-{#more#}-</a>
					</td></tr>';
		}

		$c++;
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
				<span>Range Contribs</span>
				<small><span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span></small>
			</p>
		</div>
		<div class="panel-body xt-panel-body-top" >	
			
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4  class="topcaption" >{#summary#} <span class="showhide" onclick="javascript:switchShow( \'generalstats\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="generalstats">
					{$listsum}
					<br />
				</div>
			</div>
			
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4  class="topcaption" >{#resultbytime#} <span class="showhide" onclick="javascript:switchShow( \'resultbytime\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="resultbytime">
					<table class="leantable table-condensed xt-table" >
						{$listbytime}
					</table>
				</div>
			</div>
			
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4  class="topcaption" >{#resultbyname#} <span class="showhide" onclick="javascript:switchShow( \'resultbyname\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="resultbyname">
					<table class="leantable table-condensed xt-table" >
						{$listbyname}
					</table>
				</div>
			</div>
			
		</div>
	</div>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; } 

}
