<?php
	
//Requires
	require_once( '/data/project/xtools/public_html/WebTool.php' );
	require_once( '/data/project/xtools/public_html/articleinfo/base.php' );

	ini_set("display_errors", 1);
	ini_set("memory_limit", '512M');
//Load WebTool class
	$wt = new WebTool( 'ArticleInfo', 'articleinfo' );
	/*Update me*/$wtSource = "//code.google.com/p/soxred93tools/source/browse/trunk/web/articleinfo";
	$wtTranslate = true;
	WebTool::setMemLimit();

//Show form if &article parameter is not set (or empty)
	if( !$wgRequest->getSafeVal( 'getBool', 'article' ) ) {
		$content->assign( 'form', $curlang );
		WebTool::assignContent();
	}
	
//Now load configs for the graph templates
	$linegraph = new Smarty();
	$sizegraph = new Smarty();
	if( is_file( 'configs/' . $curlang . '.conf' ) ) {
		$linegraph->config_load( $curlang . '.conf', 'articleinfo' );
		$sizegraph->config_load( $curlang . '.conf', 'articleinfo' );
	}
	else {
		$linegraph->config_load( 'en.conf', 'articleinfo' );
		$sizegraph->config_load( 'en.conf', 'articleinfo' );
	}


//Set the article variables
	$article = trim( str_replace( array('&#39;','%20'), array('\'',' '), $wgRequest->getSafeVal( 'article' ) ) );
	$article = urldecode($article);


//Initialize Peachy
	try {
		$pageClass = $site->initPage( $article, null, !$wgRequest->getSafeVal( 'getBool', 'nofollowredir' ) );
	} catch( BadTitle $e ) {
		WebTool::toDie( $phptemp->get_config_vars( 'nosuchpage', $e->getTitle() ) );
	} catch( Exception $e ) {
		WebTool::toDie( $e->getMessage() );
	}


//Check for page existance
	$phptemp->assign( "page", $pageClass->get_title() );
	
	if( !$pageClass->exists() ) WebTool::toDie( $phptemp->get_config_vars( 'nosuchpage', $pageClass->get_title() ) );

//Start doing the DB request
	$history = Base::getVars( 
		$pageClass, 
		$site, 
		$wgRequest->getSafeVal( 'getBool', 'nofollowredir' ),
		( $wgRequest->getSafeVal( 'getBool', 'begin' ) ) ? $wgRequest->getSafeVal( 'begin' ) : false, 
		( $wgRequest->getSafeVal( 'getBool', 'end' ) ) ? $wgRequest->getSafeVal( 'end' ) : false
	);
	
	if( !count( $history ) ) WebTool::toDie( $phptemp->get_config_vars( 'norevisions' ) );
	if( count( $history ) == 50000 ) $content->assign( "notice", $phptemp->get_config_vars( 'toomanyrevisions' ) );


//Get logs, for Edits over Time graph
	
	$data = Base::parseHistory( 
		$history, 
		( $wgRequest->getSafeVal( 'getBool', 'begin' ) ) ? $wgRequest->getSafeVal( 'begin' ) : false, 
		( $wgRequest->getSafeVal( 'getBool', 'end' ) ) ? $wgRequest->getSafeVal( 'end' ) : false, 
		$site, 
		$pageClass 
	);


//Now we can assign the Smarty variables!
	$content->assign( "info", true );
		$content->assign( "page", $pageClass->get_title() );
		$content->assign( "urlencodedpage", str_replace( '+', '_', urlencode( $pageClass->get_title() ) ) );
		$content->assign( "totaledits", number_format( $data['count'] ) );
		$content->assign( "minoredits", number_format( $data['minor_count'] ) );
		$content->assign( "minoredits", number_format( $data['minor_count'] ) );
		$content->assign( "anonedits", number_format( $data['anon_count'] ) );
		$content->assign( "minorpct", number_format( ( $data['minor_count'] / $data['count'] ) * 100, 2 ) );
		$content->assign( "anonpct", number_format( ( $data['anon_count'] / $data['count'] ) * 100, 2 ) );
		$content->assign( "firstedit", date( 'd F Y, H:i:s', strtotime( $data['first_edit']['timestamp'] ) ) );
		$content->assign( "firstuser", $data['first_edit']['user'] );
		$content->assign( "lastedit", date( 'd F Y, H:i:s', strtotime( $data['last_edit'] ) ) );
		$content->assign( "timebwedits", $data['average_days_per_edit'] );
		$content->assign( "editspermonth", $data['edits_per_month'] );
		$content->assign( "editsperyear", $data['edits_per_year'] );
		$content->assign( "lastday", number_format( $data['count_history']['today'] ) );
		$content->assign( "lastweek", number_format( $data['count_history']['week'] ) );
		$content->assign( "lastmonth", number_format( $data['count_history']['month'] ) );
		$content->assign( "lastyear", number_format( $data['count_history']['year'] ) );
		$content->assign( "editorcount", number_format( $data['editor_count'] ) );
		$content->assign( "editsperuser", $data['edits_per_editor'] );
		$content->assign( "toptencount", number_format( $data['top_ten']['count'] ) );
		$content->assign( "toptenpct", number_format( ( $data['top_ten']['count'] / $data['count'] ) * 100, 2 ) );
	
	
	$content->assign( "graphs", true );
		$content->assign( "graphanonpct", number_format( ( $data['anon_count'] / $data['count'] ) * 100, 2 ) );
		$content->assign( "graphuserpct", number_format( 100 - ( ( $data['anon_count'] / $data['count'] ) * 100 ), 2 ) );
		$content->assign( "graphminorpct", number_format( ( $data['minor_count'] / $data['count'] ) * 100, 2 ) );
		$content->assign( "graphmajorpct", number_format( 100 - ( ( $data['minor_count'] / $data['count'] ) * 100 ), 2 ) );
		$content->assign( "graphtoptenpct", number_format( ( $data['top_ten']['count'] / $data['count'] ) * 100, 2 ) );
		$content->assign( "graphbottomninetypct", number_format( 100 - ( ( $data['top_ten']['count'] / $data['count'] ) * 100 ), 2 ) );
	
	$content->assign( "yeargraph", true );
		$content->assign( "yearcounts", $data['year_count'] );
		$content->assign( "yearpixels", getYearPixels( $data['year_count'] ) );
		$content->assign( "pixelcolors", array( 'all' => '008800', 'anon' => '55FF55', 'minor' => 'FFFF55' ) );
	
	$content->assign( "linegraph", true );
		$linegraph->assign( "data", $data['year_count'] );
		$linegraph->assign( "eventdata", $logs );
		$content->assign( "linegraphdata", md5( $pageClass->get_title() . '-' . $pageClass->get_id() ) );
		file_put_contents( 'data/' . md5( $pageClass->get_title() . '-' . $pageClass->get_id() ) . '.xml', $linegraph->fetch( 'linegraph.tpl' ));
		chmod( 'data/' . md5( $pageClass->get_title() . '-' . $pageClass->get_id() ) . '.xml', 0775);
	
	$content->assign( "monthgraph", true );
		$content->assign( "monthpixels", getMonthPixels( $data['year_count'] ) );
		$content->assign( "evenyears", getEvenYears( array_keys( $data['year_count'] ) ) );
	
	$content->assign( "sizegraph", true );
		$sizegraph->assign( "data", $data['year_count'] );
		$content->assign( "sizegraphdata", md5( $pageClass->get_title() . '-' . $pageClass->get_id() . '-line' ) );
		file_put_contents( 'data/' . md5( $pageClass->get_title() . '-' . $pageClass->get_id()  . '-line' ) . '.xml', $sizegraph->fetch( 'sizegraph.tpl' ));
		chmod( 'data/' . md5( $pageClass->get_title() . '-' . $pageClass->get_id()  . '-line' ) . '.xml', 0775);
		
	$content->assign( "usertable", true );
		$content->assign( "userdata", $data['editors'] );
		$content->assign( "topteneditors", $data['top_fifty'] );
		$content->assign( "url", $url );
		$content->assign( "lang", $lang );
		$content->assign( "wiki", $wiki );


WebTool::finishScript();


//Script finished, now we're on to the functions

//Calculate how many pixels each year should get for the Edits per Year table
	function getYearPixels( &$data ) {
		$month_total_edits = array();
		foreach( $data as $year => $tmp ) {
			$month_total_edits[$year] = $tmp['all'];
		}
		
		$max_width = max( $month_total_edits );
		
		$pixels = array();
		foreach( $data as $year => $tmp ) {
			if( $tmp['all'] == 0 ) $pixels[$year] = array();
			
			$processarray = array( 'all' => $tmp['all'], 'anon' => $tmp['anon'], 'minor' => $tmp['minor'] );
			
			asort( $processarray );
			
			foreach( $processarray as $type => $count ) {
				$newtmp = ceil( 500 * ( $count ) / $max_width );
				$pixels[$year][$type] = $newtmp;
			}
		}
		
		return $pixels;
	}

//Calculate how many pixels each month should get for the Edits per Month table
	function getMonthPixels( &$data ) {
		$month_total_edits = array();
		foreach( $data as $year => $tmp ) {
			foreach( $tmp['months'] as $month => $newdata ) {
				$month_total_edits[ $month.'/'.$year ] = $newdata['all'];
			}
		}
	
		$max_width = max( $month_total_edits );
		
		$pixels = array();
		foreach( $data as $year => $tmp ) {
			foreach( $tmp['months'] as $month => $newdata ) {
				if( $tmp['all'] == 0 ) $pixels[$year][$month] = array();
				
				$processarray = array( 'all' => $newdata['all'], 'anon' => $newdata['anon'], 'minor' => $newdata['minor'] );
				
				asort( $processarray );
				
				foreach( $processarray as $type => $count ) {
					$newtmp = ceil( ( 500 * ( $count ) / $max_width ) );
					$pixels[$year][$month][$type] = $newtmp;
				}
			}
		}
		
		return $pixels;
	}


//Generate the log actions infobox for the flash graph
	function actionParse( $date, $logs ) {
		global $content;
		
		if( strlen( $date ) == 5 ) {
			$parseddate = '0' . substr( $date, 0, 1 ) . '/' . substr( $date, 1 );
		}
		else {
			$parseddate = substr( $date, 0, 2 ) . '/' . substr( $date, 2 );
		}
		
		$ret = $content->get_config_vars( 'duringdate', $parseddate );
		
		$ret .= "<ul>";
		
		foreach( $logs as $type => $count ) {
			switch( $type ) {
				case 'modify':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphmodify', $count ) . "</li>";
					break;
				case 'protect':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphprotect', $count ) . "</li>";
					break;
				case 'unprotect':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphunprotect', $count ) . "</li>";
					break;
				case 'move':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphmove' . $type, $count ) . "</li>";
					break;
				case 'move_redir':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphmoveredir' . $type, $count ) . "</li>";
					break;
				case 'move_prot':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphmoveprot' . $type, $count ) . "</li>";
					break;
				case 'delete':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphdelete' . $type, $count ) . "</li>";
					break;
				case 'restore':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphundelete' . $type, $count ) . "</li>";
					break;
				default:
					break;
			}
		}
		
		$ret .= "</ul>";
		
		return htmlentities( $ret );
	
	}


//Returns a list of even years, used to generate contrasting colors for the Edits/Month table
	function getEvenYears( $years ) {
		$years = array_flip( $years );
		foreach( $years as $year => $id ) {
			$years[$year] = "5";
			if( $year % 2 == 0 ) unset( $years[$year] );
		}
		return $years;
	}

