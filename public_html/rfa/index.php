<?php

//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );
	require_once( 'RFAde.php' );
	require_once( PEACHY_BASE_SYS_DIR . '/Peachy/Init.php' );

	$wt = new WebTool( 'rfa' );
	$wt->setLimits();
	$wt->getPageTemplate( 'form' );
	
	$wi = $wt->wikiInfo;
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;
		
	$domain = ( $domain ) ? $domain : 'en.wikipedia.org' ;
	
	switch ($domain){
		case "en.wikipedia.org":
			$optionsProject = "
					<option selected value ='en.wikipedia'>en.wikipedia</option>
					<option value ='de.wikipedia'>de.wikipedia</option>
					<option value ='commons.wikimedia'>commons.wikimedia</option>
					";
			break;
		case "de.wikipedia.org":
			$optionsProject = "
					<option value ='en.wikipedia'>en.wikipedia</option>
					<option selected value ='de.wikipedia'>de.wikipedia</option>
					<option value ='commons.wikimedia'>commons.wikimedia</option>
					";
			break;
		case "commons.wikimedia.org":
			$optionsProject = "
					<option value ='en.wikipedia'>en.wikipedia</option>
					<option value ='de.wikipedia'>de.wikipedia</option>
					<option selected value ='commons.wikimedia'>commons.wikimedia</option>
					";
			break;			
		
		default:
			$wt->toDie( 'nosupport', $domain );
	}
	
	$msgOnDeWiki = "on the German Wikipedia";
	$msgOnEnWiki = "on the English Wikipedia";
	$msgOnCommonsWiki = "on Wikimedia Commons";
	if ( $wt->uselang == "de" ){
		$msgOnDeWiki = "in der deutschen Wikipedia";
		$msgOnEnWiki = "in der englischen Wikipedia";
		$msgOnCommonsWiki = "auf Wikimedia Commons";
	}
	$wt->assign( 'onEnWiki', $msgOnEnWiki );
	$wt->assign( 'onDeWiki', $msgOnDeWiki );
	$wt->assign( 'onCommonsWiki', $msgOnCommonsWiki );
	
	$defaultPage["en.wikipedia.org"] = 'Wikipedia:Requests for adminship/Name of user';
	$defaultPage["de.wikipedia.org"] = 'Wikipedia:Adminkandidaturen/Name des Benutzers';
	$defaultPage["commons.wikimedia.org"] = 'Commons:Administrators/Requests/Name of user';
	
	$wt->assign( 'optionsProject', $optionsProject );
	$wt->assign( 'defaultPage', $defaultPage[ $domain ] );
	$wt->assign( 'optionsPage', getRecentRfXs( $domain ) );
	
	$p1 = $wgRequest->getVal( 'page' );
	$p1 = $wgRequest->getVal( 'p' , $p1 );
	$p2 = $wgRequest->getVal( 'page2');
	$p2 = $wgRequest->getVal( 'p2', $p2 );
	$page = ( $p2 ) ? $p2 : $p1;
	

	if( !$page || !$domain|| in_array( $page, $defaultPage )  ){
		$wt->showPage();
	}

	$page = str_replace(' ', '_', $page);
	$page = preg_replace('/^(Wikipedia:)/', '', $page);
	$page = 'Wikipedia:'.$page;

	
//Create an RFA object & analyze
	$pgVerbose = array();
	$site = Peachy::newWiki( null, null, null, "http://$wi->domain/w/api.php" );
	
	switch ( $domain ){
		case "en.wikipedia.org":			 
			$myRFA = new RFA( $site, $page );
			break;
		case "de.wikipedia.org":
			$myRFA = new RFAde( $site, $page );
			break;
		case "commons.wikimedia.org":
			$myRFA = new RFAcommons( $site, $page );
			break;	
		default:
			$wt->toDie( 'something_went_wrong');
	}
	
	$wt->content = getPageTemplate( 'result' ); 
	$wt->assign( 'list', getRfaResults( $myRFA ) );
	$wt->assign( 'domain', $domain );
	$wt->assign( 'lang', $lang );
	$wt->assign( 'wiki', $wiki );
	$wt->assign( 'page', str_replace('_', ' ', $page ) );
	$wt->assign( 'urlencodedpage', rawurlencode( $page ) );
	$wt->assign( 'username', $myRFA->get_username() );
	$wt->assign( 'usernameurl', rawurlencode( $myRFA->get_username() ) );


unset( $myRFA, $site );
$wt->showPage();


function getRecentRfXs( $domain ){
	global $wt, $redis;
	
	if ( $domain == "en.wikipedia.org" ){
		$dbr = $wt->loadDatabase( 'en', 'wikipedia' );
		$list = '<option value="" >Select from most recent RfA\'s / RfB\'s</option>';
		$optproupLabel["rfas"] = "Requests for Adminship";
		$optproupLabel["rfbs"] = "Requests for Bureaucratship";
		$queryA ="
				SELECT 'rfa' as type, page_title
				FROM page 
				WHERE page_namespace = '4'
				AND page_title LIKE 'Requests_for_adminship/%'
				AND page_title != 'Requests_for_adminship/RfA_and_RfB_Report'
				AND page_title != 'Requests_for_adminship/BAG'
				AND page_title NOT LIKE 'Requests_for_adminship/Nomination_cabal%'
				AND page_title != 'Requests_for_adminship/Front_matter'
				AND page_title != 'Requests_for_adminship/RfB_bar'
				AND page_title NOT LIKE 'Requests_for_adminship/%/%'
				AND page_title != 'Requests_for_adminship/nominate'
				AND page_title != 'Requests_for_adminship/desysop_poll'
				AND page_title != 'Requests_for_adminship/Draft'
				AND page_title != 'Requests_for_adminship/'
				AND page_title != 'Requests_for_adminship/Sample_Vote_on_sub-page_for_User:Jimbo_Wales'
				AND page_title != 'Requests_for_adminship/Promotion_guidelines'
				AND page_title != 'Wikipedia:Requests_for_adminship/Standards'
				ORDER BY page_id DESC
				LIMIT 100
			";		
		$queryB = "
				SELECT 'rfb' as type, page_title
				FROM page
				WHERE page_namespace = '4'
				AND page_title LIKE 'Requests_for_bureaucratship/%'
				AND page_title NOT LIKE 'Requests_for_bureaucratship/%/Bureaucrat_discussion'
				AND page_title != 'Requests_for_bureaucratship/Wikipedia:Requests_for_adminship'
				AND page_title != 'Requests_for_bureaucratship/Candidate_questions'
				ORDER BY page_id DESC
				Limit 100;
			";
	}
	elseif ( $domain == "de.wikipedia.org" ){
		$dbr = $wt->loadDatabase( 'de', 'wikipedia' );
		$list = '<option value="" >Wähle aus den letzten Kandidaturen</option>';
		$optproupLabel["rfas"] = "Adminkandidaturen";
		$optproupLabel["rfbs"] = "Bürokratenkandidaturen";
		$queryA ="
				SELECT 'rfa' as type, page_title
				FROM page
				WHERE page_namespace = '4'
				AND page_title LIKE 'Adminkandidaturen/%'
				AND page_title != 'Adminkandidaturen/Adminkandidatenvorlage'
				AND page_title != 'Adminkandidaturen/Kandidaturvorlagenhinweis'
				AND page_title NOT LIKE 'Adminkandidaturen/Archiv%'
				AND page_title NOT LIKE 'Adminkandidaturen/Kommentare%'
				ORDER BY page_id DESC
				LIMIT 100
			";
		$queryB = "
				SELECT 'rfb' as type, page_title
				FROM page
				WHERE page_namespace = '4'
				AND page_title LIKE 'Bürokratenkandidaturen/%'
				AND page_title NOT LIKE 'Bürokratenkandidaturen/Archiv%'
				AND page_title NOT LIKE 'Bürokratenkandidaturen/Februar_2011%'
				ORDER BY page_id DESC
				Limit 100;
			";
	}
	elseif ( $domain == "commons.wikimedia.org" ){
		$dbr = $wt->loadDatabase( 'commons', 'wikimedia' );
		$list = '<option value="" >Select from most recent RfA\'s / RfB\'s</option>';
		$optproupLabel["rfas"] = "Requests for Adminship";
		$optproupLabel["rfbs"] = "Requests for Bureaucratship";
		$queryA ="
				SELECT 'rfa' as type, page_title
				FROM page 
				WHERE page_namespace = '4'
				AND page_title LIKE 'Administrators/Requests/%'
				AND page_title NOT LIKE 'Commons:Administrators/Requests/%/Bureaucrat_discussion'
				ORDER BY page_id DESC
				LIMIT 100
			";		
		$queryB = "
				SELECT 'rfb' as type, page_title
				FROM page
				WHERE page_namespace = '4'
				AND page_title LIKE 'Commons:Bureaucrats/Requests/%'
				ORDER BY page_id DESC
				Limit 100;
			";
	}
	else {
		return;
	}
	
	$res = $dbr->query( $queryA );
	
	$list .= '<optgroup label="'.$optproupLabel["rfas"].'" >';
	foreach ($res as $i => $page ){
		$list .= '<option value="'.$page["page_title"].'" >'.$page["page_title"].'</option>';
	}
	$list .= '</optgroup>';
	
	$res = $dbr->query( $queryB );
	$list .= '<optgroup label="'.$optproupLabel["rfbs"].'" >';
	foreach ($res as $i => $page ){
		$list .= '<option value="'.$page["page_title"].'" >'.$page["page_title"].'</option>';
	}
	$list .= '</optgroup>';
	
	$dbr->close();
	return $list;
}
    
function getRfaResults( $myRFA ){

	$result = "";
	
	$error = $myRFA->get_lasterror();
	if ( $error ){
		$result .= "<h4 style='color:red' >Error: $error</h4>";
		return $result;
	}
	
    $enddate = $myRFA->get_enddate();
    $tally = count( $myRFA->get_support() ).'/'.count( $myRFA->get_oppose() ).'/'.count( $myRFA->get_neutral() );

    $totalVotes = count( $myRFA->get_support() ) + count( $myRFA->get_oppose() );
    if( $totalVotes != 0 ) {
      $tally .= ", " . number_format( ( count($myRFA->get_support()) / $totalVotes ) * 100, 2 ) . "%";
    }

    $result .= '<a href="//{$domain}/wiki/User:'.$myRFA->get_username().'">'.$myRFA->get_username().'</a>\'s RfA ('.$tally.'); End date: '.$enddate.'<br /><br />';
	$result .= 'Found <strong>'.count($myRFA->get_duplicates()).'</strong> duplicate votes (highlighted in <span class="dup">red</span>).'
    .' Votes the tool is unsure about are <span class="iffy1">italicized</span>.';
	
	$result .= '<div class="container" >';
    $result .= "<h3>Support</h3>";
    $result .= get_h_l($myRFA->get_support(),$myRFA->get_duplicates());
    $result .= "<h3>Oppose</h3>";
    $result .= get_h_l($myRFA->get_oppose(),$myRFA->get_duplicates());
    $result .= "<h3>Neutral</h3>";
    $result .= get_h_l($myRFA->get_neutral(),$myRFA->get_duplicates());
    $result .= '</div>';
    
    return $result;
}

function get_h_l( $var, $searchlist ) {
	$result = "";
	
	if (empty($var)) {
		$result .= "<ul><li>No items in list</li></ul>";
	}
	
	$result .= "<ol>";
	foreach ($var as $vr) {
		$iffy = False;

		if (isset($vr['iffy'])) {
			$iffy = $vr['iffy'];
		}
		
		if (isset($vr['error'])) {
			$text = "<strong>Error parsing signature:</strong> <em>".htmlspecialchars($vr['context'])."</em>";
		} 
		else {
			$text = $vr['name'];
		}

		if (isset($vr['name']) && in_array($vr['name'],$searchlist)) {
			if ($iffy == 1){
				$result .= "<li class=\"dup iffy1\">{$text}</li>\n";
			}
			else{
				$result .= "<li class=\"dup\">{$text}</li>\n";
			}
		} 
		else {
			if ($iffy == 1){
				$result .= "<li class=\"iffy1\">{$text}</li>\n";
			}
			else{
				$result .= "<li>{$text}</li>\n";
			}
		}
	}
	$result .= "</ol>";
	
	return $result;
}

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateResult = '

	<div class="panel panel-primary" style="text-align:center">
		<div class="panel-heading">
			<p class="xt-heading-top" >
				<a href="//{$domain}/wiki/{$urlencodedpage}">{$page}</a>
			</p>
		</div>
		<div class="panel-body xt-panel-body-top" >
			<p>
				<a href="//{$domain}/w/index.php?title=Special%3ALog&type=block&user=&page=User%3A{$usernameurl}&year=&month=-1&tagfilter=" >Block log</a> &middot;
				<a href="//tools.wmflabs.org/xtools-ec/?user={$usernameurl}&lang={$lang}&wiki={$wiki}" >Edit Counter</a> &middot;
				<a href="//tools.wmflabs.org/guc/?user={$usernameurl}" >Global user contributions</a> &middot;
				<a href="//meta.wikimedia.org/w/index.php?title=Special%3ACentralAuth&target={$usernameurl}" >Global Account Manager</a> &middot;
				<a href="//tools.wmflabs.org/wikiviewstats/?lang={$lang}&wiki={$wiki}&page={$userprefix}:{$usernameurl}*" >Pageviews in userspace</a> &middot;
			</p>
		
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4  class="topcaption" >{#generalstats#} <span class="showhide" onclick="javascript:switchShow( \'generalstats\', this )">[{#hide#}]</span></h4>
				</div>
				<div class="panel-body" id="generalstats">
					{$list}
				</div>
			</div>
		</div>
	</div>
	'; 

	if( $type == "result" ) { return $templateResult; }
}
