<?php

//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );
	require_once( 'RFAde.php' );
	require_once( 'Graph.php' );
	require_once( PEACHY_BASE_SYS_DIR . '/Peachy/Init.php' );

//Load WebTool class
	$wt = new WebTool( 'rfap' );
	$wt->setLimits();
	$wt->getPageTemplate( 'form' );

	$wt->assign( 'optionsProject', '<option selected value ="en.wikipedia.org">en.wikipedia</option><option value = "de.wikipedia.org" >de.wikipedia</option>' );
	
	$wi = $wt->wikiInfo;
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;
	
	$ui = $wt->getUserInfo( $lang, $wiki );
		$user = $ui->user;
		
	

//Show form if username is not set (or empty)
	if( !$user || !$lang || !$domain ) {
		$wt->showPage();
	}
	
//Check if the user is an IP address
	if( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $name ) ) {
		$wt->error = "User cannot be an IP.";
		$wt->showPage();
	}
	
	$rfa['en'] = 'Requests_for_adminship';
	$rfa['de'] = 'Adminkandidaturen';
	$rfb['en'] = 'Requests_for_bureaucratship';
	$rfb['de'] = 'Bürokratenkandidaturen';
	

	
// Calculate all the things	
	$dbr = $wt->loadDatabase( $lang, 'wikipedia' );
	$pgVerbose = array();
	$site = Peachy::newWiki( null, null, null, "http://$wi->domain/w/api.php" );
	
	$votes = get_rfap( $dbr, $site, $domain, $user, $rfa[ $lang ] );
	$listAdmin = makeList( $votes, $rfa[ $lang ]);

	$votes = get_rfap( $dbr, $site, $domain, $user, $rfb[ $lang ] );
	$listBureaucrat = makeList( $votes, $rfb[ $lang ] );
	
	$wt->content = getPageTemplate( 'result' );
		$wt->assign( 'listadmin', $listAdmin );
		$wt->assign( 'listbureaucrat', $listBureaucrat );
		$wt->assign( 'rfa', str_replace('_', ' ', $rfa[$lang] ) );
		$wt->assign( 'rfb', str_replace('_', ' ', $rfb[$lang] ) );
		
		$wt->assign( 'username', $user );
		$wt->assign( 'usernameurl', rawurlencode($user) );
		$wt->assign( 'domain', $domain );
		$wt->assign( 'wiki', $lang );
		$wt->assign( 'lang', $wiki );
	
	
unset( $output, $site, $votes );
$wt->showPage();	




// Generate the output
function makeList( $votes, $aorb ){
	global $I18N;
	
	$output = '<table><tr><td><p>{#considered_usernames#}:</p><ul style="padding-top:5px;">';
	foreach ( $votes["altnames"] as $i => $altname ){
		$output .= '<li><a href="//{$domain}/wiki/User:'.$altname.'" >'.$altname.'</a></li> ';
	}
	$output .= '</ul>';
	
	$total = count($votes["support"]) + count($votes["oppose"]) + count($votes["neutral"]) + count($votes["unknown"]);
	$rfxpages = str_replace("_", " ", ucfirst($aorb) );
	$output .= '
		<span>'.$I18N->msg('vote_msg', array("variables" => array( $votes["altnames"][0], $total, $rfxpages ) ) ).'</span><br />
		<span> 
			{#support#}: '.count($votes["support"]).',  
			{#oppose#}: '.count($votes["oppose"]).', 
			{#neutral#}: '.count($votes["neutral"]).', 
			{#unknown#}: '.count($votes["unknown"]).'
		</span></td>
	  ';
	
	$chdata[] = count($votes["support"]);
	$chdata[] = count($votes["oppose"]);
	$chdata[] = count($votes["neutral"]);
	$chdata[] = count($votes["unknown"]);
	
	$labels = array( $I18N->msg('support'), $I18N->msg('oppose'), $I18N->msg('neutral'), $I18N->msg('unknown') );
	$colors = array( '55FF55', 'FF5555', 'CEC7C7', 'E6E68A' );
	
	$output .= '<td><img height="" src="'.xGraph::makeMiniPie($chdata, $labels, $colors).' " alt="chart" /></td>';
	$output .= '</tr></table><br />';
	
	foreach ( $votes as $type => $voteresults ){
		
		if ( $type == "altnames" ){ continue; }

		$output .= '
				<h4>'.$I18N->msg( ucfirst($type) ).'</h4>
				<table class="leantable tble-condensed, xt-table">
			';
		
		foreach ( $voteresults as $i => $item ){
			$pagetitle = str_replace('_', ' ', preg_replace( '/^.*\/(.*)$/', '\1', $item["page"] ));
			$output .= '
					<tr>
					<td>'.($i+1).'. &nbsp; </td>
					<td style="font-size:90%; padding-right:1em" >'.$item["startdate"].'</td>
					<td style="font-size:90%" >(<span style="color:green">'.$item["pro"].'</span>, <span style="color:red">'.$item["contra"].'</span>, <span style="color:gray">'.$item["neutral"].'</span>)</td>
					<td style="padding-left:1em" ><a href="//{$domain}/wiki/'.$item["page"].'" >'.$pagetitle.'</a></td>
					</tr>
				';
		}
		$output .= '</table>';
	}

	return $output;
}
	
	
function get_rfap( &$dbr, $site, $domain, $name, $aorb){

	$output = array(
			"altnames" => array(),
			"support"  => array(),
			"oppose"   => array(),
			"neutral"  => array(),
			"unknown"  => array(),
			"dupes"    => array(),
	);

	// Get alternative names
	$output["altnames"][] = $name;
	
	$sql_aorb = $dbr->strencode($aorb);
	$sql_name = $dbr->strencode($name);
	
	$query = "
		SELECT pl_from , (select b.page_title from page as b where b.page_id = pl_from) as altname
		FROM page
		JOIN pagelinks on pl_from=page_id and pl_namespace=page_namespace
		WHERE page_is_redirect = 1 AND page_namespace = 2 AND  pl_title = '$sql_name'
	";

	$result = $dbr->query( $query );

	foreach ( $result as $alternatives ){
		$output["altnames"][] = $alternatives["altname"];
	}
	unset( $result );

	if ( $domain == "en.wikipedia.org" ){
	// Get all pages where the user has voted
		$query = "
			SELECT page_latest, rev_timestamp, page_title, COUNT(*)
			FROM revision_userindex
			JOIN page on page_id = rev_page
			WHERE rev_user_text = '$sql_name'
			AND page_namespace = '4'
			AND page_title LIKE '".$sql_aorb."/%'
			AND page_title != 'Requests_for_adminship/RfA_and_RfB_Report'
			AND page_title != 'Requests_for_adminship/BAG'
			AND page_title NOT LIKE 'Requests_for_adminship/Nomination_cabal%'
			AND page_title != 'Requests_for_adminship/Front_matter'
			AND page_title != 'Requests_for_adminship/RfB_bar'
			AND page_title NOT LIKE 'Requests_for_adminship/%/%'
			AND page_title != 'Requests_for_adminship/nominate'
			AND page_title != 'Requests_for_adminship/desysop_poll'
			AND page_title != 'Requests_for_adminship/Draft'
			AND page_title != 'Requests_for_adminship/Header'
			AND page_title != 'Requests_for_adminship/?'
			AND page_title != 'Requests_for_adminship/'
			AND page_title != 'Requests_for_adminship/Sample_Vote_on_sub-page_for_User:Jimbo_Wales'
			AND page_title != 'Requests_for_adminship/Promotion_guidelines'
			AND page_title != 'Wikipedia:Requests_for_adminship/Standards'
			GROUP by page_title
			ORDER BY rev_timestamp DESC
		";
	}
	elseif( $domain == "de.wikipedia.org" ){
		$query = "
			SELECT page_latest, rev_timestamp, page_title, COUNT(*)
			FROM revision_userindex
			JOIN page on page_id = rev_page
			WHERE rev_user_text = '$sql_name'
				AND page_namespace = '4'
				AND page_title LIKE '".$sql_aorb."/%'
				AND page_title != 'Adminkandidaturen/Adminkandidatenvorlage'
				AND page_title != 'Adminkandidaturen/Kandidaturvorlagenhinweis'
				AND page_title != 'Adminkandidaturen/Intro'
				AND page_title != 'Adminkandidaturen/Alt01'
				AND page_title != 'Adminkandidaturen/Alt02' 
				AND page_title NOT LIKE 'Adminkandidaturen/Archiv%'
				AND page_title NOT LIKE 'Adminkandidaturen/Kommentare%'
				
				AND page_title NOT LIKE 'Bürokratenkandidaturen/Archiv%'
				AND page_title NOT LIKE 'Bürokratenkandidaturen/Februar_2011%'
			GROUP by page_title
			ORDER BY rev_timestamp DESC
		";
	}
	else {
		return ;
	}
		
		
	$result = $dbr->query( $query );

	
	foreach ( $result as $u => $rfas ) {

		unset($myRFA);
		
		$candidate = "";
		$page_title = "Wikipedia:".$rfas["page_title"];
		$timestamp = date("Y-m-d", strtotime( $rfas["rev_timestamp"] ) );
		
		$rawwikitext = fetchRawwikitext( $site, $domain, $page_title, $rfas["rev_timestamp"] );
		
		//Create an RFA object & analyze
		if ( $domain == "en.wikipedia.org" ){
			$myRFA = new RFA( $site, null, $rawwikitext );
		}
		if ( $domain == "de.wikipedia.org" ){
			$myRFA = new RFAde( $site, null, $rawwikitext );
		}
		
		
		$candidate = html_entity_decode( $myRFA->get_username() );
		$subArr = array(
				"candidate" => $candidate,
				"page" => $page_title,
				"startdate" => $timestamp,
				"pro" => count( $myRFA->get_support() ),
				"contra" => count( $myRFA->get_oppose() ),
				"neutral" => count($myRFA->get_neutral() ),
			);
						
		foreach ( $myRFA->get_support() as $support ){
			if ( in_array( $support["name"], $output["altnames"] ) ){
				$output["support"][] = $subArr;
				continue(2);
			}
		}

		foreach ( $myRFA->get_oppose() as $oppose ){
			if ( in_array( $oppose["name"], $output["altnames"] ) ){
				$output["oppose"][] = $subArr;
				continue(2);
			}
		}

		foreach ( $myRFA->get_neutral() as $neutral ){
			if ( in_array( $neutral["name"], $output["altnames"] ) ){
				$output["neutral"][] = $subArr;
				continue(2);
			}
		}

		foreach ( $myRFA->get_duplicates() as $duplicates ){
			if ( in_array( $duplicates["name"], $output["altnames"] ) ){
				$output["dupes"][] = $subArr;
				continue(2);
			}
		}
		
		$output["unknown"][] = $subArr;
			
	}

	return $output;
}

/**
 * Check redis cache for rfa pagge
 * @return string rawwikitext of the page
 */
function fetchRawwikitext( $site, $domain, $pageTitle, $pageTimestamp ){
	global $redis;

	$ttl = 604800;
	$hash = "xtoolsrfap".$domain.hash("crc32", $pageTitle.$pageTimestamp);
	$lc = $redis->get($hash);
	
	if ($lc === false){

		$pageObj = $site->initPage( $pageTitle );
		$rawwikitext = $pageObj->get_text();
		
		if ( $rawwikitext ){
			$redis->setex( $hash, $ttl, serialize($rawwikitext) );
		}
	}
	else{
		$redis->expire( $hash, $ttl );
		$rawwikitext = unserialize($lc);
		unset( $lc );
	}

	return $rawwikitext;
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
				<a href="//{$domain}/wiki/User:{$usernameurl$}">{$username$}</a>
				<small><span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span></small>
			</p>
		</div>
		<div class="panel-body xt-panel-body-top" >
		<p>
			<a href="//{$domain}/w/index.php?title=Special%3ALog&type=block&user=&page=User%3A{$usernameurl}&year=&month=-1&tagfilter=" >Block log</a> &middot;
			<a href="//tools.wmflabs.org/xtools/ec/?user={$usernameurl}&lang={$lang}&wiki={$wiki}" >Edit Counter</a> &middot;
			<a href="//tools.wmflabs.org/guc/?user={$usernameurl}" >Global user contributions</a> &middot;
			<a href="//meta.wikimedia.org/w/index.php?title=Special%3ACentralAuth&target={$usernameurl}" >Global Account Manager</a> &middot;
		<!--<a href="//tools.wmflabs.org/wikiviewstats/?lang={$lang}&wiki={$wiki}&page={$userprefix}:{$usernameurl}*" >Pageviews in userspace</a> &middot; -->
		</p>

		<div class="panel panel-default">
			<div class="panel-heading">
				<h4  class="topcaption" >{$rfa} <span class="showhide" onclick="javascript:switchShow( \'admin\', this )">[{#hide#}]</span></h4>
			</div>
			<div class="panel-body" id="admin">
				{$listadmin}
			</div>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading">
				<h4  class="topcaption" >{$rfb} <span class="showhide" onclick="javascript:switchShow( \'bureaucrat\', this )">[{#hide#}]</span></h4>
			</div>
			<div class="panel-body" id="bureaucrat">
				{$listbureaucrat}
			</div>
		</div>
			
	</div>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
	
}