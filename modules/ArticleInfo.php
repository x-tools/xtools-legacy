<?php
/**
 * ArticleInfo Library
 * Copyright (C) 2014  Hedonil, TParis, X!
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */
 
class ArticleInfo {
	
	private $history = array();
	public $pageLogs = array( "months" => array(), "years" => array() );
	private $tmpLogs = array();
	private $tmpBotedits = array();
	
	public $pageviews;
	public $links;
	
	public $pagetitleFull = ""; //with namespace prefix
	public $pagetitle = "";
	public $pagetitleFullUrl;
	public $pagewatchers = "–";
	public $quickRevCount;
	public $mainAuthor;
	public $latestRev;
	public $createDate;
	public $pageLanguage;
	public $pageDomain;
	
	public $pageid = -1;
	public $namespace = -1;
	public $data = array();
	
	public $begin;
	public $end;
	
	public $error = false;
	public $historyCount = 0;
	public $checkWiki = array();
	public $extLinks = array();
	public $langToolTHtml = null;
	public $enwp10Html = null;
	public $deadlinks = '–';
	public $checkResults = array("color" => '', "list"=>array(), "prios"=>array());
	
	public $isDisamb = false;
	public $wikidataItemID;
	public $wikidataItems = array();
	public $wikidataProps = array();
	
	private $AEBTypes;
	private $checkAEB;
	private $perflog;
	private $markedRevisions;
	
	
	/**
	 * 
	 * @param Database2 $dbr
	 * @param string $article
	 * @param string $begin
	 * @param string $end
	 * @param unknown $noredirects
	 */
	function __construct( $dbr, $wi, $page, $begin, $end, $noredirects, $pageid=null, $apiconf=false, $nsid=null, $wditemid=null){
		global $redis;

		$this->checkAEB = false;
		$this->pageviews = (object)array("sumhits"=>"–");
		
		$this->pageLanguage = $wi->lang;
		$this->pageDomain = $wi->domain;
		$this->createDate = date('Y-m-d H:i:s');
		
		$this->begin = $begin;
		$this->end = $end;
		$this->pagetitle = $page;
		$this->pageid = $pageid;
		$this->namespace = $nsid;
		$this->wikidataItemID = $wditemid;
		
		if( $apiconf ){
			$this->fetchQuickData( $dbr, $wi, $apiconf );
			$this->parseChecks( $wi );
			return;
		}
		
		$this->fetchInitPageData( $dbr, $wi );

		if( !$this->pagetitle || !$this->pageid ) {
			$this->error = 'nosuchpage' ;
			return;
		}
		
		$this->fetchInitRevCount($dbr);
		
		$longQueue = false;
		if( $this->historyCount > 60000 ) {
			if ( $redis && $redis->get(XTOOLS_LONG_QUEUE_COUNT) < XTOOLS_LONG_QUEUE_LIMIT ){
				$redis->incr(XTOOLS_LONG_QUEUE_COUNT);
				$redis->expire(XTOOLS_LONG_QUEUE_COUNT, 3600);
				$longQueue = true;
			}
			else{
				$this->error = 'longQueue_full';
				return ;
			}
		}
		

		$this->fetchData( $dbr, $wi );
		
		$this->parseLogs();
		$this->parseReverts();
		$this->parseHistory();
		
		$this->parseChecks( $wi );

		if ($longQueue ) { $redis->decr(XTOOLS_LONG_QUEUE_COUNT); }
		
		global $perflog;
		array_push( $perflog->stack, $this->perflog);
	}
	
/*********************************************** fetch Quick Data ************************************************************/
		
	private function fetchInitPageData( $dbr, $wi ){
		global $wt, $perflog;
		
		$apibase = "https://$wi->domain/w/api.php?";
		$query[] = array(
				"type" => "api",
				"src" => "",
				"timeout" => 0,
				"query" => $apibase.http_build_query( array(
						'action' => 'query',
						'format' => 'json',
						'prop' => 'info|pageprops|extlinks',
						'inprop' => 'protection|talkid|watched|watchers|notificationtimestamp|subjectid|url|readable',
						'redirects' => '',
						'indexpageids' => '',
						'converttitles' => '',
						'ellimit' => '20',
						'elexpandurl' => '',
						'titles' => $this->pagetitle,
						'pageids' => $this->pageid
					))
			);
		
		$res = json_decode( $wt->gethttp( $query[0]["query"] ) );
		

		if( $id = @$res->query->pageids[0] ){
			
			$res = $res->query->pages->{$id};
			
			$this->pageid = $res->pageid;
			$this->namespace = (int)$res->ns;
			$this->pagetitleFull = $res->title;
			$this->pagetitleFullUrl = rawurlencode( str_replace( " ", "_", $this->pagetitleFull ) );
			$this->pagetitle = ( $this->namespace === 0 ) ? $res->title : preg_replace('/(^.*:)(.*)/', '\2', $this->pagetitleFull, 1 ) ;
			$this->pagewatchers = ( isset($res->watchers) ) ? (int)$res->watchers : "< 30";
			
			if ( isset( $res->pageprops->wikibase_item ) ){
				$this->wikidataItemID = $res->pageprops->wikibase_item;
			}
			if ( isset( $res->pageprops->disambiguation ) ){
				$this->isDisamb = true;
			}
			
			if ( isset( $res->extlinks ) ){
				foreach ( $res->extlinks as $i => $link ){
					$this->extLinks[] = array("link" => $link->{'*'}, "status" => "unchecked" );
				}
			}
		}
		
 		unset( $res );
		
	}
	
	private function fetchInitRevCount( $dbr ){
		$query = "Select count(*) as count FROM revision_userindex WHERE rev_page = '$this->pageid' ";
	
		$res = $dbr->query( $query );
		$this->data["count"] = $res[0]["count"];
		$this->historyCount = $this->data["count"];
	}
	
	private function fetchQuickData( $dbr, $wi, $conf ){
		global $wt, $dbrtools;
		$stime = microtime(true);
		
		//file_put_contents('/data/project/xtools/api_error', $this->pageid."\n$this->pagetitle\n$wi->lang\n$wi->wiki\n\n", FILE_APPEND );
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					(SELECT count(*) as value1, count(distinct rev_user_text) as value2 
					 FROM revision_userindex 
					 WHERE rev_page = '$this->pageid')
					 
				UNION ALL				
					(SELECT rev_user_text as value1, @uid:=rev_user as value2 
					 FROM revision_userindex 
					 WHERE rev_page = '$this->pageid' AND rev_timestamp > 1 AND rev_parent_id ='0' 
					 LIMIT 1)

				UNION ALL
					(Select IF( @uid > '0'
					, (Select user_editcount from user WHERE user_id = (Select rev_user from revision_userindex WHERE rev_page = '$this->pageid' AND rev_parent_id ='0' LIMIT 1) )
					, (Select count(*) from revision_userindex where rev_user_text = (Select rev_user_text from revision_userindex WHERE rev_page = '$this->pageid' AND rev_parent_id ='0' LIMIT 1) )
					) as value1, '' as value2) 

				UNION ALL
					(SELECT 'deadlink' as value1, count(a.cl_from) as value2
					FROM categorylinks a
					JOIN s51187__xtools_tmp.deadlinkcats b on b.cl_from=a.cl_to and b.cl_wiki ='$wi->database'
					WHERE a.cl_from in
						(
						SELECT page_id
						FROM page
						where page_namespace in(0,1) and page_title = (Select page_title from page where page_id='$this->pageid')
						)
					)
				"
			);
		
		$apibase = ( @$conf->showPageview ) ? "https://tools.wmflabs.org/wikiviewstats/api.php?" : "https://tools.wmflabs.org/ninihil/?";
		$apiquery[] =  $apibase.http_build_query( array(
						'request' => 'pageViews',
						'format' => 'json',
						'lang' => $wi->lang,
						'project' => $wi->wiki,
						'pagetitle' => str_replace(" ", "_", $this->pagetitle ),
						'type' => 'daystats',
						'latest' => '30'
					));
		
		$apibase = "https://$wi->domain/w/api.php?";
		$apiquery[] =  $apibase.http_build_query(  array(
						'action' => 'query',
						'prop' => 'info|revisions',
						'format' => 'json',
						'inprop' => 'watchers',
						'rvprop' => 'ids|timestamp',
						'rvlimit' => '1',
						'rvdir' => 'older',
						'redirects' => '',
						'pageids' => $this->pageid
					));

		$apibase = ( $this->namespace === 0 ) ? "https://tools.wmflabs.org/languagetool/pageCheck/index?" : "https://tools.wmflabs.org/ninihil/?";
		$apiquery[] = $apibase.http_build_query( array(
						'lang' => $wi->lang,
						'url' => str_replace(" ", "_", @$this->pagetitle),
					));
		
		$apibase = ( $this->namespace === 0  && $wi->database == 'dewiki' &&  @$conf->showMainauthor ) ? 
					"https://tools.wmflabs.org/wikihistory/dewiki/getauthors.php?" : "https://tools.wmflabs.org/ninihil/?" ;
		$apiquery[] = $apibase.http_build_query(  array(
						'page_id' => $this->pageid
					));
		
		$apibase = ( $this->namespace === 0 ) ?  "https://www.wikidata.org/w/api.php?" : "https://tools.wmflabs.org/ninihil/?" ;
		$apiquery[] =  $apibase.http_build_query(  array(
						'action' => 'wbgetentities',
						'format' => 'json',
						'props' => 'info|labels|descriptions|claims',
						'ids' => $this->wikidataItemID,
						'languages' => $wi->lang
				));
		
		if ( $this->namespace === 0 ) {
			
			$dbtitle = $dbrtools->strencode(str_replace('_', ' ', $this->pagetitle) );
			$querydbr =  "
					/*query faster with full join than w/o */
					SELECT 'm' as Error, 'm' as Notice, Prio, 'm' as Name_Trans, 'm' as Text
					FROM s51080__checkwiki_p.cw_error a
					JOIN s51080__checkwiki_p.cw_overview_errors b on a.project=b.project and a.error=b.id
					WHERE a.project = '$wi->database' and a.title ='$dbtitle' and b.done is null
				";

		}
		
		//Get db result
	$ptime = microtime(true);
		$res = $dbr->query( $query[0]["query"] );
	$b1 = number_format(microtime(true)-$ptime,3);
		
		$this->data["count"] = $res[0]["value1"];
		$this->data['editor_count'] = $res[0]["value2"];
		$this->data['first_edit']['user'] = $res[1]["value1"];
		$this->data['first_edit']['user_count'] = $res[2]["value1"];
		$this->deadlinks = ($res[3]["value2"]) ? 'yes (test)': '0 (test)';

		
		//Get api result
	$ptime = microtime(true);
		$res = $dbr->multicurl( $apiquery );
	$b2 = number_format(microtime(true)-$ptime,3);
	
		if ( $pv = json_decode( $res[0] ) ){
			$this->pageviews = $pv;
		}
		
		$tmp = json_decode( $res[1] );
		if ( $tmp = @$tmp->query->pages->{$this->pageid} ){
			$this->pagewatchers = ( isset($tmp->watchers) ) ? (int)$tmp->watchers : "< 30";
			$this->latestRev = @$tmp->revisions[0]; 
		}

		if ( isset( $res[2]) ){
			$this->langToolTHtml = $res[2];
		}
		
		if ( isset( $res[3]) ){
			$result2 = str_replace(array('authors.stoploading();', 'authors.resultloaded(','"'), array('','',''), $res[3] );
 			$m1 = substr_replace($result2, '', strpos($result2, ',') );
 			if ( strlen($m1) > 10 && strlen($m1) < 100 ) {
 				$this->mainAuthor = $m1;
 			}
		}

		$reswd = isset( $res[4] ) ? json_decode( $res[4] ) : null;
		if ( $reswd ) {
			$reswd = $reswd->entities->{$this->wikidataItemID};
			$this->wikidataProps[] = ( isset($reswd->labels->{$wi->lang}->value) ) ? "label" : null;
			$this->wikidataProps[] = ( isset($reswd->descriptions->{$wi->lang}->value) ) ? "description" : null;

			if ( !empty( $reswd->claims ) ) {
				foreach ( $reswd->claims as $claim => $row ) {
					$this->wikidataProps[] = $claim;
				}
			}
		}

		unset($res);

	$ptime = microtime(true);
		if ( isset($querydbr) ){
			if( $res = $dbrtools->query( $querydbr ) ){
				$this->checkWiki = $res;
			}
		}
	$b3 = number_format(microtime(true)-$ptime,3);
	
	
	$bges = number_format(microtime(true)-$stime,3);
	#file_put_contents('/data/project/xtools/api_error', date('Y-m-d H:i:s')."\t".$this->pagetitle."\t".$wi->domain."\t".gethostname()."\t"."$b1 $b2 $b3 $bges\n", FILE_APPEND );
	
	#file_put_contents('/data/project/xtools/api_error', $this->pagetitle."\t".json_encode($this->wikidataProps)."\n\n", FILE_APPEND );
	#file_put_contents('/data/project/xtools/api_error', "$this->pagetitle\t$this->latestRev\t$this->pagewatchers\n", FILE_APPEND);
		
		unset($query, $res, $tmp);
	}
	

/*********************************************** fetch Full Data ************************************************************/
		
	private function fetchData( $dbr, $wi ){
		$pstart = microtime(true);
		
// 		if( $this->begin ) { $conds[] = 'UNIX_TIMESTAMP(rev_timestamp) > ' . $dbr->strencode( strtotime( $this->begin ) ); }
// 		if( $this->end   ) { $conds[] = 'UNIX_TIMESTAMP(rev_timestamp) < ' . $dbr->strencode( strtotime( $this->end ) ); }

		//rev_comment has sometimes some strange chars, currently not needed, removed from fileds list
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
						SELECT rev_id, rev_parent_id, rev_user_text, rev_user, rev_timestamp, rev_minor_edit, rev_len
						FROM revision_userindex
						WHERE rev_page = '$this->pageid' AND rev_timestamp > 1 
						ORDER BY rev_timestamp
					",
				);
		
		$title = $dbr->strencode( str_replace(" ", "_", $this->pagetitle ) );
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
						SELECT log_action as action, log_timestamp as timestamp 
						FROM logging_logindex 
						WHERE log_namespace = '$this->namespace' AND log_title = '$title' AND log_timestamp > 1
						AND log_type in ('delete', 'move', 'protect')
					",
				);
		
		$wbitem = str_replace("Q", "", $this->wikidataItemID );
		$query[] = array(
				"type" => "db",
				"src" => "slice s1.labsdb",
				"timeout" => 0,
				"query" => "
						SELECT ips_site_id, ips_site_page 
						From wikidatawiki_p.wb_items_per_site
						WHERE ips_item_id = '$wbitem'
					",
				);
		
		
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
						SELECT count(*) as value, 'links_ext' as type FROM externallinks where el_from= '$this->pageid' 
						UNION
						SELECT count(*) as value, 'links_out' as type FROM pagelinks where pl_from= '$this->pageid' 
						UNION
						SELECT count(*) as value, 'links_in' as type FROM pagelinks where pl_namespace = '$this->namespace' and pl_title= '$title'
						UNION
						SELECT count(*) as value, 'redirects' as type FROM redirect WHERE rd_namespace = '$this->namespace' and rd_title= '$title'
				",
		);
		

		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					SELECT count(rev_user_text) as count, rev_user_text
					FROM revision_userindex
					JOIN user_groups on rev_user = ug_user
					LEFT JOIN user_former_groups on rev_user = ufg_user
					WHERE rev_page = '$this->pageid' and (ug_group = 'bot' or ufg_group = 'bot')
					GROUP BY rev_user_text
				",
		);
		
		
		$apibase = "https://tools.wmflabs.org/wikiviewstats/api.php?";
		$query[] = array(
				"type" => "api",
				"src" => "",
				"timeout" => 0,
				"query" => $apibase.http_build_query( array(
						'request' => 'pageViews',
						'lang' => $wi->lang,
						'project' => $wi->wiki,
						'wikidataid' => @$this->wikidataItemID,
						'pagetitle' => str_replace(" ", "_", @$this->pagetitleFull),
						'type' => 'daystats',
						'latest' => '60'
					) )
				);
		
		if ( $this->namespace === 0 ) {
			
			$dbtitle = $dbr->strencode(str_replace('_', ' ', $this->pagetitle) ); //no _ here!
			$query[] = array(
					"type" => "db",
					"src" => "slice tools.labsdb",
					"timeout" => 0,
					"query" => "
						/*query faster with full join than w/o */
						SELECT Error, Notice, Prio, Name_Trans, Text 
						FROM s51080__checkwiki_p.cw_error a
						JOIN s51080__checkwiki_p.cw_overview_errors b on a.project=b.project and a.error=b.id
						WHERE a.project = '$wi->database' and a.title ='$dbtitle' and b.done is null
					"
				);
			
			$query[] = array(
					"type" => "db",
					"src" => "slice s1.labsdb",
					"timeout" => 0,
					"query" => "Select '-'
						/*query faster with full join than w/o 
						SELECT IF(term_type = 'label', 'label', 'description') as term , term_text
						FROM wikidatawiki_p.wb_entity_per_page
						JOIN wikidatawiki_p.page on epp_page_id=page_id
						JOIN wikidatawiki_p.wb_terms on term_entity_id=epp_entity_id and term_language='$wi->lang' and term_type in ('label','description')
						where epp_entity_id= REPLACE('$this->wikidataItemID','Q','')
						UNION
						SELECT pl_title as term,  wb_terms.term_text
						FROM wikidatawiki_p.pagelinks
						JOIN wikidatawiki_p.wb_terms on term_entity_id = substring(pl_title, 2) and term_entity_type = (IF(substring(pl_title,1,1) ='Q', 'item', 'property')) and term_language='en' and term_type ='label'
						wHERE pl_namespace in (0,120 )and pl_from = (select page_id from page where page_namespace=0 and page_title = '$this->wikidataItemID')
					*/
					"
				);
			
			$apibase = "https://tools.wmflabs.org/languagetool/pageCheck/index?";
			$query[] = array(
					"type" => "api",
					"src" => "plainhtml",
					"timeout" => 0,
					"query" => $apibase.http_build_query( array(
						'lang' => $wi->lang,
						'url' => str_replace(" ", "_", @$this->pagetitleFull),
					) )
				);
			
			$skip = ( $this->namespace === 0 && $wi->domain == "en.wikipedia.org" ) ? "" : "skip";
#			$apibase = "https://tools.wmflabs.org/${skip}enwp10/cgi-bin/list2.fcgi?";
			$apibase = "https://tools.wmflabs.org/${skip}enwp10/cgi-bin/log.fcgi?";
			$query[] = array(
					"type" => "api",
					"src" => "plainhtml",
					"timeout" => 0,
					"query" => $apibase.http_build_query( array(
						'project' => '',
						'ns' => 0,
						'namespace' => 0,
						'pagename' => str_replace(" ", "_", @$this->pagetitleFull),
						'limit' => '50',
						'offset' => 1,
						'sorta' => 'Importance',
						'releaseFilter' => 0
							
					) )
				);
			
			$links = 0;
// 			foreach ( (array)$this->extlinks as $i => $link ){
// 				$query2[] = array(
// 						"type" => "api",
// 						"src" => "head",
// 						"timeout" => 1.5,
// 						"query" => $link
// 					);
// 				$links = $i;
// 			}
			

		}
		
		$res = $dbr->multiquery( $query );
		
		$this->history = $res[0];
		$this->tmpLogs = $res[1];
		$this->wikidataItems = $res[2];
		$this->links = $res[3];
		$this->tmpBotedits = $res[4];
		
		if ($res[5] ){
			$this->pageviews = $res[5];
		}
		
		
		if( isset($res[6]) ){
			$this->checkWiki = $res[6];
			#$this->perflog[] = array("respppp", $res[6] );
		}
		
		if( isset($res[7]) ){
			foreach ($res[7] as $i => $row ){
				$this->wikidataProps[] = $row["term"];
				#$this->perflog[] = array( $row["term"] => $row["term_text"] );
			}
			
			
		}
		
		if( isset($res[8]) ){
			$this->langToolTHtml = $res[8];
			#$this->perflog[] = $this->langToolTHtml;
		}
		
		if( isset($res[9]) ){
			$this->enwp10Html = $res[9];
			#$this->perflog[] = $this->langToolTHtml;
		}
		
// 		if( isset($res[9]) ){
// 			for ($i=0; $i <= $links; $i++){
// 				$status = $res[ ( 9 + $i) ][0];
// 				$error = ($status > 302 ) ? "link error" : "ok";
// 				$prio = ( $status > 302 ) ? 2 : 0;
// 				$this->checkWiki[] = array(
// 						"Prio" => $prio,
// 						"Name_Trans" => 'Sample link test',
// 						"Notice" => $status,  
// 						"Text" => '<a href="'.$this->extlinks[$i].'" >'.$this->extlinks[$i].'</a>',
// 					);
// 			}
// 		}
		
#		global $perflog;
#		$perflog->add('resp999', 0, $res );
		
		$this->historyCount = count($this->history);
		
		unset($res);

		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}

	private function parseChecks( $wi ){
		
		$this->checkResults["prios"][0] = null;
		$this->checkResults["prios"][1] = null;
		$this->checkResults["prios"][2] = null;
		$this->checkResults["prios"][3] = null;
		$this->checkResults["sources"]['wikidata'] = '–';
		$this->checkResults["sources"]['checkwiki'] = '–';
		$this->checkResults["sources"]['languagetool'] = '–';
		
		if (!($this->namespace === 0) ){ return;}
		
		$this->checkResults["sources"]['wikidata'] = 0;
		$this->checkResults["sources"]['checkwiki'] = 0;
		$this->checkResults["sources"]['languagetool'] = 0;
		
		//1. checkwiki
		foreach ($this->checkWiki as $i => $row ){
			$this->checkResults['list'][] = array(
					"source" => "checkwiki",
					"prio" => $row["Prio"],
					"notice" => $row["Notice"],
					"name" => $row["Name_Trans"],
					"explain" => $row["Text"],
				);
		}
		
		//2. wikidata
		if (  !$this->wikidataItemID  || $this->wikidataProps == 'disabled') {
// 			$this->checkResults['list'][] = array(
// 					"source" => "wikidata",
// 					"prio" => '1',
// 					"notice" => 'Wikidata',
// 					"name" => 'missing in Wikidata',
// 					"explain" => 'label for language <em>'.$wi->lang.'</em> is missing. See: <a href="//www.wikidata.org/wiki/Help:Label" >Help:Label</a> ',
// 			);
		}
		else{

			if ( ($pos = array_search( 'label', $this->wikidataProps)) === false  ) { 
				$this->checkResults['list'][] = array(
						"source" => "wikidata",
						"prio" => '2',
						"notice" => 'Label for language <em>'.$wi->lang.'</em> is missing',
						"name" => 'Wikidata',
						"explain" => 'See: <a href="//www.wikidata.org/wiki/Help:Label" >Help:Label</a> ',
				);
			}
			else{
				unset( $this->wikidataProps[$pos] );
			}
			if ( ($pos = array_search( 'description', $this->wikidataProps)) === false ) {
				$this->checkResults['list'][] = array(
						"source" => "wikidata",
						"prio" => '3',
						"notice" => 'Description for language <em>'.$wi->lang.'</em> is missing',
						"name" => 'Wikidata',
						"explain" => 'See: <a href="//www.wikidata.org/wiki/Help:Description" >Help:Description</a> ',
				);
			}
			else{
				unset( $this->wikidataProps[$pos] );
			}
			
			if ( count($this->wikidataProps) == 0  ) {
				$this->checkResults['list'][] = array(
						"source" => "wikidata",
						"prio" => '1',
						"notice" => 'Item has no properties.',
						"name" => 'Wikidata',
						"explain" => 'See: <a href="//www.wikidata.org/wiki/Wikidata:List_of_properties/'.$wi->lang.'" >Wikidata:List of properties</a> ',
				);
			}
			else{
			
				//P31 = instancce of Q5 = human, P21 = sex
				if ( in_array('P31', $this->wikidataProps) && in_array('Q5', $this->wikidataProps) && !in_array('P21', $this->wikidataProps) ) {
					$this->checkResults['list'][] = array(
							"source" => "wikidata",
							"prio" => '2',
							"notice" => 'Missing sex or gender.',
							"name" => 'Wikidata',
							"explain" => 'See: <a href="//www.wikidata.org/wiki/Wikidata:List_of_properties/'.$wi->lang.'" >Wikidata:List of properties</a>',
					);
				}
				//Q5 = human, P21 = sex
				if ( in_array('P31', $this->wikidataProps) && in_array('Q5', $this->wikidataProps) && (!in_array('P569', $this->wikidataProps)) ) {
					$this->checkResults['list'][] = array(
							"source" => "wikidata",
							"prio" => '2',
							"notice" => 'Missing date of birth. ',
							"name" => 'Wikidata',
							"explain" => 'See: <a href="//www.wikidata.org/wiki/Wikidata:List_of_properties/'.$wi->lang.'" >Wikidata:List of properties</a>',
					);
				}
				//Q571 = book, [Q8261] => novel, [P50] => author
				if ( (in_array('Q571', $this->wikidataProps) || in_array('Q826', $this->wikidataProps) ) && !in_array('P50', $this->wikidataProps) ) {
					$this->checkResults['list'][] = array(
							"source" => "wikidata",
							"prio" => '2',
							"notice" => 'Missing author of book or novel.',
							"name" => 'Wikidata',
							"explain" => 'See: <a href="//www.wikidata.org/wiki/Wikidata:List_of_properties/'.$wi->lang.'" >Wikidata:List of properties</a> ',
					);
				}
				//[P31] => instance of [P279] => subclass of, [P361] => part of
				if ( !(in_array('P31', $this->wikidataProps) || in_array('P279', $this->wikidataProps) || in_array('P361', $this->wikidataProps)) ) {
					$this->checkResults['list'][] = array(
							"source" => "wikidata",
							"prio" => '2',
							"notice" => 'Missing basic membership properties (instance of / subclass of / part of)',
							"name" => 'Wikidata',
							"explain" => 'See: <a href="//www.wikidata.org/wiki/Help:Basic_membership_properties/'.$wi->lang.'" >Help:Basic membership properties</a>',
					);
				}
			}
		}
		
		if( $this->langToolTHtml ){
			libxml_use_internal_errors(true);
			
			$dom = new DOMDocument();
			$dom->validateOnParse = true;
			$dom->loadHTML( $this->langToolTHtml );
			$dom->preserveWhiteSpace = false;

			if ($ul = $dom->getElementById('replMaximum') ){
				$elmnts = $ul->parentNode->childNodes;
	
				foreach ( $elmnts as $tblnode){
					if ( $tblnode->nodeName == "table" ){
						$i=0;
						foreach ($tblnode->childNodes as $rownode ){
							if ($i == 1){
								$f1 = trim(str_replace(array('<td colspan="2">','</td>'), array('',''), $rownode->firstChild->C14N() ))."\n";
							}
							if ($i == 2){
								$f2 = trim(str_replace(array('\r\n',"\n"), array('',''), $rownode->firstChild->nodeValue) ) ."\n";
								$f3 = trim(str_replace(array('<td>','</td>'), array('',''), $rownode->firstChild->nextSibling->C14N() )) ."\n";
							}
							$i++;
						}
						$this->checkResults['list'][] = array(
								"source" => "languagetool",
								"prio" => '2',
								"notice" => $f2.$f3,
								"name" => 'Grammar',
								"explain" => $f1,
						);
					}
				}
			}
			libxml_clear_errors();
			unset($dom); 
		}
		
		if( $this->enwp10Html && strpos($this->enwp10Html, '<table class="wikitable"') > 0 ){
			libxml_use_internal_errors(true);
				
			$dom = new DOMDocument();
			$dom->validateOnParse = true;
			$dom->loadHTML( $this->enwp10Html );
			$dom->preserveWhiteSpace = false;
		
			if ($cont = $dom->getElementById('content') ){
				$elmnts = $cont->childNodes;
				foreach ( $elmnts as $tblnode){
					if ( $tblnode->nodeName == "center" ){
						$this->enwp10Html = str_replace('class="wikitable"', 'class="table-condensed table-striped  xt-table"', $tblnode->firstChild->C14N() );
					}
				}
			}
			libxml_clear_errors();
			unset($dom);
		}
		
		
		$this->perflog[] = $this->enwp10Html;
		
		$highest = 99;
		foreach ($this->checkResults["list"] as $i => $row){
			$this->checkResults["prios"][$row["prio"]]++;
			@$this->checkResults["sources"][$row["source"]]++;
			if($row["prio"] < $highest ){ $highest = $row["prio"];}
		}
		$this->checkResults["color"] = "";
		if ( $highest == '3' ) { $this->checkResults["color"] = "border-bottom:1px dotted;color:darkblue"; }
		if ( $highest == '2' ) { $this->checkResults["color"] = "border-bottom:1px dotted;color:orange"; }
		if ( $highest == '1' ) { $this->checkResults["color"] = "border-bottom:1px dotted;color:red";  }
		
		uasort( $this->checkResults["list"], function($al, $bl) {
			$a = $al["prio"];
			$b = $bl["prio"];
			if ($a == $b) { return 0; }
			return ($a < $b) ? -1 : 1;
		});
		
		#file_put_contents('/data/project/xtools/api_error', date('Y-m-d H:i:s')."\t".$this->pagetitle."\t".$wi->domain."\t len:".strlen($this->langToolTHtml)."\t".json_encode($this->checkResults)."\n\n", FILE_APPEND );
		#$this->perflog[] = $this->checkResults;
	}
	
	private function parseReverts(){
		
		//Create an array with revision_id -> length assoc.
		foreach ( $this->history as $i => $rev ){
				
			$curlen = $rev["rev_len"];
			$this->markedRevisions[ $rev["rev_id"] ]["rev_len"] = $curlen;
				
			if ($i == 0 || ( isset($this->markedRevisions[ $rev["rev_id"] ]["revert"]) && $this->markedRevisions[ $rev["rev_id"] ]["revert"] )  )
				continue;
				
			$this->markedRevisions[ $rev["rev_id"] ]["revert"] = false;
				
			$prevlen = $this->history[$i-1]["rev_len"];
				
			$curdiff = abs($curlen - $prevlen);
			if ( $curdiff > 100 ){
				for ($u=0; $u <10; $u++){
					if( ($i + $u) >= $this->historyCount ) { break; }
					
					$nextlen = $this->history[$i+$u]["rev_len"];
						
					if ( abs($nextlen - $prevlen) < 50 ){
						for ($x=0; $x <= $u; $x++){
							$this->markedRevisions[ $this->history[$i+$x]["rev_id"] ]["revert"] = true;
						}
						break;
					}
				}
			}
		}
	}

	private function parseLogs (){
		$pstart = microtime(true);
		
		if( !isset($this->tmpLogs) ){ return; }
		
		foreach( $this->tmpLogs as $log ) {
			if( !in_array( $log['action'], array( 'revision' ) ) ) {
				
				$time = date('Ym', strtotime( $log['timestamp'] ) );
				$year = date('Y', strtotime( $log['timestamp'] ) );
				
#				$this->pageLogs["months"][ $time ]["duringdate"] = date('m/Y', strtotime( $log['timestamp'] ) );
				
				if( !isset( $this->pageLogs["months"][ $time ][$log['action'] ] ) ) {
					$this->pageLogs["months"][ $time ][$log['action']] = 0;
				}
				if( !isset( $this->pageLogs["years"][ $year ][$log['action'] ] ) ) {
					$this->pageLogs["years"][ $year ][$log['action']] = 0;
				}
				
				 
				$this->pageLogs["months"][ $time ][$log['action']]++;
				$this->pageLogs["years"][ $year ][$log['action']]++;
			}
		}
		unset( $this->tmpLogs );
		
		ksort( $this->pageLogs["months"] );
		ksort( $this->pageLogs["years"] );

		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	

	public function parseHistory() {
		$pstart = microtime(true);
		
		if ( $this->historyCount == 0 ){ 
			$this->error = "no records";
			return;
		}
	
		//Now we can start our master array. This one will be HUGE!
		$data = array(
			'first_edit' => array(
				'timestamp' => $this->history[0]['rev_timestamp'],
				'revid' => $this->history[0]['rev_id'],
				'user' => $this->history[0]['rev_user_text']
				),
			'last_edit' => array(
				'timestamp' =>	$this->history[ $this->historyCount-1 ]['rev_timestamp'],
				'revid' => $this->history[ $this->historyCount-1 ]['rev_id'],
				'user' => $this->history[ $this->historyCount-1 ]['rev_user_text']
				),
			'max_add' => array(
				'timestamp' =>	null,
				'revid' => null,
				'user' => null,
				'size' => -1000000,
			),
			'max_del' => array(
				'timestamp' =>	null,
				'revid' => null,
				'user' => null,
				'size' => 1000000,
			),
			'year_count' => array(),
			'count' => 0,
			'editors' => array(),
			'anons' => array(),
			'year_count' => array(),
			'minor_count' => 0,
			'count_history' => array( 'today' => 0, 'week' => 0, 'month' => 0, 'year' => 0 ),
			'current_size' => $this->history[ $this->historyCount-1 ]['rev_len'],
			'textshares' => array(),
			'textshare_total' => 0,
			'tools' => array(),
			'automated_count' => 0,
			'bots' => array(),
		);
		
		$first_edit_parse = date_parse( $data['first_edit']['timestamp'] );
	
#print_r($this->history);	
	
	
		//And now comes the logic for filling said master array
		foreach( $this->history as $id => $rev ) {

			$data['count']++;
			
			//Sometimes, with old revisions (2001 era), the revisions from 2002 come before 2001
			if( strtotime( $rev['rev_timestamp'] ) < strtotime( $data['first_edit']['timestamp'] ) ) {	
				$data['first_edit'] = array(
					'timestamp' => $rev['rev_timestamp'],
					'user' => htmlspecialchars( $rev['rev_user_text'] )
				);
				
				$first_edit_parse = date_parse( $data['first_edit']['timestamp'] );
			}
			
			
			$timestamp = date_parse( $rev['rev_timestamp'] );
			
			
			//Fill in the blank arrays for the year and 12 months
			if( !isset( $data['year_count'][$timestamp['year']] ) ) {
				$data['year_count'][$timestamp['year']] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'months' => array() );
				
				for( $i = 1; $i <= 12; $i++ ) {
					$data['year_count'][$timestamp['year']]['months'][$i] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'size' => array() );
				}
			}
			
			//Increment counts
			$data['year_count'][$timestamp['year']]['all']++;
			$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['all']++;
			$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['size'][] = number_format( ( $rev['rev_len'] / 1024 ), 2 );
			
			
			//Now to fill in various user stats
			$username = htmlspecialchars($rev['rev_user_text']);
			if( !isset( $data['editors'][$username] ) ) {
				$data['editors'][$username] = array( 	
					'all' => 0, 
					'minor' => 0, 
					'first' => date( 'Y-m-d, H:i', strtotime( $rev['rev_timestamp'] ) ), 
					'last' => null, 
					'atbe' => null, 
					'minorpct' => 0, 
					'size' => array(), 
					'urlencoded' => rawurlencode( $rev['rev_user_text'] ), //str_replace( array( '+' ), array( '_' ), urlencode( $rev['rev_user_text'] ) )
				);
			}
			
			//Increment these counts...
			$data['editors'][$username]['all']++;	
			$data['editors'][$username]['last'] = date( 'Y-m-d, H:i', strtotime( $rev['rev_timestamp'] ) );	
			$data['editors'][$username]['size'][] = number_format( ( $rev['rev_len'] / 1024 ), 2 );
			
			$revdiff = ($rev["rev_parent_id"] != 0) ? $rev["rev_len"] - $this->markedRevisions[ $rev["rev_parent_id"] ]["rev_len"] : $rev["rev_len"];
			$revert = isset($this->markedRevisions[ $rev["rev_id"] ]["revert"]) ? $this->markedRevisions[ $rev["rev_id"] ]["revert"] : false;
			
			if ( !$revert ){
				if ($revdiff > 0){ 
					$data['textshare_total'] += $revdiff;
					if( !isset( $data['textshares'][$username]['all'] ) ){
						$data['textshares'][$username]['all'] = 0;
					}
					$data['textshares'][$username]['all'] += $revdiff;
				}
				if ( $revdiff > $data["max_add"]["size"] ){
					$data["max_add"]['timestamp'] =	$rev["rev_timestamp"];
					$data["max_add"]['revid'] = $rev["rev_id"];
					$data["max_add"]['user'] = $rev["rev_user_text"];
					$data["max_add"]['size'] = $revdiff;
				}
				if ( $revdiff < $data["max_del"]["size"] ){
					$data["max_del"]['timestamp'] =	$rev["rev_timestamp"];
					$data["max_del"]['revid'] = $rev["rev_id"];
					$data["max_del"]['user'] = $rev["rev_user_text"];
					$data["max_del"]['size'] = $revdiff;
				}
			}	
			
			
			if( !$rev['rev_user'] ) {
				//Anonymous, increase counts
				$data['anons'][] = $username;
				$data['year_count'][$timestamp['year']]['anon']++;
				$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['anon']++;
			}
			
			if( $rev['rev_minor_edit'] ) {
				//Logged in, increase counts
				$data['minor_count']++;
				$data['year_count'][$timestamp['year']]['minor']++;
				$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['minor']++;
				$data['editors'][$username]['minor']++;	
			}
			
// 			if ( $this->checkAEB ){
// 				foreach ( $this->AEBTypes as $tool => $signature ){
// 					if ( preg_match( $signature["regex"], $rev["rev_comment"]) ){
// 						$data['automated_count']++;
// 						$data['year_count'][$timestamp['year']]['automated']++;
// 						$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['automated']++;
// 						$data['tools'][$tool]++;
// 						break;
// 					}
// 				}
// 			}
			
			//Increment "edits per <time>" counts
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 day' ) ) $data['count_history']['today']++;
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 week' ) ) $data['count_history']['week']++;
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 month' ) ) $data['count_history']['month']++;
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 year' ) ) $data['count_history']['year']++;
			
		}
		
		unset($this->history );
	
	
	//Fill in years with no edits
		for( $year = $first_edit_parse['year']; $year <= date( 'Y' ); $year++ ) {
			if( !isset( $data['year_count'][$year] ) ) {
				$data['year_count'][$year] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'months' => array() );
				
				for( $i = 1; $i <= 12; $i++ ) {
					$data['year_count'][$year]['months'][$i] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'size' => array() );
				}
			}
		}
	
	
	//Add more general statistics
		$dateFirst = DateTime::createFromFormat('YmdHis', $data['first_edit']['timestamp']);
		$dateLast  = DateTime::createFromFormat('YmdHis', $data['last_edit']['timestamp']);
		$interval = date_diff($dateLast, $dateFirst, true);
		
		$data['totaldays'] = $interval->format('%a');
		$data['average_days_per_edit'] = $data['totaldays'] / $data['count'];
		$data['edits_per_month'] = ( $data['totaldays'] ) ? $data['count'] / ( $data['totaldays'] / ( 365/12 ) ) : 0;
		$data['edits_per_year'] = ( $data['totaldays'] ) ? $data['count'] / ( $data['totaldays'] / 365 )  : 0;
		$data['edits_per_editor'] = $data['count'] / count( $data['editors']);
		$data['editor_count'] = count( $data['editors'] );
		$data['anon_count'] = count( $data['anons'] );

	//Various sorts
		arsort( $data['editors'] );
		arsort( $data['textshares'] );
		ksort( $data['year_count'] );
	
	
	
	//Fix the year counts
		$num = 0;
		$cum = 0;
		$scum = 0;
		
		foreach( $data['year_count'] as $year => $months ) {
			
			//Unset months before the first edit and after the last edit
			foreach( $months['months'] as $month => $tmp ) {
				if( $year == $first_edit_parse['year'] ) {
					if( $month < $first_edit_parse['month'] ) unset( $data['year_count'][$year]['months'][$month] );
				}
				if( $year == date( 'Y' ) ) {
					if( $month > date( 'm' ) ) unset( $data['year_count'][$year]['months'][$month] );
				}
			}
			
			
			//Calculate anon/minor percentages
			$data['year_count'][$year]['pcts']['anon'] = ( $data['year_count'][$year]['all'] ) ? number_format( ( $data['year_count'][$year]['anon'] / $data['year_count'][$year]['all'] ) * 100, 2 ) : 0.00;
			$data['year_count'][$year]['pcts']['minor'] = ( $data['year_count'][$year]['all'] ) ? number_format( ( $data['year_count'][$year]['minor'] / $data['year_count'][$year]['all'] ) * 100, 2 ) : 0.00;
			
			
			//Continue with more stats...
			foreach( $data['year_count'][$year]['months'] as $month => $tmp ) {
			
				//More percentages...
				$data['year_count'][$year]['months'][$month]['pcts']['anon'] = ( $tmp['all'] ) ? number_format( ( $tmp['anon'] / $tmp['all'] ) * 100, 2 ) : 0.00;
				$data['year_count'][$year]['months'][$month]['pcts']['minor'] = ( $tmp['all'] ) ? number_format( ( $tmp['minor'] / $tmp['all'] ) * 100, 2 ): 0.00;
				
				//XID and cumulative are used in the flash graph
				$data['year_count'][$year]['months'][$month]['xid'] = $num;
				$data['year_count'][$year]['months'][$month]['cumulative'] = $cum + $tmp['all'];
				
				if( count( $tmp['size'] ) ) {
					$data['year_count'][$year]['months'][$month]['size'] = number_format( ( array_sum( $tmp['size'] ) / count( $tmp['size'] ) ), 2 );
				}
				else {
					$data['year_count'][$year]['months'][$month]['size'] = 0;
				}
				
				$data['year_count'][$year]['months'][$month]['sizecumulative'] = $scum + $data['year_count'][$year]['months'][$month]['size'];
				$num++;
				$cum += $tmp['all'];
				$scum += $data['year_count'][$year]['months'][$month]['size'];
			}
		}
	
	
	//Top 10% info
		$data['top_ten'] = array( 'editors' => array(), 'count' => 0 );
	
	//Now to fix the user info...
		$tmp = $tmp2 = 0;
		foreach( $data['editors'] as $editor => $info ) {
			
			//Is the user in the top 10%?
			if( $tmp <= (int)( count( $data['editors'] ) * 0.1 ) ) {
				$data['top_ten']['editors'][] = $editor;
				$data['top_ten']['count'] += $info['all'];
				
				$tmp++;
			}
			
			$data['editors'][$editor]['minorpct'] = ( $info['all'] ) ?  ( $info['minor'] / $info['all'] ) * 100 : 0 ;
			
			if( $info['all'] > 1 ) {
				$secs = intval( ( strtotime( $info['last'] ) - strtotime( $info['first'] ) ) / $info['all'] );
				$data['editors'][$editor]['atbe'] = $secs / (60*60*24) ;
			}
			
			if( count( $info['size'] ) ) {
				$data['editors'][$editor]['size'] = array_sum( $info['size'] ) / count( $info['size'] ) ;
			}
			else {
				$data['editors'][$editor]['size'] = 0;
			}
			
			$tmp2++;
		}
		
		
		//Parse the botedits
		$sum = 0;
		foreach ( $this->tmpBotedits as $i => $bots ){
			$data["bots"][ $bots["rev_user_text"] ] = $bots["count"];
			$sum += $bots["count"];
		}
		$data["botedit_count"] = $sum;
		if ( $data["bots"] ){
			arsort( $data["bots"] );
		}
		unset( $this->tmpBotedits );
		
		$this->data = $data;
		
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	

	
	/**
	 * Calculate how many pixels each year should get for the Edits per Year table
	 * @param unknown $data
	 * @return Ambigous <multitype:multitype: , number>
	 */
	public function getYearPixels() {
		
		$month_total_edits = array();

		foreach( $this->data['year_count'] as $year => $tmp ) {
			$month_total_edits[$year] = $tmp['all'];
		}
	
		$max_width = max( $month_total_edits );
	
		$pixels = array();
		foreach( $this->data['year_count'] as $year => $tmp ) {
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
	
	
	/**
	 * Calculate how many pixels each month should get for the Edits per Month table
	 * @param unknown $data
	 * @return multitype:
	 */
	function getMonthPixels() {
		
		$month_total_edits = array();
		
		foreach( $this->data['year_count'] as $year => $tmp ) {
			foreach( $tmp['months'] as $month => $newdata ) {
				$month_total_edits[ $month.'/'.$year ] = $newdata['all'];
			}
		}
	
		$max_width = max( $month_total_edits );
	
		$pixels = array();
		foreach( $this->data['year_count'] as $year => $tmp ) {
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
	

	/**
	 * Returns a list of even years, used to generate contrasting colors for the Edits/Month table 
	 * @param unknown $years
	 * @return Ambigous <string, multitype:>
	 */
	public function getEvenYears() {
		$years = array_keys( $this->data['year_count'] );
		$years = array_flip( $years );
		
		foreach( $years as $year => $id ) {
			$years[$year] = "5";
			if( $year % 2 == 0 ) unset( $years[$year] );
		}
		return $years;
	}
	
	/**
	 * Load AEB Types from counter, to parse (semi) automated edits
	 */
	function loadAEBTypes(){
		try {
			require_once 'Counter.php';
			$this->AEBTypes = Counter::getAEBTypes();
		}
		catch (Exception $e){
			$this->AEBTypes = null;
		}

		
	}


}
