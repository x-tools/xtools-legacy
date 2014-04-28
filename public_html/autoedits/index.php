<?php

//Requires
   require_once( '/data/project/xtools/public_html/WebTool.php' );
   require_once( '/data/project/xtools/public_html/autoedits/base.php' );

//Load WebTool class
   $wt = new WebTool( 'AutoEdits', 'autoedits' );
   $wtSource = "//code.google.com/p/soxred93tools/source/browse/trunk/web/autoedits";
   $wtTranslate = true;
   WebTool::setMemLimit();

//Show form if &article parameter is not set (or empty)
   if( !$wgRequest->getSafeVal( 'getBool', 'user' ) ) {
      $content->assign( 'form', $curlang );
      WebTool::assignContent();
   }
   
   if( $lang == "en" && $wiki == "wikipedia" ) {
      //WebTool::toDie( "In order to both discourage editcountitis and give the English Wikipedia editors a chance to realize what editing is all about and why I created this tool in the first place, I have disabled my edit counters (pcount, simplecount, autoedits) until August 17 2010. Please use this time to reflect on why I made this tool in the first place: To serve curiosity, not to create false judgement descisions about editors. -X! 13 August 2010" );
   }

   $user = WebTool::prettyTitle( $wgRequest->getSafeVal( 'user' ), true );

//Initialize Peachy
   try {
      $userClass = $site->initUser( $user );
   } catch( Exception $e ) {
      WebTool::toDie( $e->getMessage() );
   }

   $phptemp->assign( "page", $user );
   
   if( !$userClass->exists() ) WebTool::toDieMsg( 'nosuchuser', $user );

   $useLabs = true;   
   $count = $userClass->get_editcount( false, $dbr );
   
   //Here
   if( $count > 100000 ) WebTool::toDieMsg( 'toomanyedits', 100000 );

//Start doing the DB request
   $data = AutoEditsBase::getMatchingEdits( 
      $user,
      ( $wgRequest->getSafeVal( 'getBool', 'begin' ) ) ? $wgRequest->getSafeVal( 'begin' ) : false, 
      ( $wgRequest->getSafeVal( 'getBool', 'end' ) ) ? $wgRequest->getSafeVal( 'end' ) : false,
      $count
   );
   
   $content->assign( 'showedits', true );
      $content->assign( 'data', $data['counts'] );
      $content->assign( 'tools', AutoEditsBase::$AEBTypes );
      $content->assign( 'url', $url );
      
      $content->assign( 'totalauto', $data['total'] );
      $content->assign( 'totalall', $data['editcount'] );
      $content->assign( 'pct', $data['pct'] );
      $content->assign( 'urls', $data['urls'] );

WebTool::finishScript();
