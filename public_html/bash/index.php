<?php
ini_set("display_errors", 1);
//Requires
	require_once( '/data/project/xtools/public_html/WebTool.php' );
	require_once( '/data/project/xtools/public_html/bash/base.php' );

//Load WebTool class
	$wt = new WebTool( 'Bash', 'bash', array( 'getwikiinfo', 'peachy', 'database' ) );
	/*UPDATE ME*/$wtSource = "//code.google.com/p/soxred93tools/source/browse/trunk/web/bash";
	$wtTranslate = true;
	WebTool::setMemLimit();

//Show form if &article parameter is not set (or empty)
	if( !$wgRequest->getSafeVal( 'getBool', 'action' ) ) {
		$content->assign( 'form', true );
		WebTool::assignContent();
	}
	
	$base = new BashBase();
	
	switch( $wgRequest->getSafeVal( 'action' ) ) {
		case 'random':
			$quote = $base->getRandomQuote();
			
			
			$content->assign( 'random', true );
			$content->assign( 'quote', $quote['quote'] );
			$content->assign( 'id', $quote['id'] );
			$phptemp->assign( 'page', $quote['id'] );
			
			$phptemp->assign( 'thisurl', "//tools.wmflabs.org".$_SERVER['REQUEST_URI'] );
			break;
		case 'showall':
			$quotes = $base->getAllQuotes();
			
			$content->assign( 'showall', true );
			$content->assign( 'quotes', $quotes );
			break;
		case 'showone':
			$quote = $base->getQuoteFromId( $wgRequest->getSafeVal( 'id' ) );
			
			$content->assign( 'showone', true );
			$content->assign( 'quote', $quote['quote'] );
			$content->assign( 'id', $quote['id'] );
			$phptemp->assign( 'page', $quote['id'] );
			break;
		case 'search':
			$quotes = $base->getQuotesFromSearch( $wgRequest->getSafeVal( 'search' ), ( $wgRequest->getBool( 'regex' ) ) );
			
			$content->assign( 'search', true );
			$content->assign( 'quotes', $quotes );
			if( !count( $quotes ) ) $content->assign( 'error', $phptemp->get_config_vars( 'noresults') );
			break;
		default:
			WebTool::toDie( $phptemp->get_config_vars( 'invalidaction' ) );
	}



WebTool::finishScript();

