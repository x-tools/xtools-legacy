<?php

//Requires
require_once( '/data/project/xtools/public_html/WebTool.php' );
require_once( '/data/project/xtools/public_html/pages/base.php' );

//Load WebTool class
	$wt = new WebTool( 'Pages', 'pages' );
	$wtSource = "";
	$wtTranslate = true;
	WebTool::setMemLimit();

// fill the select fields
	$selectns ='
		<select name="namespace">
			<option value="all">-All-</option>
			<option value="0">Main</option>
			<option value="1">Talk</option>
			<option value="2">User</option>
			<option value="3">User talk</option>
			<option value="4">Wikipedia</option>
			<option value="5">Wikipedia talk</option>
			<option value="6">File</option>
			<option value="7">File talk</option>
			<option value="8">MediaWiki</option>
			<option value="9">MediaWiki talk</option>
			<option value="10">Template</option>
			<option value="11">Template talk</option>
			<option value="12">Help</option>
			<option value="13">Help talk</option>
			<option value="14">Category</option>
			<option value="15">Category talk</option>
			<option value="100">Portal</option>
			<option value="101">Portal talk</option>
			<option value="108">Book</option>
			<option value="109">Book talk</option>
		</select><br />
      ';
	$selectredir ='
		<select name="redirects">
			<option value="none">Include redirects and non-redirects</option>
			<option value="onlyredirects">Only include redirects</option>
			<option value="noredirects">Exclude redirects</option>
		</select><br />
	';
	$content->assign( 'selectns', $selectns );
	$content->assign( 'selectredir', $selectredir );
	
//Show form if &article parameter is not set (or empty)
	if( !$wgRequest->getSafeVal( 'getBool', 'user' ) ) {
		$content->assign( 'form', $curlang );
		WebTool::assignContent();
	}
	
//Get username & userid, quit if not exist
	$userData = PagesBase::getUserData( $dbr, $wgRequest->getSafeVal('user') );
	if( !$userData ) { WebTool::toDie("No such user"); }
	
	$result = PagesBase::getCreatedPages( 
				$dbr, 
				$userData["user_id"], 
				$wgRequest->getSafeVal('lang'), 
				$wgRequest->getSafeVal('wiki'),
				$wgRequest->getSafeVal('namespace'),
				$wgRequest->getSafeVal('redirects')
			 );	
	
	$filtertextNS = ( $result->filterns == "all" ) ? " in all namespaces." : " in namespace ".$wgRequest->getSafeVal('namespace').".";
	
#print_r($result->namespaces);	
	$content->assign( 'showresult', true );
	$content->assign( 'graphs', true );
	$content->assign( 'totalcreated', "User ".$userData["user_name"]." has created $result->total pages on ".$wgRequest->getSafeVal('lang').".".$wgRequest->getSafeVal('wiki').$filtertextNS );
	$content->assign( 'filterredir', $result->filterredir );
	$content->assign( 'namespaces', $result->namespaces );
	$content->assign( 'nstotals', $result->listnum );
	$content->assign( 'nsnames', $result->listns );
	$content->assign( 'list', $result->list );
	WebTool::assignContent();

			
			
