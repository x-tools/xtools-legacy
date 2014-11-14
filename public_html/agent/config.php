<?php 

require_once( '/data/project/xtools/modules/WebTool.php' );
require_once( 'Agent.php' );

$wt = new WebTool( 'config' );

$action = $wgRequest->getVal('action', null);
$method = $_SERVER["REQUEST_METHOD"];


if ( $method == "POST" ){

	if ($action == "clearconfig") {
		$redis->del('xagconfig'.session_id());
		exit();
	}
	
	if ($action == "loadconfig") {
		if ($result = $redis->get('xagconfig'.session_id() ) ){
			header('Content-Type: application/json; charset=utf-8');
			echo $result;
		}
		else{
			header("HTTP/1.0 404 Not found");
		}
		exit();
	}
	
	if ($action == "saveconfig") { 
		$conf = $wgRequest->getText('config', null);
		
		if( $check = json_decode($conf) ){
			$redis->setex( 'xagconfig'.session_id(), 86400, $conf );
			print_r( $check);
		}
		else{
			header("HTTP/1.0 500 Internal Server Error");
		}
		exit(0);
	}
	
	if ($action == "check"){
		
		//example  http://tools.wmflabs.org/xtools/templates/xgconfig.php?action=check&pages=[%22enwiki|Enterprise%20social%20graph%22,%22Eva%20Green%22,%22dewiki|Babylon%205%22,%22%22]
		//get page array, array values shoud be trimmed 
		$pages = $wgRequest->getText('pages', null);
		$pages2 = json_decode($pages);
		
		//slpit, trim
		foreach ($pages2 as $i => $page){
			#if( ! trim($page) )continue;
			
			$parts = explode("|", $page);
			if ( !@$parts[1] ){
				$parts[1] = trim($parts[0]);
				$parts[0] = "enwiki";
			}
			else {
				$parts[0] = strtolower( trim( $parts[0] ) );
				$parts[1] = trim( $parts[1] );
			}
			$hash = crc32( $parts[0].$parts[1] );
			
			$pageList[$i] = array('src'=> $page, 'hash' => $hash, 'wiki' => $parts[0], 'page' => $parts[1] );
			
			$wikiList[ $parts[0] ][] = $parts[1];
		}
		
		//api
		foreach ( array_keys( $wikiList) as $wiki ){
			$domain = $wt->metap[ $wiki ]["domain"];
			$apibase = "http://$domain/w/api.php?";
			$pages = array_values( $wikiList[ $wiki ] );
			$data = array(
					'action' => 'query',
					'format' => 'json',
					'prop' => 'info|revisions',
					'inprop' => 'watched|watchers|notificationtimestamp|url',
					'indexpageids' => '',
					'converttitles' => '',
					'titles' => implode('|', $pages)
			);
			$query = $apibase . http_build_query($data);
			#print_r($apibase);
			#print_r($data);
			
			$res = json_decode( $wt->gethttp( $query ) )->query->pages;
			foreach ( $res as $pageid => $row ){
				
				$hash = crc32( $wiki.$row->title );
				foreach ( $pageList as $i => $entry) {
					
					if ( $hash == $entry["hash"] ){
						try{
							$diff = $wt->datediff( new DateTime($row->revisions[0]->timestamp) );
							$lastrev = $wt->dateFmt($row->revisions[0]->timestamp);
							$err =false;
						}
						catch (Exception $e){
							$err = true;
						}
						if ($err){
							$diff = $wt->datediff( null );
							$lastrev = null;
						}
						$pageList[$i]["pageid"] = $pageid;
						$pageList[$i]["ns"] = $row->ns;
						$pageList[$i]["lastrev"] = $lastrev;
						$pageList[$i]["datediff"] = "<span style=\"color:$diff->diffcolor \" >$diff->difftxt</span>";
						$pageList[$i]["lastrevid"] = $row->lastrevid;
						$pageList[$i]["lastuser"] = $row->revisions[0]->user;
						$pageList[$i]["lastcomment"] = $row->revisions[0]->comment;
						$pageList[$i]["url"] = str_replace('http://', '', $row->fullurl);
					}
				}
			}
			#print_r($res);
		}
			
		
		$result = json_encode($pageList);
		header('Content-Type: application/json; charset=utf-8');
		echo $result;
		exit;
	}

}


//****************************************************++ non-api ************************************************************

#print_r(session_id());
$wt->content = getPageTemplate();

#print_r($_SESSION);
$wt->sitenotice = null;
$wt->moreheader = '
		<script type="text/javascript" src="//'.XTOOLS_BASE_WEB_DIR.'/static/js/jquery-1.11.1.min.js"></script>
		<script type="text/javascript" src="//'.XTOOLS_BASE_WEB_DIR.'/static/js/unserialize.jquery.1.0.2.js"></script>
		<script type="text/javascript" src="//'.XTOOLS_BASE_WEB_DIR.'/static/js/xagent.js?v10"></script>
	';




$langOptions = '<option value="default">default</option>';
foreach( $wt->i18Langs as $langCode => $langName ){
	$langOptions .= "<option value=\"$langCode\">$langName</option>";
}

$autowikilist = '<p style="text-align:center"><br/>&nbsp;You are not logged in. <a href="//tools.wmflabs.org/xtools/?login" >Log in</a> with secure Wikimedia OAuth.<br/>&nbsp;</p>';
if ($wt->loggedInUsername){
	
	$autowiki = getUserActivity( $wt, $wt->loggedInUsername);
	$autowikilist = '<table class="table-condensed table-striped xt-table">';
	
	foreach ( $autowiki["topwikis"] as $wiki => $row ){
		$diff = $wt->datediff( new DateTime( $row["latest"] ) );
		$diffsp = "<span style=\"color:$diff->diffcolor \" >$diff->difftxt</span>";
		$autowikilist .= '<tr><td>'.$wiki.'</td><td>'.$row["editcount"].'</td><td>'.$wt->dateFmt($row["latest"]).'</td><td>'.$diffsp.'</td></tr>';
	}
	$autowikilist .= '</table>';
}

// both configs are for JS-Hanlder in xagent.js
$defCfg = array(
	'showAuthor' => "1",
	'showCreatedby' => '1',
	'showPageview' => '1',
	'showRevision' => '1',
	'showWatcher' => '1',
	'showMainauthor' => 0,
	'trackpages' => array("enwiki|Enterprise social graph","Eva Green","dewiki|Babylon 5"),
	'trackwikis' => array("enwiki","dewiki","metawiki","wikidatawiki","commonswiki"),
	'defaultWiki' => 'enwiki',
	'status' => 'enabled',
	'mode' => 'verbose',
	'uselang' => 'default',
);

$clearCfg = new stdClass();
foreach ($defCfg as $prop => $val){
	$clearCfg->{"$prop"} = null;
}

$wt->moreScript = "clearConfig = ".json_encode($clearCfg)."\ndefaultConfig = ".json_encode($defCfg).";\nxsessionid='".session_id()."';\nsettingsLoad();";

$perflog->add('session_id', 0, session_id() );

$wt->assign( 'autowikilist', $autowikilist);
$wt->assign('langoptions', $langOptions );
$wt->assign( 'echolink', $wt->statusLink['echo'] );

$wt->showPage(true);

function getPageTemplate(){


$template = '
<div class="panel panel-success" style="text-align:center">
<div class="panel-heading">
	<p class="xt-heading-top" >
		
		<img height=28px src="//upload.wikimedia.org/wikipedia/commons/0/06/Green_Fedora_hat.png" />
		&nbsp; <span>XAgent &bull; Configuration</span> &nbsp;&nbsp;<i>Test mode</i>
	</p>
</div>
<div class="panel-body xt-panel-body-top" >
<p>

</p>

<form id="form_settings" >
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4  class="topcaption" >General <span class="showhide" onclick="javascript:switchShow( \'generalstats\', this )">[{#hide#}]</span>
			</h4>
		</div>
		
	<div class="panel-body" id="generalstats">
			<table class="table-condensed xt-table"
			<tr>
				<td style="padding-bottom:10px">Enable Agent:</td>
				<td> 
					<input type="radio" name="status" checked value="enabled">enable
					<input type="radio" name="status" value="disabled">disable
					&nbsp;<span ><a href="//meta.wikimedia.org/wiki/User:Hedonil/XTools" >How to enable the agent/gadget</a></span>
				</td>
			<tr>
			</tr>
				<td style="padding-bottom:10px">Agent mode:</td>
				<td> 
					<input type="radio" name="mode" checked value="verbose">verbose
					<input type="radio" name="mode"  value="silent">silent
				</td>			
			</tr>
			<tr>
				<td>Language:</td>
				<td>
					<select  name="uselang"  >{$langoptions}</select> default = language of the respective wiki
				</td>
			</tr>
			<tr>
			<td style="padding-top:10px">Display:</td>
				<td style="padding-top:10px">
					<input type="checkbox" name="showRevision" checked value="1">Revisions &nbsp;
					<input type="checkbox" name="showAuthor"   checked value="1">Authors &nbsp;
					<input type="checkbox" name="showWatcher"  checked value="1">Watchers &nbsp;
					<input type="checkbox" name="showPageview"  checked value="1">Page views &nbsp;
					<input type="checkbox" name="showCreatedby"  checked value="1">Created by &nbsp;
					<input type="checkbox" name="showMainauthor"  value="1">Main author (german wiki only) &nbsp;
				</td>	
			</tr>
			</table>
			<br />
			<input class="btn  btn-primary" type="button" value="Save configuration" onclick="settingsSave()" ></input>
			&nbsp;&nbsp;&nbsp;<label class="label" id="smessage"></label>
			<input class="btn  btn-small pull-right" type="button" value="Clear configuration" onclick="settingsClear()" ></input>
			
		</div>
		<p class="alert alert-info xt-alert"> 
				Note: If you can\' t configure XAgent, it\'s probably because it isn\' t allowed to set the necessary cookie. See <a href="//meta.wikimedia.org/wiki/User:Hedonil/XTools">Gadget\'s wikipage</a> for details. 
			</p>
	</div>

	<div class="panel panel-default">
		<div class="panel-heading">
			<h4  class="topcaption" >XAgent Echo <span class="showhide" onclick="javascript:switchShow( \'xcm\', this )">[{#hide#}]</span>
			&nbsp; <small style="color:green" >Cross-wiki notifier, if you have notifications in any of 800+ wikis. ON AIR - ready for testing!. Log in and try out.</small>
			</h4>
		</div>
		<div class="panel-body" id="xcm">
			<p>One entry per line. While running test mode, max: 20. <br />These wikis will be checked by XAgent for new, unread notifications (cache: 5 minutes).
			The right column shows the wikis autodetected by XAgent. These wikis will be included automatically. 
			It\'s an intersection of your 10 most edited and 10 latest edited wikis (30 days). This list is recomputed every 15 minutes.<br />
			If you just want to check your global notifications once in a while, goto &nbsp;{$echolink}.
			</p>
			<table class="table">
			<tr>
				<td style="width:40%">
				 	<textarea class="form-control" rows=5  style="width:100%"  id="trackwikis" name="trackwikis"></textarea>
				</td>
				<td style="width:60%">
				 	<div id="autowikilist" style="border:1px solid silver" >{$autowikilist}</div>
				</td>
			</tr>
			</table>
			
		</div>
	</div>
		
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4  class="topcaption" style="color:lightgray" >XTracker Pages <span class="showhide" onclick="javascript:switchShow( \'xtp\', this )">[{#hide#}]</span>
			&nbsp; <small style="color:lightgray" >Cross-wiki notifier, if these pages are edited</small>
			</h4>
		</div>
		<div class="panel-body" id="xtp">
			<p>One entry per line. Enter in format &lt;wiki&gt;|&lt;page name&gt; If no wiki is specified, the default wiki will be used.</p>
			Default wiki: <input class="form-inline" type="text" disabled name="xag-default-wiki" value="enwiki" />
			<table class="table">
			<tr>
				<td style="width:40%">
				 	<textarea class="form-control" rows=5  readonly style="width:100%" wrap="off" id="trackpages" name="trackpages"></textarea>
				</td>
				<td style="width:60%">
				 	<div id="checkresultsP" ></div>
				</td>
			</tr>
			</table>
			<input class="btn btn-xs btn-primary" type="button" value="Check" onclick="checkPages(\'trackpages\')" ></input>
			<br />
			... work in progress. Soon.			
		</div>
	</div>

	<div class="panel panel-default">
		<div class="panel-heading">
			<h4  class="topcaption" style="color:lightgray" >XTracker Users <span class="showhide" onclick="javascript:switchShow( \'xtu\', this )">[{#hide#}]</span>
			&nbsp; <small style="color:lightgray" >Cross-wiki notifier, if this user makes an edit</small>
			</h4>
		</div>
		<div class="panel-body" id="xtu">
		... work in progress. Soon.	
		</div>
	</div>
				
</form>
</div>
</div>
';
return $template;
}