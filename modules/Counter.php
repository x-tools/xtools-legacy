<?php

/*
Soxred93's Edit Counter
Copyright (C) 2010 Soxred93

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

Completely rewritten and adjusted
(C) 2014 Hedonil
 
*/

class Counter {
	
	public $baseurl;
	public $apibase;
	public $error = false;

	public $mNamespaces = array();
	public $timeCard = array( 'matrix' => array() );
	
	private $mIP;
	private $mExists;
	
	public $mUID;
	public $mName;
	private $mNameDbEnc;
	private $mNameUrlEnc;
	
	public $mDomain;
	public $mRegistration;
	public $mRegistrationNormal;
	public $mGroups;
	public $mGroupsGlobal;
	public $mHomeWiki;
	public $mRegisteredWikis;
	public $mSULEditCount;

	
	public $mUniqueDeleted;
	public $mReverted = 'n.a.';
	public $mDeleted;
	public $mDeletedCreated;
	public $mAutoEdits = 'n.a.';
	public $mUploaded;
	public $mUploadedCommons;
	public $mBlockedCurrent = null;
	public $mBlockedNum = '0';
	public $mBlockedLongest = '–';

	public $mLatestEditsGlobal;

	public $mUnique = 0;
	public $mCreated = 0;
	public $mEditsSummary = 0;
	public $mMinorEdits = 0;
	public $mFirstEdit = '20991231000000';
	public $mLatestEdit = '00000000000000';
	public $mEditDays = 1;
	
	public $mLive;
	public $mTotal;
	public $mTotalGlobal;
	public $mAveragePageEdits;
	
	public $mSumLen;
	public $mAverageLen;
	public $mMinorEditsByte;
	public $mMajorEditsByte;
	public $mLastDay;
	public $mLastWeek;
	public $mLastMonth;
	public $mLastYear;

	
	public $mNamespaceTotals = array();
	public $mLogActions = array("thanks/thank"=>0, "review/approve"=>0, "review/unapprove"=>0, "patrol/patrol"=>0, "block/block"=>0, "block/unblock"=>0,
								"protect/protect"=>0, "protect/unprotect"=>0, "delete/delete"=>0, "delete/revision"=>0, "delete/restore"=>0, "import/interwiki"=>0,
								"move/move"=>0, "move/move_redir"=>0, "upload/upload"=>0 );
	public $mMonthTotals = array();

	
	private $mUniqueArticles = array( 'total', 'namespace_specific' );
	private $mUniqueArticlesDeleted = array();
	
	public $mAutoEditTools = array();

	private $parentRevs = array();
	
	private $optinPages = array();
	public $optin = false;
	
	public $wikis = array();

	private $perflog;
	
	private $tmpDB = XTOOLS_DATABASE_TMP;
	private $tmpTable = '';
	private $tmpTableParent = '';
	private $tblFlaggedRevsExist;
	
	private $FROM;
	public $extended;
	
	
	function __construct( &$dbr, $user, $domain, $noautorun=false, $editcount=0, $extended=false ) {
		global $wt, $redis;
		
		$this->extended = ( $extended ) ? true : false;
		
		$this->baseurl = 'https://'.$domain;
		$this->apibase = $this->baseurl.'/w/api.php?';
		
		$this->mDomain = $domain;
		$this->mName = trim( urldecode( $user ) );
		$this->mNameDbEnc = $dbr->strencode( $this->mName );
		$this->mNameUrlEnc = rawurlencode( $this->mName );
		
		$longQueue = false;
		if( $editcount > 200000 ) {
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
		
		$this->checkIP();
		if ( $this->mIP ){
			$this->fetchUserDataIP( $dbr );		
		}
		else{
			$this->fetchUserData( $dbr );
			$this->checkOptin();
		}
		
		$this->mNamespaces = $wt->namespaces;
		
		if ( !$this->mExists || $noautorun ) return ;
		
				
		$this->prepareData($dbr);
		$this->fetchData1($dbr);
		$this->fetchData2($dbr);
		$this->clearData($dbr);
		
#		$this->fillMonthList();

		if ($longQueue ) { $redis->decr('longQueueCount'); }

		global $perflog;
		array_push( $perflog->stack, $this->perflog);
	}

	
	function checkIP(){
		
		#$this->mIP = ( long2ip( ip2long( $this->mName ) ) == $this->mName ) ? true : false;
		if ( @inet_pton( $this->mName ) === false ){
			return;
		}
		else{
			$this->mName = strtoupper( $this->mName );
			$this->mNameDbEnc = $this->mName;
			$this->mNameUrlEnc = $this->mName;
			$this->mIP = true; 
			$this->mExists = true;
			$this->mUID = 0;
			$this->mGroups = array('–');
			$this->optin = true;			
		}
	}
	
	function checkOptin(){
		global $wt;
		
		if ( $this->mName == $wt->loggedInUsername ){
			$this->optin = true;
			return;
		}
		
		if( $this->mDomain == "en.wikipedia.org" || $this->mIP ){
			$this->optin = true;
			return;
		}
		
		if ( in_array( "bot", $this->mGroups ) ){
			$this->optin = true;
			return;
		}
				
		foreach ($this->optinPages as $site ){
			
			foreach ($site as $i => $optinPage) {
				if ( strpos ($optinPage["page_title"], "OptOut.js") ){
					$this->optin = false;
					break(2);
				}
				if ( strpos ($optinPage["page_title"], "OptIn.js") ){
					$this->optin = true;
					break(2);
				}
			}
		}
	}
	
	function fetchUserData( $dbr ){
		$pstart = microtime(true);
		
		//Get lokal user info
		$data = array(
				'action' => 'query',
				'list' 	 => 'users',
				'meta' => 'globaluserinfo',
				'format' => 'json',
				'usprop' => 'blockinfo|groups|implicitgroups|editcount|registration',
				'guiprop' => 'groups|merged|unattached|editcount',
				'ususers'=> $this->mName,
				'guiuser' => $this->mName
			);
		$query[] = array( "type" => "api", "src" => "", "timeout" => 0, "query" => $this->apibase.http_build_query( $data ) );
		
		
		$db = $dbr->getCurrentDatabase();
		$username = str_replace(' ', '_', $this->mNameDbEnc);  //username as pagetitle requires _

		$query[] = array(
				"type" => "db",
				"src" => "slice s1.labsdb",
				"timeout" => 0,
				"query" => "
					(SELECT page_title
					FROM ${db}.page
					WHERE page_namespace = 2 AND page_title like '$username/EditCounterOpt%.js')
					UNION
					(SELECT page_title
					FROM metawiki_p.page
					WHERE page_namespace = 2 AND page_title like '$username/EditCounterGlobalOpt%.js')
				"
			);
		
	
		//Execute multiquery
		$multires = $dbr->multiquery ($query );
		
		
		//Get the outputs
		$res = $multires[0]->query->users[0];
		
		if ( isset( $res->userid) ){
			$this->mUID = $res->userid;
			$this->mName = urldecode($res->name);
			$this->mSULEditCount = $res->editcount;
			$this->mGroups = $res->groups;
				unset($this->mGroups[ array_search("*", $this->mGroups) ]);
			$this->mRegistration = $res->registration;
			$this->mRegistrationNormal = isset( $this->mRegistration ) ? str_replace("-", "", substr($this->mRegistration, 0,10) ) : 1;
			$this->mBlockedCurrent = isset($res->blockid) ? $res->blockexpiry : null; 
			$this->mExists = true;
		}
		else {
			$this->mExists = false;
		}
		unset($res);
		
		
		$res = $multires[0]->query->globaluserinfo;
		
		if ( isset( $res->id) ){
			$this->mGroupsGlobal = $res->groups;
			$this->mHomeWiki = $res->home;
		}
		$sum = 0;
		if ( isset( $res->merged) ) {	
			foreach ( $res->merged as $wiki ){
				$this->wikis[ $wiki->wiki] = array( 
							"editcount" => $wiki->editcount, 
							"registraion" => str_replace(array('-',':','T','Z'), array('','','',''), $wiki->timestamp),
							"blocked" => null, 
						);
				$sum += $wiki->editcount;
			}
		}
		if (isset($res->unattached )){
			foreach ( $res->unattached as $wiki ){
				$this->wikis[ $wiki->wiki ] = array( "editcount" => $wiki->editcount, "registraion" => @$wiki->timestamp );
				$sum += $wiki->editcount;
			}
		}
		arsort($this->wikis);
		unset($res);
		
		$this->mRegisteredWikis = count($this->wikis);
		$this->mTotalGlobal = $sum;
		unset($res);
		
		$this->optinPages[] = $multires[1]; //->query->pages ;
						

		$this->perflog[] = $this->optinPages;
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	
	function fetchUserDataIP( $dbr ){
		global $wt;
		
		$where = "rev_user_text = '$this->mNameDbEnc' ";
		
		foreach ( $wt->metap as $db => $row ){
			if ($db == "centralauth" ) { continue; }
			$subquery[ $row["slice"] ][] = "
					(SELECT '$db' as wiki, count(*) as editcount
					FROM ${db}_p.revision_userindex
					where  $where )
				";
		}
		$subcount = 0;
		foreach ( $subquery as $slice => $queries ){
			$query[] = array(
				"type" => "db",
				"src" => "slice ".$slice,
				"timeout" => 0,
				"query" => implode(" UNION ", $queries )
				);
			$subcount++;
		}
		
		$res = $dbr->multiquery( $query );
		
		$sum = 0;
		for( $i=0; $i <= $subcount; $i++ ){
			foreach ($res[ $i ] as $row ){
				$this->wikis[ $row["wiki"] ] = array( "editcount" => $row["editcount"], "registraion" => null );
				$sum += $row["editcount"];
			}
		}
		
		$this->mRegisteredWikis = count($this->wikis);
		$this->mTotalGlobal = $sum;
		
		arsort($this->wikis);
		unset( $res );
		
	}

//
// ****************************           fetch data          **********************
//	
	function prepareData($dbr){
		$pstart = microtime(true);
		
		$this->tmpTable = date('YmdHis') . hash('md5', $this->mDomain.$this->mName );
		$this->tmpTableParent = $this->tmpTable."_parent";
		$engine = ( $this->mSULEditCount < 50000 ) ? "MEMORY" : "MyISAM";  //;"InnoDB"; //
#		

		$dbr->query( "
					CREATE TABLE `$this->tmpDB`.`$this->tmpTable` (
					  `page_namespace` int(11) NOT NULL DEFAULT '0',
					  `rev_page` int(8) unsigned NOT NULL DEFAULT '0',
					  `rev_timestamp` varbinary(14) NOT NULL DEFAULT '',
					  `rev_minor_edit` tinyint(1) unsigned NOT NULL DEFAULT '0',
					  `rev_parent_id` int(8) unsigned NOT NULL DEFAULT '0',
					  `rev_len` int(8) unsigned NOT NULL DEFAULT '0',
					  `rev_comment` varbinary(42) DEFAULT ''
					) ENGINE=$engine DEFAULT CHARSET=BINARY
			");
#Aria PAGE_CHECKSUM=0 TABLE_CHECKSUM=0 TRANSACTIONAL=0 ROW_FORMAT=FIXED
#MyISAM ROW_FORMAT=FIXED
		$this->perflog[] = $dbr->dbo->error;
		
		$dbr->query( "
					CREATE TABLE IF NOT EXISTS `$this->tmpDB`.`$this->tmpTableParent` (
					`rev_id` int(8) unsigned DEFAULT '0',
					`rev_len` int(8) unsigned DEFAULT '0',
					KEY `revid` (`rev_id`)
					) ENGINE=MEMORY  DEFAULT CHARSET=BINARY
				");
		$this->perflog[] = $dbr->dbo->error;
		
		$res = $dbr->query("
					SELECT table_name 
					FROM information_schema.tables
					WHERE table_schema = (select schema()) AND table_name = 'flaggedrevs_promote' LIMIT 1
				");
		if ( $res[0]["table_name"] == "flaggedrevs_promote" ) {
			$this->tblFlaggedRevsExist = true;
		}
		
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	
	function clearData( $dbr ){
		$dbr->query( "DROP TABLE IF EXISTS `$this->tmpDB`.`$this->tmpTable` ");
		$dbr->query( "DROP TABLE IF EXISTS `$this->tmpDB`.`$this->tmpTableParent` ");
	}
	
	function fetchData1( $dbr ){
		global $wt;
		$pstart = microtime(true);
		
		$res = array();

#		$timeArr = array('2002','2005','2008','2010','2012','2015');
#		for( $i=0; $i < count($timeArr) -1 ; $i++ ){
#			$wheretime = "AND (a.rev_timestamp >= '".$timeArr[$i]."' AND a.rev_timestamp < '".$timeArr[$i+1]."' ) ";
		$where  = ( $this->mIP ) ? " a.rev_user_text = '$this->mNameDbEnc' " :  " a.rev_user = '$this->mUID' AND  a.rev_timestamp > $this->mRegistrationNormal ";
#		$where  = ( true || $this->mIP ) ? " a.rev_user_text = '$this->mNameDbEnc' " :  " a.rev_user = '$this->mUID' ";
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					/*SLOW_OK ".$timeArr[$i]." USER_QUERY_MAIN*/
					INSERT HIGH_PRIORITY IGNORE INTO `$this->tmpDB`.`$this->tmpTable`
				 	SELECT 
						  page_namespace
						, a.rev_page
						, a.rev_timestamp
						, a.rev_minor_edit
						, IFNULL(a.rev_parent_id,0)
						, IFNULL(a.rev_len,0)
						, CONCAT( LEFT(SUBSTRING_INDEX( a.rev_comment, '*/', -1),20), RIGHT(a.rev_comment,20) ) as rev_comment
					FROM revision_userindex a
					JOIN page ON page_id = a.rev_page
					WHERE  $where 
				"
			);
#		}

		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => ( !$this->extended ) ? " SELECT current_user() " : "
					/*SLOW_OK USER_QUERY_PAR*/
					INSERT HIGH_PRIORITY IGNORE INTO `$this->tmpDB`.`$this->tmpTableParent`
					SELECT parent.rev_id as previd, parent.rev_len as prevlen
					FROM revision_userindex AS a
					JOIN revision_userindex AS parent on a.rev_parent_id = parent.rev_id   
					WHERE  $where 

					"	
				);
				
		
		$where  = ( $this->mIP ) ? " b.log_id = '-1'  " : " b.log_user = '$this->mUID' AND b.log_timestamp > '$this->mRegistrationNormal' ";
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					(SELECT b.log_type, '' as log_action, count(*) as count
					FROM logging_userindex b 
					WHERE b.log_type in('review','thanks','block','protect','delete','patrol','import','upload','move') AND  $where 
					Group BY b.log_type)
					UNION
					(SELECT b.log_type, b.log_action, count(*) as count
					FROM logging_userindex b 
					WHERE  b.log_type = 'review' and b.log_action ='approve' AND  $where 
					)
				"
			);
#( b.log_type = 'review' OR b.log_type = 'thanks' OR b.log_type = 'block' OR b.log_type = 'protect' OR b.log_type = 'delete' OR b.log_type = 'patrol' OR b.log_type = 'import' OR b.log_type = 'upload' OR b.log_type = 'move')
		
		$where = ( $this->mIP ) ? "ar_user_text = '$this->mNameDbEnc' AND ar_timestamp > '1' " : "ar_user_text = '$this->mNameDbEnc' AND ar_timestamp > '$this->mRegistrationNormal' ";
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					/*SLOW_OK USER_QUERY*/
					SELECT COUNT(ar_id) AS count, CAST(SUM( IF(ar_parent_id = '0',1,0) ) as unsigned) as countcreated, count(distinct ar_title) as countdistinct
					FROM archive_userindex
					WHERE  $where
				"
			);
		
		$where = ( $this->mIP ) ? "frp_user_id = '999999999' " : "frp_user_id = '$this->mUID' ";
		$flquery = ( $this->tblFlaggedRevsExist ) ? " (SELECT frp_user_params FROM flaggedrevs_promote WHERE  $where ) " : " (Select NULL )"; 
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					/*SLOW_OK USER_QUERY*/
					SELECT IFNULL( $flquery, 'n.a.' ) as frp_user_params
				"
			);
		
		$where = ( $this->mIP ) ? "log_id = '-1' " : "user_name= '$this->mNameDbEnc' ";
		$query[] = array(
				"type" => "db",
				"src" => "slice s1.labsdb",
				"timeout" => 0,
				"query" => "
					SELECT count(log_type) as count 
					FROM commonswiki_p.logging_userindex  
					JOIN commonswiki_p.user ON user_id = log_user  
					WHERE log_action = 'upload' AND  $where
				"
			);
		
		$datenow = new DateTime();
		$datefrom = date_format($datenow->sub(new DateInterval("P30D")), "YmdHis");
		$knownwikis = array_keys($this->wikis);
		$where = "rev_user_text = '$this->mNameDbEnc' AND rev_timestamp > '$datefrom' ";
		
		foreach ( $wt->metap as $db => $row ){

			if ($db == "centralauth" || !in_array($db, $knownwikis) ) { continue; }
			
			$sublatestEdits[] =	"
						(SELECT rev_timestamp, '$db' as wiki, page_namespace, page_title, rev_id, rev_comment 
						FROM ${db}_p.revision_userindex
						JOIN ${db}_p.page on page_id = rev_page
						where  $where
						ORDER BY rev_timestamp desc
						Limit 10)
					";
			$subblocks[] =	"
						(SELECT '$db' as wiki, ipb_expiry 
						FROM ${db}_p.ipblocks_ipindex
						where ipb_address = '$this->mNameDbEnc')
					";
		}
		$query[] = array(
				"type" => "db",
				"src" => "slice s1.labsdb",
				"timeout" => 0,
				"query" => implode(" UNION ", $sublatestEdits )
			);
		
		$query[] = array(
				"type" => "db",
				"src" => "slice s1.labsdb",
				"timeout" => 0,
				"query" => implode(" UNION ", $subblocks )
		);
		
		
#		$this->perflog[] = implode(" ; ", $subquery2 );
		
		$res = $dbr->multiquery( $query );
		
		$offset = 1 ;
		
		$x = $offset + 1;
		foreach ( $res[$x] as $i => $row ){
			$this->mLogActions[ $row["log_type"].'/'.$row["log_action"] ] = $row["count"];
		}
		
		$x = $offset + 2;
		$this->mDeleted = $res[$x][0]["count"];
		$this->mDeletedCreated = $res[$x][0]["countcreated"];
		$this->mUniqueArticlesDeleted = $res[$x][0]["countdistinct"];
		
		$x = $offset + 3;
		if ( 1 === preg_match('/revertedEdits=([0-9]+)/', $res[$x][0]['frp_user_params'], $capt)){ $this->mReverted = $capt[1]; }
		
		$x = $offset + 4;
		$this->mUploadedCommons = $res[$x][0]["count"];
		
		$x = $offset + 5;
		foreach ($res[$x] as $i => $row ){
			$this->mLatestEditsGlobal["list"][] = $row;
			if ( !isset($this->mLatestEditsGlobal["latest"][ $row["wiki"] ]) ){
				$this->mLatestEditsGlobal["latest"][ $row["wiki"] ] = '0';
			}
			if ( $row["rev_timestamp"] > $this->mLatestEditsGlobal["latest"][ $row["wiki"] ] ){;
				$this->mLatestEditsGlobal["latest"][ $row["wiki"] ] = $row["rev_timestamp"];
			}
		}
		
		$x = $offset + 6;
		foreach ($res[$x] as $i => $row ){
			if ( $row["ipb_expiry"] ){
				$this->wikis[ $row["wiki"] ]["blocked"] = $row["ipb_expiry"];
			}
		}


		uasort( $this->mLatestEditsGlobal["list"], function($al, $bl) {
			$a = $al["rev_timestamp"];
			$b = $bl["rev_timestamp"];
			if ($a == $b) { return 0; }
			return ($a < $b) ? 1 : -1;
		});
		
		#$this->perflog[] = $this->mLatestEditsGlobal;

		unset($res);
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}

	
	function fetchData2( $dbr ){
		$pstart = microtime(true);
		
		$res = array();
		$curDB = $dbr->getCurrentDatabase();
		$curTime = time(); 
		
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					/*SLOW_OK USER_QUERY*/
					SELECT 
					  a.page_namespace
					, count(*) as count
					, count(distinct a.rev_page) as countdistinct
					, CAST(SUM( IF(a.rev_parent_id = '0',1,0) ) as Unsigned) as countcreated
					, CAST(SUM( IF(a.rev_comment != '',1,0) ) as Unsigned) as countsummary
					, CAST(SUM( IF(a.rev_minor_edit = '1' ,1,0) ) as Unsigned) as countminormarked
				
					, CAST(SUM( IF( UNIX_TIMESTAMP(a.rev_timestamp) > ($curTime - 86400) ,1,0) ) as Unsigned) as countlastday
					, CAST(SUM( IF( UNIX_TIMESTAMP(a.rev_timestamp) > ($curTime - 604800) ,1,0) ) as Unsigned) as countlastweek
					, CAST(SUM( IF( UNIX_TIMESTAMP(a.rev_timestamp) > ($curTime - 2592000) ,1,0) ) as Unsigned) as countlastmonth
					, CAST(SUM( IF( UNIX_TIMESTAMP(a.rev_timestamp) > ($curTime - 31536000) ,1,0) ) as Unsigned) as countlastyear
				
					,CAST( SUM( @diff:= IF( a.rev_len >= IFNULL(c.rev_len,0), a.rev_len - IFNULL(c.rev_len,0), IFNULL(c.rev_len,0) - a.rev_len  ) ) as Unsigned) as sumdiff 
					,CAST( SUM( IF(@diff < 20,1,0) ) as Unsigned) as countminorbyte
					,CAST( SUM( IF(@diff > 1000,1,0) ) as Unsigned) as countmajorbyte
					FROM `$this->tmpDB`.`$this->tmpTable` a
					LEFT JOIN `$this->tmpDB`.`$this->tmpTableParent` c on c.rev_id = a.rev_parent_id
					GROUP BY page_namespace
				"
			);
		
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					/*SLOW_OK USER_QUERY*/
					SELECT LEFT(rev_timestamp, 6) as month, page_namespace, count(rev_timestamp) as count
					FROM `$this->tmpDB`.`$this->tmpTable`
					GROUP BY month, page_namespace
				"
			);
		
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					/*SLOW_OK USER_QUERY*/
					SELECT 
						CAST( DAYOFWEEK(rev_timestamp) as UNSIGNED) as day, 
						CAST( TRUNCATE(SUBSTRING(rev_timestamp, 9,2) / 4, 0) as UNSIGNED) as hour, 
						COUNT(rev_timestamp) as count
					FROM `$this->tmpDB`.`$this->tmpTable`
					GROUP BY day, hour
				"
			);
		
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					/*SLOW_OK USER_QUERY*/
					SELECT c.page_namespace, e.page_title, c.count
					FROM
					(
						SELECT b.page_namespace, b.rev_page, b.count
						,@rn := if(@ns = b.page_namespace, @rn + 1, 1) as row_number
						,@ns := b.page_namespace as dummy
						FROM
						(
							SELECT page_namespace, rev_page, count(rev_page) as count
							FROM `$this->tmpDB`.`$this->tmpTable`
							GROUP BY page_namespace, rev_page
						) as b
						JOIN (SELECT @ns := NULL, @rn := 0) AS vars
						ORDER BY b.page_namespace ASC, b.count DESC
					) as c
					JOIN $curDB.page e on e.page_id = c.rev_page 
					WHERE c.row_number < 20
				"
			);
		
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					/*SLOW_OK USER_QUERY*/
					SELECT MIN(rev_timestamp) as min_timestamp, MAX(rev_timestamp) as max_timestamp
					FROM `$this->tmpDB`.`$this->tmpTable`
				"
			);
		
		foreach( self::getAEBTypes() as $toolname => $check ) {
				$cond_tool = " rev_comment ".$check['type']." '".$check['query']. "' ";
				$queryAEBs[] = "
						SELECT '$toolname' as toolname, count(*) as count
						FROM `$this->tmpDB`.`$this->tmpTable`
						WHERE  $cond_tool
					";
			}
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => implode(" UNION ", $queryAEBs )
			);
		
		$nameSpecial = str_replace(' ', '_', $this->mNameDbEnc);
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"timeout" => 0,
				"query" => "
					/*SLOW_OK USER_QUERY*/
					SELECT log_timestamp, cast(log_params as char) as log_params 
					FROM logging_logindex
					WHERE log_namespace = '2' and log_title in ('$nameSpecial','$this->mNameDbEnc') and log_type= 'block' and log_action in ('block','reblock')
				"
			);
				
		$res = $dbr->multiquery( $query );
		
		
		foreach ( $res[0] as $i => $row ){
			$this->mNamespaceTotals[ $row["page_namespace"] ] = $row["count"];
			$this->mUnique += $row["countdistinct"];
			$this->mCreated += $row["countcreated"];
			$this->mEditsSummary += $row["countsummary"];
			$this->mMinorEdits += $row["countminormarked"];
			$this->mMinorEditsByte += $row["countminorbyte"];
			$this->mMajorEditsByte += $row["countmajorbyte"];
			$this->mSumLen += $row["sumdiff"];
			$this->mLastDay += $row["countlastday"];
			$this->mLastWeek += $row["countlastweek"];
			$this->mLastMonth += $row["countlastmonth"];
			$this->mLastYear += $row["countlastyear"];
		}
		if (!$this->extended ){
			$this->mMinorEditsByte = 'extended';
			$this->mMajorEditsByte = 'extended';
			$this->mSumLen = 'extended';
		}
		
		foreach ( $res[1] as $i => $row ){
			$this->mMonthTotals[ $row["month"] ][ $row["page_namespace"] ] = $row["count"];
		}
		
		foreach ( $res[2] as $i => $row ){
			$this->timeCard["matrix"][ $row["day"]-1 ][ $row["hour"] ] = $row["count"];
		}
		
		foreach ( $res[3] as $i => $row ){
			$this->mUniqueArticles['namespace_specific'][ $row["page_namespace"] ][ (string)$row["page_title"] ] = $row["count"];
		}
		
		$this->mFirstEdit = $res[4][0]["min_timestamp"];
		$this->mLatestEdit = $res[4][0]["max_timestamp"];
		$this->mEditDays = (int)(( strtotime($this->mLatestEdit) - strtotime($this->mFirstEdit) ) /3600/24);
		
		foreach ( $res[5] as $i => $row ){
			$this->mAutoEditTools[ $row["toolname"] ] = $row["count"];
			$this->mAutoEdits += $row["count"];
		}

		foreach ( $res[6] as $i => $row ){
			$this->mBlockedNum = $i+1;
			preg_match('/[0-9]*[ \s]*(?:hour[s]*|day[s]*|week[s]*|month[s]*|years[s]*)/', $row["log_params"], $matchVerb);
			preg_match('/[0-9\-]+T[0-9:]+Z/', $row["log_params"], $matchTZ);
			preg_match('/(indefinite|infinite)/', $row["log_params"], $matchIndef ); 
			if ( $matchVerb[0] ){
				$lg1 = preg_replace(
						array('/hour(?!s)/','/day(?!s)/','/week(?!s)/','/month(?!s)/','/year(?!s)/'),
						array('hours','days','weeks','months','years'),
						$matchVerb[0]
					);
				$lg2 = str_replace(
						array('hours','days','weeks','months','years'), 
						array(1,24,(24*7),(24*30),(24*365)), $lg1). " ".$lg1;
			}
			elseif( $matchTZ[0] ){
				$ts = strtotime($row[log_timestamp]);
				$bl = strtotime($matchTZ[0]);
				#$this->perflog[] = array($row[log_timestamp], $matchTZ[0], $ts, $bl);
				$diff = $diff2 = number_format( ($bl - $ts)/3600, 1 );
				$diffunit = "hours";
				if ($diff > 24 ){
					$diff2 = intval($diff/24);
					$diffunit = "days";
				}
				$lg2 = "$diff 1 $diff2 $diffunit";
			}
			elseif ( $matchIndef ){
				$lg2 = 'infinite';
			}
			$longest[] = $lg2;
			$vals = explode(" ", $lg2);
			$bres[] = array($vals[0] * $vals[1], @$vals[2] . " ". @$vals[3]);
		}
		$max = 0;
		foreach ($bres as $i => $bitem){
			//do we have a wretched code-editor here?
			if ( $longest[$i] == 'infinite' ){
				$this->mBlockedLongest = 'infinite';
				break;
			}
			if( $bitem[0] > $max ){
				$max = $bitem[0];
				$this->mBlockedLongest = str_replace(
						array('hours','days','weeks','months','years'),
						array('{#hours##}','{#days##}','{#weeks##}','{#months##}','{#years##}'),
						$bitem[1]
					);
			}
		}
		$this->perflog[] = $res[6];
		$this->perflog[] = $longest; 
		$this->perflog[] = $bres;
		$this->perflog[] = $this->mBlockedLongest;
		
		$this->mLive = array_sum( $this->mNamespaceTotals );
		$this->mTotal = $this->mLive + $this->mDeleted;
		$this->mAveragePageEdits = ( $this->mTotal ) ? $this->mLive / $this->mUnique  : 0 ;
		$this->mAverageLen = ($this->extended) ? $this->mSumLen / $this->mLive : 'extended';
		
		
		unset($res);
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}

	
	function parseRevisions(){
		$pstart = microtime(true);
		
		$this->mFirstEdit  = '20991231000000';
		$this->mLatestEdit = '00000000000000';
		foreach ( $this->revs as $u => $row ) {
			
			if ( !isset($this->mNamespaceTotals[ $row['page_namespace'] ]) ){
				$this->mNamespaceTotals[ $row['page_namespace'] ] = 0;
			}
			$this->mNamespaceTotals[ $row['page_namespace'] ]++;
			
			$timestamp = substr( $row['rev_timestamp'], 0, 4 ) . '/' . substr( $row['rev_timestamp'], 4, 2 );
			
			if( !isset( $this->mMonthTotals[$timestamp] ) ) {
				$this->mMonthTotals[$timestamp] = $base_ns;
			}
			
			if( !isset( $this->mMonthTotals[$timestamp][ $row['page_namespace'] ] ) ){
				$this->mMonthTotals[$timestamp][ $row['page_namespace'] ] = 0;
			}
			$this->mMonthTotals[$timestamp][ $row['page_namespace'] ]++;
			
			
			if( $row["rev_timestamp"] < $this->mFirstEdit ) {
				$this->mFirstEdit = $row["rev_timestamp"]; 
			}
			if( $row["rev_timestamp"] > $this->mLatestEdit ) {
				$this->mLatestEdit = $row["rev_timestamp"];
			}
				
			
			if( !isset( $this->mUniqueArticles['namespace_specific'][$row['page_namespace']] ) ) {
				$this->mUniqueArticles['namespace_specific'][$row['page_namespace']] = array();
			}
			if( !isset( $this->mUniqueArticles['namespace_specific'][$row['page_namespace']][$row['page_title']] ) ) {
				$this->mUniqueArticles['namespace_specific'][$row['page_namespace']][$row['page_title']] = 0;
			}
			if( !isset( $this->mUniqueArticles['total'][$row['page_title']] ) ) {
				$this->mUniqueArticles['total'][$row['page_title']] = 0;
			}
			$this->mUniqueArticles['namespace_specific'][$row['page_namespace']][$row['page_title']]++;
			$this->mUniqueArticles['total'][$row['page_title']]++;
			
			if ( $row["rev_parent_id"] == 0 ) { $this->mCreated++ ; }
			
			if ( isset($row["parent_len"]) ){
				$diff = $row["rev_len"] - $row["parent_len"] ;
				if ( abs($diff) < 10 ){
					$this->mMinorEdits++;
				}
			}
			
			$revtime = strtotime( $row["rev_timestamp"] );
			$weekday = date('w', $revtime );
			$hour = intval( intval(date('H', $revtime )) / 4 );
			
			if ( !isset($this->timeCard["matrix"][$weekday][$hour]) ){
				$this->timeCard["matrix"][$weekday][$hour] = 0;
			}
			$this->timeCard["matrix"][$weekday][$hour]++;
			
			if ( !isset($this->timeCard["weekdays"][$weekday]) ){
				$this->timeCard["weekdays"][$weekday] = 0;
			}
			$this->timeCard["weekdays"][$weekday]++;
				
			if ( !isset($this->timeCard["hours"][$hour]) ){
				$this->timeCard["hours"][$hour] = 0;
			}
			$this->timeCard["hours"][$hour]++;

			
			$commentCleared = trim( preg_replace('/^\/\*.*\*\//', '', $row['rev_comment']) );
			
			if ( $commentCleared != "" ){ $this->mEditsSummary++ ; }			
			
			if ( strlen($commentCleared) > 4 ) {
				foreach ( self::getAEBTypes() as $tool => $signature ){
					if ( preg_match( $signature["regex"], $row['rev_comment']) ){
						$this->mAutoEdits++;
						$this->mAutoEditTools[$tool]++;
						break;
					}
				}
			}
			
			$this->mLive++;
		}
		
#print_r($this->timeCard);		
		//check unique articles in deleted
		if ( !isset( $this->mUniqueArticlesDeleted ) ){ $this->mUniqueArticlesDeleted = array(); }
		$this->mUniqueDeleted = count( $this->mUniqueArticlesDeleted );
#print_r($this->mUniqueDeleted);
		foreach ( $this->mUniqueArticlesDeleted as $i => $row ){
			if ( array_key_exists( $row["ar_title"], $this->mUniqueArticles['total'] ) ){
				$this->mUniqueDeleted -= 1;
			}
		}
#print_r($this->mUniqueDeleted);
		
		$this->mFirstEdit = date('Y-m-d H:i:s', strtotime( $this->mFirstEdit ) );
		$this->mUnique = count( $this->mUniqueArticles['total'] );
		$this->mTotal = $this->mLive + $this->mDeleted;
		
		
		$this->mAveragePageEdits = ( $this->mTotal ) ? $this->mTotal / $this->mUnique  : 0 ;
		
		//Well that sucked. This just fills the mMonthTotals array with all the months that have passed since the users last edit, 
		//if they haven't edited in over a month. Instead of appearing as though the user edited this month, it now is obvious they haven't edited in months
		if( !isset( $this->mMonthTotals[date('Y/m')] ) ) {

			$month_totals = $this->mMonthTotals;
			
			$last_month = strtotime(array_pop(array_keys($month_totals)).'/01');
			$now_month = strtotime(date('Y/m') . '/01');
			
			for( $i = $last_month;  $i <= $now_month; $i = strtotime( date( 'Y-m-d', $i ) . ' +1 month' ) ) {
				if( !isset( $this->mMonthTotals[date('Y/m', $i )] ) ) {
					$this->mMonthTotals[date('Y/m', $i )] = array();
				}
			}
		}
		
		ksort( $this->mNamespaceTotals);
		
		
		unset($res);
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	
	
	/**
	 * add missing months (0 edits) to the month list
	 */
	function fillMonthList() {
		$new_monthlist = array();
		$last_monthkey = null;
	
		foreach( $this->mMonthTotals as $month => $null ) {
			$str = explode( '/', $month );
			$str = strtotime( $str[0] . "-" . $str[1] . "-01" );
			if( !isset( $first_month ) ) $first_month = $str;
			$last_month = $str;
		}
	
		for( $date = $first_month; $date <= $last_month; $date += 10*24*60*60 ) {
			$monthkey = date( 'Y/m', $date );
	
			if( $monthkey != $last_monthkey ) {
				$new_monthlist[] = $monthkey;
				$last_monthkey = $monthkey;
			}
		}
	
		$monthkey = date( 'Y/m', str_replace( '/', '', $last_month ) );
	
		if( $monthkey != $last_monthkey ) {
			$new_monthlist[] = $monthkey;
			$last_monthkey = $monthkey;
		}
	
		foreach( $new_monthlist as $month ) {
			if( !isset( $this->mMonthTotals[$month] ) ) {
				$this->mMonthTotals[$month] = array();
			}
		}
	
		ksort( $this->mMonthTotals );
	}

	
// #*************************************************** stand alone modules ****************************************************#	
	
	/**
	 * Modul for standalone call from /autoedits
	 * this one performs a separate query, while in Counter main, it is part of parseRevs
	 * expects input date to be in format YYYY-mm-dd
	 */
	public function calcAutoEditsDB( &$dbr, $begin, $end ) {
		global $perflog; $start = microtime(true);
		
		$AEBTypes = self::getAEBTypes();

		$user = $dbr->strencode( $this->mName );
		$begindb = $dbr->strencode( str_replace("-", "", $begin ) );
		$enddb   = $dbr->strencode( str_replace("-", "", $end ) );
	
		$cond_begin = ( $begin ) ? " AND rev_timestamp > '$begindb' " : null;
		$cond_end 	= ( $end ) ? " AND rev_timestamp < '$enddb' ": null;
	
		$query = null;
		foreach( $AEBTypes as $toolname => $check ) {
				
			$cond_tool = " AND rev_comment ".$check['type']." '".$check['query']. "' ";
				
			$query[] .= "
					SELECT '$toolname' as toolname, count(*) as count
					FROM revision_userindex
					WHERE rev_user_text = '$user' $cond_begin $cond_end $cond_tool
				";
		}
		$query[] = " SELECT 'live' as toolname ,count(*) as count from revision_userindex WHERE rev_user_text = '$user' ";
		$query[] = " SELECT 'deleted' as toolname, count(*) as count from archive_userindex WHERE ar_user_text = '$user' ";

		
		$res = $dbr->query( implode(" UNION ", $query ) );
		
		foreach ( $res as $i => $item ){
			$contribs["tools"][ $item['toolname'] ] = $item['count'];
		}

		$contribs["editcount"] = $contribs["tools"]["live"] + $contribs["tools"]["deleted"];
		unset( $contribs["tools"]["live"], $contribs["tools"]["deleted"] );
		
		$contribs["start"] = $begin;
		$contribs["end"] = $end;
		$contribs["total"] = array_sum( $contribs["tools"] );
		$contribs["pct"] = ( $contribs["total"] / $contribs["editcount"] ) *100 ;
		
		
		$perflog->add(__FUNCTION__, microtime(true)-$start );
		
		return $contribs;
	}
	
	
	/**
	 * Modul for stand alone calls from /pages created
	 */	
	function getCreatedPages( &$dbr, $ui, $domain, $namespace, $redirects, $inclMovedelete=false ){
		global $perflog;
	
		$namespaceConditionRev = "";
		$namespaceConditionArc = "";
		
		if ($namespace != "all") {
			$namespaceConditionRev = " and page_namespace = '".intval($namespace)."' ";
			$namespaceConditionArc = " and ar_namespace = '".intval($namespace)."' ";
			$namespaceConditionArc2 = " and b.ar_namespace = '".intval($namespace)."' ";
		}
	
		$redirectCondition = "";
		if ( $redirects == "onlyredirects" ){ $redirectCondition = " and page_is_redirect = '1' "; }
		if ( $redirects == "noredirects" ){ $redirectCondition = " and page_is_redirect = '0' "; }
	
		$username = $ui->userDb;
		$userid = $ui->userid;
	
		if ( $ui->isIP ){
			$whereRev = " rev_user_text = '$username' AND rev_user = '0' ";
			$whereArc = " ar_user_text = '$username' AND ar_user = '0' ";
			$whereArc2 = " b.ar_user_text = '$username' AND b.ar_user = '0' ";
			$having = " rev_user_text = '$username' ";
		}
		else {
			$whereRev = " rev_user = '$userid' AND rev_timestamp > 1 ";
			$whereArc = " ar_user = '$userid' AND ar_timestamp > 1 ";
			$whereArc2 = " b.ar_user = '$userid' AND b.ar_timestamp > 1 ";
			$having = " rev_user = '$userid' ";
		}
		
		$moveCond = ( $inclMovedelete ) ?  "" : " and log_action is null " ;
		
// 		$query = "
// 			(SELECT DISTINCT page_namespace as namespace, 'rev' as type, page_title as page_title, page_is_redirect as page_is_redirect, rev_timestamp as timestamp
// 			FROM page
// 			JOIN revision_userindex on page_id = rev_page
// 			WHERE  $whereRev  AND rev_parent_id = '0'  $namespaceConditionRev  $redirectCondition  
// 			)
				
// 			UNION
				
// 			(SELECT DISTINCT ar_namespace as namespace, 'arc' as type, ar_title as page_title, '0' as page_is_redirect, ar_timestamp as timestamp
// 			FROM archive_userindex
// 			WHERE  $whereArc  AND ar_parent_id = '0' $namespaceConditionArc  
// 			)
				
// 			ORDER BY namespace ASC, timestamp DESC;
// 		";
		
		$query2 = "
			(SELECT DISTINCT page_namespace as namespace, 'rev' as type, page_title as page_title, page_is_redirect as page_is_redirect, rev_timestamp as timestamp, rev_user, rev_user_text
			FROM page
			JOIN revision_userindex on page_id = rev_page
			WHERE  $whereRev  AND rev_parent_id = '0'  $namespaceConditionRev  $redirectCondition
			)
			
			UNION
			
			(SELECT  a.ar_namespace as namespace, 'arc' as type, a.ar_title as page_title, '0' as page_is_redirect, min(a.ar_timestamp) as timestamp , a.ar_user as rev_user, a.ar_user_text as rev_user_text
			FROM archive_userindex a
			JOIN 
			 (
			  Select b.ar_namespace, b.ar_title
			  FROM archive_userindex as b
			  LEFT JOIN logging_logindex on log_namespace = b.ar_namespace and log_title = b.ar_title  and log_user = b.ar_user and (log_action = 'move' or log_action = 'move_redir')
			  WHERE  $whereArc AND b.ar_parent_id = '0' $namespaceConditionArc  $moveCond
			 ) AS c on c.ar_namespace= a.ar_namespace and c.ar_title = a.ar_title 
			GROUP BY a.ar_namespace, a.ar_title
			HAVING  $having
			)
			
			ORDER BY namespace ASC, timestamp DESC;
		";
		
		#$perflog->add('query', 0, $query2 );
		
		return $dbr->query( $query2 );
	}
	
	/**
	 *Modul for stand alone calls from /topedits 
	 * @param unknown $dbr
	 */

		
	function getOptinLinkLocal(){
		return  "<a href=\"//$this->mDomain/wiki/User:$this->mName/EditCounterOptIn.js\" >$this->mName/EditCounterOptIn.js</a>";
	}
	function getOptinLinkGlobal(){
		return "<a href=\"//meta.wikimedia.org/wiki/User:$this->mName/EditCounterGlobalOptIn.js\" >$this->mName/EditCounterGlobalOptIn.js (metawiki)</a>";
	}
	
	function getNamespaces(){
		return $this->mNamespaces;
	}
		
	function getMonthTotals() {
		return $this->mMonthTotals;
	}
	
	function getNamespaceTotals() {
		return $this->mNamespaceTotals;
	}
	
	function getName() {
		return $this->mName;
	}
	
	function getIP() {
		return $this->mIP;
	}
	
	function getExists() {
		return $this->mExists;
	}
	
	function getUID() {
		return $this->mUID;
	}
	
	function getCreated() {
		return $this->mCreated;
	}
	
	function getUnique() {
		return $this->mUnique;
	}
	
	function getDeleted() {
		return $this->mDeleted;
	}
	
	function getReverted() {
		return $this->mReverted;
	}
	
	function getMoved() {
		return $this->mMoved;
	}
	
	function getApproved() {
		return $this->mApproved;
	}
	
	function getThanked() {
		return $this->mThanked;
	}
	
	
	function getLive() {
		return $this->mLive;
	}
	
	function getTotal() {
		return $this->mTotal;
	}
	
	function getGroupList() {
		return $this->mGroups;
	}
	
	function getUniqueArticles() {
		return $this->mUniqueArticles;
	}
	
	function getFirstEdit() {
		return $this->mFirstEdit;
	}
	
	function getAveragePageEdits() {
		return $this->mAveragePageEdits;
	}
	function getAutoEdits(){
		return $this->mAutoEdits;
	}
	
	static function getAEBTypes(){
		return self::$AEBTypes;
	}
			
	public static $AEBTypes = array(
				'Huggle' => array(
						'url' => '//en.wikipedia.org/wiki/WP',
						'type' => 'RLIKE',
						'query' => '(WP:HG|WP:Huggle|\(HG\))',
						'regex' => '/.*(?=WP:HG\|HG\|WP:Huggle).*/',
						'shortcut' => 'WP:HG'
					),
				
				'Twinkle' => array(
						'url' => '',
						'type' => 'LIKE',
						'query' => '%WP:TW%',
						'regex' => '/.*WP:TW.*/',
						'shortcut' => 'WP:TW'
					),

				'STiki' => array(
						'url' => '',
						'type' => 'LIKE',
						'query' => '%WP:STiki%',
						'regex' => '/.*WP:STiki.*/',
						'shortcut' => 'WP:STiki'
				),

				'Popups' => array(
						'url' => '',
						'type' => 'LIKE',
						'query' => '%|popups]]%',
						'regex' => '/.*Wikipedia:Tools\/Navigation_popups\|popups.*/',
						'shortcut' => 'WP:POP'
					),
				
				'Articles For Creation tool' => array(
						'url' => '',
						'type' => 'LIKE',
						'query' => '%WP:AFCH%',
						'regex' => '/.*\(\[\[WP:AFCH\|AFCH\]\]\).*/',
						'shortcut' => 'WP:AFCH'
					),
				
				'AutoWikiBrowser' => array(
						'url' => '',
						'type' => 'LIKE',
						'query' => '%|AWB]]%',
						'regex' => '/.*(?=Project:AWB\|AWB|WP:AWB).*/',
						'shortcut' => 'WP:AWB' 
					),

				'WPCleaner' => array(
						'url' => '',
						'type' => 'LIKE',
						'query' => '%WP:CLEANER%',
						'regex' => '/.*\[\[(?:\:en\:)*WP:CLEANER\|WPCleaner\]\].*/',
						'shortcut' => 'WP:CLEANER'
					),
				
				'FurMe' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => '%|FurMe]]%',
						'regex' => '/.*(?=Wikipedia:FurMe\|FurMe|WP:FURME).*/',
						'shortcut' => 'WP:FURME' 
					),
				
				'NPWatcher' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => '%WP:NPW%',
						'regex' => '/.*WP:NPW.*/',
						'shortcut' => 'WP:NPW' 
					),
				
				'Igloo' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => '%|GLOO]]%',
						'regex' => '/.*\|GLOO\]\].*/',
						'shortcut' => 'WP:IGL' 
					),
				
				'HotCat' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => '%WP:HOTCAT%',
						'regex' => '/.*\(using \[\[WP:HOTCAT\|HotCat\]\]\).*/',
						'shortcut' => 'WP:HOTCAT' 
					),
				

// 				'Friendly' => array(
// 						'url' => '',
// 						'type' => 'LIKE',
// 						'query' => '%WP:FRIENDLY%',
// 						'regex' => '/.*WP:FRIENDLY.*/',
// 						'shortcut' => 'WP:FRIENDLY'
// 					),

// 				'Dazzle!' => array(
// 						'url' => '',
// 						'type' => 'LIKE',
// 						'query' => '%Dazzle!%',
// 						'regex' => '/.*Dazzle\!.*/',
// 						'shortcut' => 'WP:Dazzle!'
// 				),
				
// 				'MWT' => array(
// 						'url' => '',
// 						'type' => 'LIKE',
// 						'query' => '%User:MichaelBillington/MWT%',
// 						'regex' => '/.*User:MichaelBillington\/MWT.*/',
// 						'shortcut' => 'User:MichaelBillington/MWT'
// 					),
					
// 				'Amelvand' => array(
// 						'url' => '',
// 						'type' => 'LIKE',
// 						'query' => 'Reverted % edit% by % (%) to last revision by %',
// 						'regex' => '/^Reverted.*edit.*by .* \(.*\) to last revision by .*/',
// 						'shortcut' => 'User:Gracenotes/amelvand.js'
// 					),
				
		);
		

}
