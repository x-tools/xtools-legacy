<?php

//Requires
	require_once( '/data/project/newwebtest/xtools/public_html/WebTool.php' );
	require_once( '/data/project/newwebtest/xtools/public_html/blame/base.php' );

// set some global vars
	$pgVerbose = false;

//Load WebTool class
	$wt = new WebTool( 'Blame', 'blame', array( 'getwikiinfo', 'peachy', 'database' ) );
	$wtTranslate = true;
	WebTool::setMemLimit( 256 );
	
	$siteNoticeClass = new siteNotice;
	$sitenotice = $siteNoticeClass->checkSiteNoticeRaw();
	if( $sitenotice ) {
		$phptemp->assign( "alert", $sitenotice );
	}

// get params from query string
	$lang = $wgRequest->getSafeVal( 'lang' );
	$wiki = $wgRequest->getSafeVal( 'wiki' );
	$article = $wgRequest->getSafeVal( 'article' );
	$nofollowredir = $wgRequest->getBool( 'nofollowredir' );
	$text = isset($_GET["text"]) ? urldecode($_GET["text"]) : "";
	
	$wikibase = $lang.'.'.$wiki.'.org';
	
//Show form if &article parameter is not set (or empty)
	if( $lang == "" || $wiki == "" || $article == "" || $text == "" ) {
		$content->assign( 'form', 'en' );
		WebTool::assignContent();
		WebTool::toDie("");
	}

// execute the main logic
	$revs = BlameBase::getBlameResult( $wikibase, $article, $nofollowredir, $text);
	$content->assign( "revs", $revs );
	
//Calculate time taken to execute
	$exectime = number_format(microtime( 1 ) - $time, 2, '.', '');
	$phptemp->assign( "excecutedtime", "Executed in $exectime seconds" );
	$phptemp->assign( "memory", "Taken ". number_format((memory_get_usage() / (1024 * 1024)), 2, '.', '')." megabytes of memory to execute." );


WebTool::assignContent();
WebTool::finishScript();

