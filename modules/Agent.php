<?php 
require_once( 'WebTool.php');
require_once( 'OAuth.php');



function getUserActivity( $wt ){
	global $redis, $perflog;
	
	$resArr = array();
	if ( !$wt->OAuthObject->isAuthorized() ){ return $resArr; }
	
	$username = $wt->OAuthObject->getUsername();
	
	$ttl = 1800;
	$hash = session_id().XTOOLS_REDIS_FLUSH_TOKEN."02".hash('crc32', "xtoolsMSGWikis".$username );
	$lc = $redis->get($hash);
	
	if ($lc === false ){
	
		$wikis = array();
		
		//1. Get wikis from global user info
		$data = array(
				'action' => 'query',
				'meta' => 'globaluserinfo',
				'format' => 'json',
				'guiprop' => 'merged|unattached|editcount',
				'guiuser' => $username,
			);
		
		$res = json_decode( $wt->gethttp("http://meta.wikimedia.org/w/api.php?".http_build_query( $data ) ) );
		$res = $res->query->globaluserinfo;
	
		$attached = 0;
		$unattached = 0;
		$totaleditcount = 0;
		
		if ( isset( $res->merged)  ) {
			foreach ( $res->merged as $wiki ){
				$wikis[ $wiki->wiki ] = array(
						'url' => $wiki->url,
						'editcount' => $wiki->editcount,
						'attached' => true,
						'latest' => null,
					);
				$attached += 1;
				$totaleditcount += $wiki->editcount;
			}
		}
	
		if (isset($res->unattached )){
			foreach ( $res->unattached as $wiki ){
				$wikis[ $wiki->wiki ] = array(
						'url' => 'http://'.$wt->metap[ $wiki->wiki ]["domain"],
						'editcount' => $wiki->editcount,
						'attached' => false,
						'latest' => null,
					);
				$unattached += 1;
				$totaleditcount += $wiki->editcount;
			}
		}
		
		
				
		$dbr = $wt->loadDatabase(null, null, 's1');
		$mNameDbEnc = $dbr->strencode( $username );
		
		//2. Get useractivity form db	
		$datenow = new DateTime();
		$datefrom = date_format($datenow->sub(new DateInterval("P30D")), "YmdHis");
		
		$knownwikis = array_keys( $wikis );
		
		foreach ( $wt->metap as $db => $row ){
		
			if ($db == "centralauth" || !in_array($db, $knownwikis) ) { continue; }
				
			$latestEdits[] =	"
					( SELECT '$db' as wiki, rev_timestamp
					FROM ${db}_p.revision_userindex
					where  rev_user_text = '$mNameDbEnc' AND rev_timestamp > '$datefrom'
					ORDER BY rev_timestamp desc
					Limit 1 )
				";
	
		}
		$query = implode(" UNION ", $latestEdits );
		
		$res = $dbr->query($query);
		
		foreach ( $res as $i => $row){
			$wikis[ $row["wiki"] ]["latest"] = $row["rev_timestamp"];
		}
		
		$dbr->close();
		
		
		//get top 10 most edited
		uasort( $wikis, function($al, $bl) {
			$a = $al["editcount"];
			$b = $bl["editcount"];
			if ($a == $b) { return 0; }
			return ($a < $b) ? 1 : -1;
		});
		
		$topmost = array_slice($wikis, 0, 10, true);
		
		#print_r( $topmost );
		
		//get top 10 latest edited
		uasort( $wikis, function($al, $bl) {
			$a = $al["latest"];
			$b = $bl["latest"];
			if ($a == $b) { return 0; }
			return ($a < $b) ? 1 : -1;
		});
		
		$toplatest = array_slice($wikis, 0, 10, true);
			
		#print_r($toplatest);
	
		$topges = array_merge($topmost, $toplatest);
		
		$resArr = array(
				"user" => $username,
				"attached" => $attached,
				"unattached" => $unattached,
				"totaleditcount" => $totaleditcount,
				"topwikis" => $topges,
				"allwikis" => $wikis,
			);
		
		if ( count($resArr["allwikis"]) > 0 ){
			$redis->setex( $hash, $ttl, serialize( $resArr ) );
		}
	}
	else {
		$resArr = unserialize( $lc );
		$perflog->add(__FUNCTION__, 0, 'from Redis');
	}	
	
	
	#print_r($resArr);
	return $resArr;
	
}

function mergeCustomWikis( $wt, $wikis ){
	global $redis;

	//Get wikis from XAgent config
	if ( $lc = $redis->get('xagconfig'.session_id() ) ){

		$confread = json_decode( $lc );
		foreach ( $confread-> trackwikis as $wiki){
			$domain = $wt->metap[$wiki]["domain"];
			
			if ( !isset($wikis[$wiki]) ){
				$wikis[ $wiki ] = array(
						'url' => "http://$domain",
						'editcount' => null,
						'attached' => null,
						'latest' => null,
				);
			}
		}

		#$perflog->stack[] = $wikis;
	}

	return $wikis;
}

function getCrossWikiMessage( $wt, $type='agent', $limit=5, $forceRefresh=false ){
	global $perflog, $redis, $wgRequest;
	
	if ( !$wt->OAuthObject->isAuthorized() ){ return null;}
	
	$wikiArr = getUserActivity( $wt );
	
	$msgwrap1 = '<li class="mw-echo-notification" ><div class="mw-echo-notification-wrapper" style="padding-bottom:7px;border-bottom: 1px solid rgb(221, 221, 221);" >';
	$msgwrap2 = '</div></li>';
	
	$ttl = 300;
	$hash = session_id().XTOOLS_REDIS_FLUSH_TOKEN."03".hash('crc32', "xtoolsMSG1".$wt->OAuthObject->getUsername() );
	$lc = $redis->get($hash);
	
	if ( $lc === false || $forceRefresh ){
		
		if ( $type == 'agent' ){
			
			$wikis = $wikiArr["topwikis"];
			$wikis = mergeCustomWikis( $wt, $wikis );
			$limit = 5;
		}
		elseif ( $type == "all" ){
			
			$wikis = $wikiArr["allwikis"];
		}
		
		$data = array(
		'action' => 'query',
					'meta' => 'notifications',
					'format' => 'json',
					'notprop' => 'count|list|index',
					'notformat' => 'flyout',
					'notlimit' => $limit,
					'uselang' => $wt->uselang
		);
	
		foreach ($wikis as $wiki => $row ){
			$apiRequestArr[] = array(
					"wiki" => $wiki,
					"apiUrl" => str_replace("http", "https", $row['url'])."/w/api.php",
					"data" => $data
			);
		}
		#$perflog->stack[] = $wikis;
	
		$res = $wt->OAuthObject->doApiMultiQuery($apiRequestArr);
		
		#$perflog->stack[] = $res;
	
		$msgs = '';
		$countnew = 0;
		foreach ($res as $wiki => $rawrow ){
			
			if( isset($rawrow->error) ){

				$row = new stdClass();
					$row->index = array(0);
					$row->list->{'0'}->{'*'} = '<div class="mw-echo-state"><img class="mw-echo-icon" src="//bits.wikimedia.org/static-1.24wmf15/extensions/Echo/modules/icons/Generic.png"></img><div class="mw-echo-content">' . "Error: $wiki " . $rawrow->error->info .'</div></div>';
					$row->list->{'0'}->timestamp->mw = null;
				$error = true;
			}
			else{
				$row = $rawrow->query->notifications;
				$countnew += $row->count;
				$error = false;
			}
			
			
			foreach ($row->index as $idx ){
				
				$domain = $wt->metap[ $wiki ]["domain"];
				$msg = str_replace( array('/wiki/','/w/') , array("//$domain/wiki/","//$domain/w/"), $row->list->{$idx}->{"*"} );
				
				$wi = $wt->getWikiInfo(null, null, $domain, false);
				$imglang = '<img height=12px alt="'.$wi->lang.'" src="'.$wi->imglang.'" />';
				$imgfam = '<img height=16px src="'.$wi->imgwiki.'" />';
				
				$msgArr[ $wiki.$idx ] = array(
						"error"=> $error,
						"wiki" => $wiki,
						"lang" => $wt->metap[ $wiki ]["lang"],
						"timestamp" => $row->list->{$idx}->timestamp->mw,
						"read" => @$row->list->{$idx}->read,
						"text" => $msgwrap1 .$msg. $imglang.'&nbsp;' . $imgfam. $msgwrap2
					);
			}
		}
		
		krsort($msgArr);
		$resArr = array(
				'user' => $wikiArr["username"],
				'attached' => $wikiArr["attached"],
				'unattached' => $wikiArr["unattached"],
				'countnew'=> $countnew, 
				'msgArr'=>$msgArr
			);
		
		
		$redis->setex($hash, $ttl, serialize($resArr) );
		
		$perflog->add(__FUNCTION__, 0, 'numArr: '.count($msgArr) );
	}
	else{

		$resArr = unserialize( $lc );
		$perflog->add(__FUNCTION__, 0, 'from Redis');
		#$perflog->add(__FUNCTION__, 0, $lc);
	}
	
	
	

	$countnew = $resArr["countnew"];
	$bgclass = ($countnew) ? " xt-badge-unread" : "";
	$list = '
			<div class="xt-badge'.$bgclass.'" >'.$countnew.'
			<div class="mw-echo-overlay" >
				<ul class="mw-echo-notifications" style="max-height: 570px;">
		';

	$configlink = $msgwrap1 . '<span style="display:block; text-align:center;" >'. str_replace('{$linktext}', ' Configure XAgent', $wt->statusLink['agentconfig']) . '</span>' . $msgwrap2;
	$showall = $msgwrap1 . '<span style="display:block; text-align:center;" >'. str_replace('XEcho', 'XEcho - Show all available notifications.', $wt->statusLink['echo']) . '</span>' . $msgwrap2;
	$listerr = '';
	
	if ($type == 'agent' ){
	
		foreach ( $resArr["msgArr"] as $time => $row ){
			
			if ( $row["read"]) { continue; }
			
			if ($row["error"]){
				$listerr .= $row["text"];
			}
			else{
				$list .= $row["text"];
			}
		}
		$list .= $listerr. $configlink. $showall. '</ul></div></div>';
		
		return $list;
	}
	else{
		$resArr["list"] = $list. '</ul></div></div>';
		 
		return $resArr;
	}
	
}

