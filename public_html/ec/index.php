<?php
if( $_SERVER['SCRIPT_NAME'] == "/xtools/ec/index.php" ) {
header('HTTP/1.1 301 Moved Permanently');
?><html>
<head>
<script type="text/javascript">
<!--
function delayer(){
    window.location = "//tools.wmflabs.org/xtools-ec/index.php?<?php echo $_SERVER['QUERY_STRING']; ?>"
}
//-->
</script>
</head>
<body onLoad="setTimeout('delayer()', 5000)">
<h1>301 Moved Permanently</h1>
<p>This tool has moved to a new location.  You will be redirected to tools.wmflabs.org/xtools-ec/index.php?<?php echo $_SERVER['QUERY_STRING']; ?> shortly.</p>

</body>
</html>
<?php die();
}

//Requires
    set_include_path( get_include_path() . PATH_SEPARATOR . '/data/project/xtools/public_html/ec');
    require_once( '/data/project/xtools/modules/WebTool.php' );
    require_once( '/data/project/xtools/modules/Counter.php' );

    
//Load WebTool class
    $wt = new WebTool( 'ec' );
    $wt->setLimits( 650, 120 );
    $wt->getPageTemplate( "form" );

    $purge = $wgRequest->getBool('purge');
    if ( $wgRequest->getSessionData('ec_purge') ){
        unset( $_SESSION["ec_purge"] );
        $purge = true;
    }
    
    $extended = $wgRequest->getBool('extended', false);
    
    $wi = $wt->wikiInfo;
        $lang = $wi->lang;
        $wiki = $wi->wiki;
        $domain = $wi->domain;
    
    $ui = $wt->getUserInfo();
        $user = $ui->user;
    
    
    
//Show form if user is not set (or empty)
    if( !$user || !$lang || !$wiki ) {
        $wt->showPage();
    }

        
//Create new Counter object

    $ttl = 120;
    $hash = "xtoolsCNT".XTOOLS_REDIS_FLUSH_TOKEN.hash('crc32', $lang.$wiki.$user.$extended);
    $lc = $redis->get($hash);
    
    if ($lc === false || $purge ){
        $dbr = $wt->loadDatabase( $lang, $wiki);
        $cnt = new Counter( $dbr, $user, $domain, false, $ui->editcount, $extended);
        if( $ui->editcount > 100000 ) { $ttl = 1800; }
        if( $ui->editcount > 300000 ) { $ttl = 86400; }
        if( !$cnt->error ) { $redis->setex( $hash, $ttl, serialize( $cnt ) ); }
    
    }
    else{
        $cnt = unserialize( $lc );
        unset( $lc );
        $perflog->add('CNT', 0, 'from Redis');
    }
    
    if ($cnt->error ){
        $perflog->stack[] = $cnt->error;
        $wt->toDie('error. ec::index::cnt -> '.$cnt->error );
    }

    require_once( 'Graph.php' );
    
//Output stuff
    $wt->content = getPageTemplate( "result" );    
    
//Make Graphs
    $graphNS = xGraph::makePieGoogle( $cnt->getNamespaceTotals() );
    $legendNS = xGraph::makeLegendTable(  $cnt->getNamespaceTotals(), $cnt->getNamespaces() );
    
    $graphMonths = xGraph::makeHorizontalBar( "month", $cnt->getMonthTotals(), 800, $cnt->getNamespaces());
    $graphYears = xGraph::makeHorizontalBar( "year", $cnt->getMonthTotals(), 800, $cnt->getNamespaces());
    
    $gcolor1 = '99CCFF';
    $gcolor2 = '99CC00';
    $msgBytes = $I18N->msg('bytes');
    
    $gminorpct = ($cnt->mMinorEditsByte / $cnt->mLive) *100;
    $mdata = array( $cnt->mMinorEditsByte, $cnt->mLive - $cnt->mMinorEditsByte );
    $mlabels = array( "< 20 $msgBytes &middot; ". $wt->numFmt($gminorpct,1).'%', "≥ 20 $msgBytes &middot; ". $wt->numFmt(100 - $gminorpct,1).'%' );
    $mcolors = ( is_numeric($cnt->mMinorEditsByte) ) ? array( $gcolor1, $gcolor2 ) : array('cccccc', 'cccccc') ;
    $graphminorbyte = '<img alt="bla" src="'.xGraph::makeMiniPie( $mdata, $mlabels, $mcolors, $wi->lang ).'" />';
    $wt->assign( 'graphminorbyte', $graphminorbyte );
    
    $gminorpct = ($cnt->mEditsSummary / $cnt->mLive) *100;
    $mdata = array(  $cnt->mLive - $cnt->mEditsSummary, $cnt->mEditsSummary);
    $mlabels = array( $I18N->msg('no_summary'). ' &middot; ' . $wt->numFmt(100 - $gminorpct,1).'%', $I18N->msg('with_summary'). ' &middot; '. $wt->numFmt($gminorpct,1).'%' );
    $mcolors = array( $gcolor1, $gcolor2 );
    $graphminorbyte = '<img alt="bla" src="'.xGraph::makeMiniPie( $mdata, $mlabels, $mcolors, $wi->lang ).'" />';
    $wt->assign( 'grapheditsummary', $graphminorbyte );


//Make list of TopEdited Pages
    $wgNamespaces = $cnt->getNamespaces();
    
    $uniqueEdits = $cnt->getUniqueArticles();
    ksort($uniqueEdits['namespace_specific']);
    
    $num_to_show = 15;

    $out = '<table class="table-condensed table-striped xt-table">';

    foreach( $uniqueEdits['namespace_specific'] as $namespace_id => $articles ) {

        $out .= '<tr><td colspan=22 ><h3>' . $wgNamespaces['names'][$namespace_id] . '</h3></td></tr>';
    
        #asort( $articles );
        #$articles = array_reverse( $articles );
        #$perflog->stack[] = $articles; //$uniqueEdits['namespace_specific'][0];
    
        $i = 0;
        foreach ( $articles as $article => $count ) {
            if( $i == $num_to_show ) {
                $out .= "<tr><td colspan=22 style='padding-left:50px; padding-top:10px;'><a href=\"//".XTOOLS_BASE_WEB_DIR."/topedits/?lang=$lang&wiki=$wiki&user=$user&namespace=${namespace_id}\" >-".$I18N->msg('more')."-</a></td></tr>";
                break;
            }
            
            $nscolon = '';
            if( $namespace_id != 0 ) {
                $nscolon = $wgNamespaces['names'][$namespace_id].":";
            }

            $articleencoded = rawurlencode( str_replace(" ", "_", $nscolon.$article ) );
            $articleencoded = str_replace( array('%2F', '%3A'), array('/', ':'), $articleencoded );
            $article = str_replace("_", " ", $nscolon.$article);
            
            $out .= "
                <tr>
                <td class=tdnum >$count</td>
                <td style=\"max-width:70%\"><a href=\"//$domain/wiki/$articleencoded\" >$article</a></td>
                <td style=\"white-space:nowrap\">
                <a href=\"//$domain/w/index.php?title=Special:Log&type=&page=$articleencoded\" ><small>log</small></a> &middot;
                <a href=\"//".XTOOLS_BASE_WEB_DIR."/articleinfo/?lang=$lang&wiki=$wiki&page=$articleencoded\" ><small>page history</small></a> &middot;
                <a href=\"//".XTOOLS_BASE_WEB_DIR."/topedits/?lang=$lang&wiki=$wiki&user=${user}&page=$articleencoded\" ><small>topedits</small></a>
                </td>
             ";
                            
            $i++;
        }
        
    } 
    $out .= "</table><br />";
    
//Make list of automated edits tools
    $AEBs = $cnt->getAEBTypes();
    arsort( $cnt->mAutoEditTools );
    $list = '<table>';
    foreach ( $cnt->mAutoEditTools as $tool => $count ){
        $shortcut = $AEBs[$tool]["shortcut"];
        $list .= '
                <tr>
                </td><td class="tdnum" style="min-width: 50px; ">'.$wt->numFmt( $count, true ).'</td>
                <td style="padding-left:10px;"><a href="//en.wikipedia.org/wiki/'.$shortcut.'" >'.$tool.'</a>
                </tr>';
    }
    $list .= '</table>';
    
    $wt->assign( 'autoeditslist', $list);
    unset( $list );
    
//Make latest global edits
    $list = '
            <table class="table-striped table-condensed sortable xt-table" >
            <tr>
            <th style="white-space:nowrap">{#date#}</th>
            <th style="white-space:nowrap">{#wiki#}</th>
            <th style="white-space:nowrap">Links</th>
            <th style="white-space:nowrap">{#page#}</th>
            <th>{#comment#}</th>
            </tr>
        ';
    foreach( $cnt->mLatestEditsGlobal["list"] as $i => $row ){
        $fdomain = $wt->metap[ $row["wiki"] ]["domain"];
        $fnamespace = $wt->getNamespaces( $fdomain, true );
        $ns = ($row["page_namespace"] == 0 ) ? "" : $fnamespace["names"][ $row["page_namespace"] ].":"; 
        $title = str_replace('_', ' ', $ns . $row["page_title"]);
        $urltitle = rawurlencode( str_replace(' ', '_', $title) );
        $date = date('Y-m-d, H:i ', strtotime( $row['rev_timestamp']) );
        $list .= '
            <tr>
            <td class="tddate">'.$date.'</td>
            <td>'.$row["wiki"].'</td>
            <td style="white-space:nowrap">(
                <a title="Current diff" href="//'.$fdomain.'/w/index.php?title='.$urltitle.'&amp;diff=prev&amp;oldid='.$row['rev_id'].'" title="'.$title.'">diff</a> &middot; 
                <a title="Special:contributions" href="//'.$fdomain.'/w/index.php?title=Special:Contributions&tagfilter=&contribs=user&target='.$ui->userUrl.'" >log</a> &middot;
                <a title="XTools topedits: useredits per page" href="//'.XTOOLS_BASE_WEB_DIR.'/topedits/index.php?project='.$fdomain.'&user='.$ui->userUrl.'&page='.$urltitle.'">top</a>
                )
            </td>
            <td class="tdtitle break" ><a class="break" href="//'.$fdomain.'/wiki/'.$urltitle.'" >'.$title.'</a></td>
            <td class="revtext break" ><span title="'.htmlspecialchars($row['rev_comment'], ENT_QUOTES).'">'.mb_strimwidth( htmlspecialchars( preg_replace('/[\r\n]/', '', $row['rev_comment']), ENT_QUOTES) ,0,60,'...').'</span></td>
            </tr>
        ';
    }
    $list .= '</table>';
    
#    $list = '<table><tr><td colspan=20 >This feature is currently not available (database upgrade). Sorry. Back soon</td></tr></table>';
    
    $wt->assign( 'latestglobal', $list );
    unset($list);

//Make topten sulinfo table
    $list = '';
    $i = 0;
    $listsum = 0;
    $latestother = '';
    
    foreach ( $cnt->wikis as $sulwiki => $row ){
        
        $latest = @$cnt->mLatestEditsGlobal["latest"][ $sulwiki ];
        $diff = $wt->datediff( DateTime::createFromFormat('YmdHis', $latest ) );
        $diffspan = "<span style=\"color:$diff->diffcolor\" ><small>$diff->difftxt</small></span>";
        
        $trmarker = "";
        if ($sulwiki == $wi->database ){
            $trmarker = '►';
        }
        $blockmarker = "";
        $blocktext = '';
        if ($row["blocked"]){
            $blockmarker = 'red';
            $blocktext = 'The user is currently blocked on this wiki';
        }
        
        if ( $row["editcount"] && $i <= 10 ){
            $list .= '
                <tr>
                <td>'.$trmarker.' <span title="'.$blocktext.'" style="color:'.$blockmarker.'" >'.$sulwiki.'</span></td>
                <td><span class="tdgeneral" ><a href="//'.XTOOLS_BASE_WEB_DIR."-ec/index.php?user=$ui->userUrl&project=$sulwiki".'" >'.$wt->numFmt($row["editcount"]).'</a></span></td>
                <td class="tdnum">'.$diffspan.'</td>
                </tr>
            ';
            $listedwikis[] = $sulwiki;
            $listsum += $row["editcount"];
        }
        else {
            if ( $latest >= $latestother){
                $latestother = $latest;
                $latestotherspan = $diffspan;
                $latestotherwiki = $sulwiki;
            }
        }
        
        $i++;
    }
    
    $wt->assign( 'sulinfotop', $list);
    $wt->assign( 'sulother', ($cnt->mRegisteredWikis - count($listedwikis) ) );
    $wt->assign( 'sulothercount', $wt->numFmt( ($cnt->mTotalGlobal - $listsum) ) );
    $wt->assign( 'latestotherspan', $latestotherspan );
    $wt->assign( 'latestotherwiki', $latestotherwiki );
    $wt->assign( 'sultotal', $wt->numFmt( $cnt->mTotalGlobal ) );
    unset( $list, $listedwikis );
    
//Make TimeCard graphics
    $imgTimecardBubble = '<img alt="Timecard" src="'.xGraph::makeTimecardBubble( $cnt->timeCard["matrix"], $lang ).'" />';
    
    
//Output stuff
    $groupsGlobal = ($cnt->mGroupsGlobal) ? " &bull; global: ".implode(", ", $cnt->mGroupsGlobal) : "";
    $extendedLink = (true || $cnt->extended) ? '' : '<small><a href="//tools.wmflabs.org/xtools/ec/?'.$_SERVER['QUERY_STRING'].'&extended=1 " >Run extended</a></small>';
    $wt->assign('runextended', $extendedLink);
    
    $msgDay = $I18N->msg('days', array("variables"=>array(1)));
    $msgDays = $I18N->msg('days', array("variables"=>array(2)));
    $wt->assign( 'avgeditsperdaytext', $I18N->msg('avg_edits_per_time_sign', array("variables" => array($msgDay))) ) ;
    
    $wt->assign( 'xtoolsbase', XTOOLS_BASE_WEB_DIR );
    $wt->assign( "lang", $lang );
    $wt->assign( "wiki", $wiki );
    $wt->assign( "userid", $cnt->mUID );
    $wt->assign( "username", $cnt->getName() ); 
    $wt->assign( "usernameurl", rawurlencode($cnt->getName()) );
    $wt->assign( "userprefix", rawurlencode($cnt->mNamespaces["names"][2] ) ); 
    $wt->assign( "domain", $domain );
    $wt->assign( "loadwiki", "&wiki=$wiki&lang=$lang" );
    
    $adminlink = '<a href="//'.XTOOLS_BASE_WEB_DIR.'/adminstats/?project='.$wi->domain.'" >sysop</a>';
    $wt->assign( "groups", str_replace('sysop', $adminlink, implode( ', ', $cnt->mGroups)) . $groupsGlobal );
    
    $wt->assign( "firstedit",         $wt->dateFmt( $cnt->mFirstEdit) );
    $wt->assign( "latestedit",         $wt->dateFmt( $cnt->mLatestEdit) );
    $wt->assign( 'editdays',         $cnt->mEditDays." $msgDays" );
    $wt->assign( 'avgeditsperday',     $wt->numFmt( ($cnt->mTotal / $cnt->mEditDays), 1 ) );
    $wt->assign( "unique",               $wt->numFmt( $cnt->getUnique() ) );
    $wt->assign( "average",           $wt->numFmt( $cnt->getAveragePageEdits(),1 ) );
    $wt->assign( "average_len",      $wt->numFmt( $cnt->mAverageLen ) );
    $wt->assign( "pages_created",   $wt->numFmt( $cnt->mCreated + $cnt->mDeletedCreated ) );
    $wt->assign( "pages_moved",       $wt->numFmt( $cnt->mLogActions["move/"] ) ); //+ $cnt->mLogActions["move/move_redir"]
    $wt->assign( "uploaded",           $wt->numFmt( $cnt->mLogActions["upload/"] ) ); //["upload/upload"]
    $wt->assign( "uploaded_commons",$wt->numFmt( $cnt->mUploadedCommons ) );
    $wt->assign( "autoedits",          $wt->numFmt( $cnt->mAutoEdits ) );
    $wt->assign( "reverted",          $wt->numFmt( $cnt->mReverted ) );
    $wt->assign( "edits_summary",      $wt->numFmt( $cnt->mEditsSummary ) );
    $wt->assign( "minor_edits",      $wt->numFmt( $cnt->mMinorEdits ) );
    $wt->assign( "minor_edits_byte",$wt->numFmt( $cnt->mMinorEditsByte ) );
    $wt->assign( "major_edits_byte",$wt->numFmt( $cnt->mMajorEditsByte ) );
    
    $wt->assign( "blockednum",        $wt->numFmt( $cnt->mBlockedNum ) );
    $wt->assign( "blockedlongest",    $wt->numFmt( $cnt->mBlockedLongest ) );
    
    $blockedmessage = '<span>Current block:&nbsp;</span><span style="float:right" >–</span>';
    if ($cnt->mBlockedCurrent){
        $blockedmessage = '<span style="color:red" >Current block:&nbsp;</span><span style="float:right;color:red" >'.$cnt->mBlockedCurrent.'</span>';
    }
    $wt->assign( "blockedcurrent",    $wt->numFmt( $blockedmessage ) );
    
    $wt->assign( "live",               $wt->numFmt( $cnt->mLive ) );
    $wt->assign( "deleted",           $wt->numFmt( $cnt->mDeleted ) );
    $wt->assign( "total",               $wt->numFmt( $cnt->mTotal ) );
    
    $wt->assign( "lastday",           $wt->numFmt( $cnt->mLastDay ) );
    $wt->assign( "lastweek",           $wt->numFmt( $cnt->mLastWeek ) );
    $wt->assign( "lastmonth",          $wt->numFmt( $cnt->mLastMonth ) );
    $wt->assign( "lastyear",           $wt->numFmt( $cnt->mLastYear ) );
    
    $wt->assign( "thanked",           $wt->numFmt( $cnt->mLogActions["thanks/"] ) );  //["thanks/thank"]
    $wt->assign( "approve",           $wt->numFmt( $cnt->mLogActions["review/approve"] ) );
    $wt->assign( "unapprove",           $wt->numFmt( $cnt->mLogActions["review/unapprove"] ) );
    $wt->assign( "patrol",               $wt->numFmt( $cnt->mLogActions["patrol/"] ) );  //["patrol/patrol"]
    $wt->assign( "block",               $wt->numFmt( $cnt->mLogActions["block/"] ) );  //["block/block"]
    $wt->assign( "unblock",           $wt->numFmt( $cnt->mLogActions["block/unblock"] ) );
    $wt->assign( "protect",           $wt->numFmt( $cnt->mLogActions["protect/"] ) );  //["protect/protect"]
    $wt->assign( "unprotect",           $wt->numFmt( $cnt->mLogActions["protect/unprotect"] ) );
    $wt->assign( "delete_page",       $wt->numFmt( $cnt->mLogActions["delete/"] ) ); //["delete/delete"]
    $wt->assign( "delete_rev",           $wt->numFmt( $cnt->mLogActions["delete/revision"] ) );
    $wt->assign( "restore",              $wt->numFmt( $cnt->mLogActions["delete/restore"] ) );
    $wt->assign( "import_iw",           $wt->numFmt( $cnt->mLogActions["import/"] ) );  //["import/interwiki"]
    
    $wt->assign( "namespace_legend", $legendNS );
    $wt->assign( "namespace_graph", '<img src="'.$graphNS.'"  />' );
    
    $wt->assign( "yearcounts", $graphYears );
    
    
    if( $cnt->optin ) {
        $wt->assign( "monthcounts", $graphMonths );
        $wt->assign( "topedited", $out );
        $wt->assign( 'timebubble', $imgTimecardBubble );
    }
    else {
        $nomessage = $I18N->msg( "nograph", array( "variables"=> array( $cnt->getOptinLinkLocal(), $cnt->getOptinLinkGlobal() ) ))
                . "<br /> " . $I18N->msg('nograph2', array( "variables" => array($wt->linkOauthHelp) )  );
        
        $wt->assign( "monthcounts", $nomessage);
        $wt->assign( "topedited", $nomessage );
        $wt->assign( 'timebubble', $nomessage );
    }



unset( $out, $graph, $cnt, $imgTimecardBubble );
$wt->showPage();


/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

    $templateForm = '..old..';

    $templateResult = '
    
    <div class="panel panel-primary" style="text-align:center">
        <div class="panel-heading">
            <p class="xt-heading-top" >
                <a href="//{$domain}/wiki/User:{$usernameurl$}">{$username$}</a>
                <small><span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span></small>
            </p>
        </div>
        <div class="panel-body xt-panel-body-top" >    
            <p>
                <a href="//{$domain}/w/index.php?title=Special%3ALog&type=block&user=&page=User%3A{$usernameurl}&year=&month=-1&tagfilter=" >Block log</a> &middot;
                <a href="//tools.wmflabs.org/guc/?user={$usernameurl}" >Global user contributions</a> &middot;
                <a href="//meta.wikimedia.org/w/index.php?title=Special%3ACentralAuth&target={$usernameurl}" >Global Account Manager</a> &middot;
                <a href="//tools.wmflabs.org/quentinv57-tools/tools/sulinfo.php?username={$usernameurl}" >SUL Info</a> &middot;
                <a href="//tools.wmflabs.org/wikiviewstats/?lang={$lang}&wiki={$wiki}&page={$userprefix}:{$usernameurl}*" >Pageviews in userspace</a> &middot;
            </p>

            
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4  class="topcaption" >{#generalstats#} <span class="showhide" onclick="javascript:switchShow( \'generalstats\', this )">[{#hide#}]</span>
            &nbsp;&nbsp;&nbsp;{$runextended}
            </h4>
        </div>
        <div class="panel-body" id="generalstats">
            <div class="row">
                <div class="col">
                    <table class="table-condensed  xt-table">
                        <tr><td>{#userid#}:</td><td>{$userid}</td></tr>
                        <tr><td style="vertical-align:top;">{#groups#}:</td><td style="max-width:300px;" >{$groups$}</td></tr>
                        <tr><td>{#firstedit#}:</td><td>{$firstedit$}</td></tr>
                        <tr><td>{#latestedit#}:</td><td>{$latestedit$}</td></tr>
                        <tr><td colspan=20 ></td></tr>
                        <tr><td style="border-left:1px solid blue" >{#live#}:</td><td>{$live$}</td></tr>
                        <tr><td style="border-left:1px solid blue" >{#deleted_edits#}:</td><td>{$deleted$}</td></tr>
                        <tr><td style="border-left:1px solid blue" ><b>{#total#}:</b>&nbsp;&nbsp;</td><td><b>{$total$}</b></td></tr>
                    </table>
                </div>
                <div class="col">
                    <table class="table-condensed  xt-table">
                        <tr><td>{#lastday#}:</td><td><span class="tdgeneral" >{$lastday}</span></td></tr>
                        <tr><td>{#lastweek#}:</td><td><span class="tdgeneral" >{$lastweek}</span></td></tr>
                        <tr><td>{#lastmonth#}:</td><td><span class="tdgeneral" >{$lastmonth}</span></td></tr>
                        <tr><td>{#lastyear#}:</td><td><span class="tdgeneral" >{$lastyear}</span></td></tr>
                        <tr><td colspan=20 ></td></tr>
                        <tr><td title="{$editdays}" >{$avgeditsperdaytext}:</td><td><span class="tdgeneral" >{$avgeditsperday}</span></td></tr>
                    </table>
                </div>
            </div>
            <br />
            <div class="row">
                <div class="col">
                    <table class="table-condensed  xt-table">
                        <tr><td style="color:gray">{#live#}:</td></td></tr>
                        <tr><td>{#unique#}:</td><td><span class="tdgeneral" >{$unique$}</span></td>    </tr>
                        <tr><td>{#pages_created#}:</td><td><span class="tdgeneral" ><a href="//{$xtoolsbase}/pages/?user={$usernameurl}&lang={$lang}&wiki={$wiki}&namespace=all&redirects=none" >{$pages_created}</a></span></td></tr>
                        <tr><td>{#pages_moved#}:</td><td><span class="tdgeneral" >{$pages_moved}</span></td></tr>
                        <tr><td title="{#average_per_page#}" >{#average_per_page_sign#}:</td><td><span class="tdgeneral" >{$average$}</span></td></tr>
                        <tr><td title="{#average_change_per_page#}" >{#average_change_per_page_sign#} ({#bytes#}):</td><td><span class="tdgeneral" >{$average_len$}</span></td></tr>
                        <tr><td colspan=20 ></td></tr>
                        <tr><td>{#files_uploaded#}:</td><td><span class="tdgeneral" ><a href="//{$domain}/wiki/Special:ListFiles/{$usernameurl}" >{$uploaded}</a></span></td></tr>
                         <tr><td>{#files_uploaded#} (Commons):</td><td><span class="tdgeneral" ><a href="//commons.wikimedia.org/wiki/Special:ListFiles/{$usernameurl}" >{$uploaded_commons}</a></span></td></tr>
                        <tr><td colspan=20 ></td></tr>
                        <tr><td>{#autoedits#}:</td><td><span class="tdgeneral" ><a href="#autoeditslist">{$autoedits}</a></span></td></tr>
                        <tr><td>{#reverted#}:</td><td><span class="tdgeneral" >{$reverted$}</span></td></tr>
                        <tr><td colspan=20 ></td></tr>
                        <tr><td>{#edits_summary#}:</td><td><span class="tdgeneral" >{$edits_summary}</span></td></tr>
                        <tr><td>{#minoredits#} ({#tagged#}):</td><td><span class="tdgeneral" >{$minor_edits}</span></td></tr>
                        <tr><td>{#numedits#} (<20 {#bytes#}):</td><td><span class="tdgeneral" >{$minor_edits_byte}</span></td></tr>
                        <tr><td>{#numedits#} (>1000 {#bytes#}):</td><td><span class="tdgeneral" >{$major_edits_byte}</span></td></tr>
                    </table>
                </div>
                <div class="col">
                    <table class="table-condensed  xt-table">
                        <tr><td style="color:gray">{#actions#}:</td></td></tr>
                        <tr><td>{#thank#}:</td><td><span class="tdgeneral"><a href="//{$domain}/w/index.php?title=Special%3ALog&type=thanks&user={$usernameurl}&page=&year=&month=-1&tagfilter=" >{$thanked} <small>x</small></a></span></td></tr>
                        <tr><td>{#approve#}:</td><td><span class="tdgeneral"><a href="//{$domain}/w/index.php?title=Special%3ALog&type=review&user={$usernameurl}&page=&year=&month=-1&tagfilter=&hide_patrol_log=1&hide_review_log=1&hide_thanks_log=1" >{$approve} <small>x</small></a></span></td></tr>
                <!--    <tr><td>{#unapprove#}:</td><td><span class="tdgeneral">{$unapprove} <small>x</small></span></td></tr>   -->
                        <tr><td>{#patrol#}:</td><td><span class="tdgeneral"><a href="//{$domain}/w/index.php?title=Special%3ALog&type=patrol&user={$usernameurl}&page=&year=&month=-1&tagfilter=" >{$patrol} <small>x</small></a></span></td></tr>
                        <tr><td colspan=2></td></tr>
                        <tr><td style="color:gray">{#actions_admin#}</td></td></tr>
                        <tr><td>{#block#}:</td><td><span class="tdgeneral">{$block} <small>x</small></span></td></tr>
                <!--    <tr><td>{#unblock#}:</td><td><span class="tdgeneral">{$unblock} <small>x</small></span></td></tr>   -->
                        <tr><td>{#protect#}:</td><td><span class="tdgeneral">{$protect} <small>x</small></span></td></tr>
                 <!--    <tr><td>{#unprotect#}:</td><td><span class="tdgeneral">{$unprotect} <small>x</small></span></td></tr>   -->
                        <tr><td>{#delete#}:</td><td><span class="tdgeneral">{$delete_page} <small>x</small></span></td></tr>
                <!--    <tr><td>{#delete#} (revision):</td><td><span class="tdgeneral">{$delete_rev} <small>x</small></span></td></tr>  -->
                <!--    <tr><td>{#restore#}:</td><td><span class="tdgeneral">{$restore} <small>x</small></span></td></tr>  -->
                        <tr><td>{#import#}:</td><td><span class="tdgeneral">{$import_iw} <small>x</small></span></td></tr>
                        <tr><td colspan=2></td></tr>
                        <tr><td colspan=2></td></tr>
                <!--    <tr><td style="color:gray">罪恶:</td></td></tr> -->
                        <tr><td style="color:gray">过错:</td></td></tr>
                        <tr><td>(Re)blocked:</td><td><span class="tdgeneral"><a href="//{$domain}/w/index.php?title=Special%3ALog&type=block&user=&page=User%3A{$usernameurl}&year=&month=-1&tagfilter=" >{$blockednum} <small>x</small></a></span></td></tr>
                        <tr><td colspan=2 >Longest block:&nbsp;<span style="float:right" >{$blockedlongest}</span></td></tr>
                        <tr><td colspan=2 >{$blockedcurrent}</td></tr>
                    </table>
                </div>
                <div class="col"> <!-- <td style="vertical-align:top; padding-left:70px;" > -->
                    <table class="table-condensed  xt-table">
                        <tr><td colspan=2 style="color:gray;" title="SUL = Single User Login / Unififed lgoin" >SUL {#editcounter#} <br/>({#approximate#}):</td><td style="color:gray;text-align:right"><a href="#latestglobal2" >latest</a></td></tr>
                            {$sulinfotop}
                        <tr><td>{$sulother} {#others#}</td><td><span class="tdgeneral" >{$sulothercount}</span></td><td class="tdnum" title="{$latestotherwiki}" >{$latestotherspan}</td></tr>
                        <tr><td colspan=2></td></tr><tr>
                        <tr><td>{#total#}</td><td><span class="tdgeneral" >{$sultotal}</span></td></tr>
                    </table>
                </div> 
            </div>
            <br />
            <div class="row">
                <td>{$graphminorbyte} {$grapheditsummary}</td>
            </div>
        </div>
    </div>
    
    <a name="nstotals"></a>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#namespacetotals#} <span class="showhide" onclick="javascript:switchShow( \'nstotals\', this )" >[{#hide#}]</span></h4>
        </div>
        <div class="panel-body" id="nstotals">
            <table>
                <tr>
                <td>{$namespace_legend}</td>
                <td style="text-align:center;"><div class="center" style="padding-left:80px;">{$namespace_graph}</div></td>
                </tr>
            </table>
        </div>
    </div>

    <a name="yearcounts"></a>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#yearcounts#} <span class="showhide" onclick="javascript:switchShow( \'yearcounts\', this )">[{#hide#}]</span></h4>
        </div>
        <div class="panel-body" id="yearcounts" >
            {$yearcounts$}
            <br />
        </div>
    </div>
            
    <a name="timecard"></a>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#timecard#} <span class="showhide" onclick="javascript:switchShow( \'timecard\', this )">[{#hide#}]</span></h4>
        </div>
        <div class="panel-body" id="timecard" >
            {$timebubble}
            <br />
        </div>
    </div>
            
    <a id="latestglobal2"></a>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#latestedit#} (global) - <small>{#lastmonth#}, max. 10 / Wiki</small> <span class="showhide" onclick="javascript:switchShow( \'latestglobal\', this )">[{#hide#}]</span></h4>
        </div>
        <div class="panel-body" id="latestglobal" >
            {$latestglobal}
            <br />
        </div>
    </div>
            
    <a name="monthcounts"></a>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#monthcounts#} <span class="showhide" onclick="javascript:switchShow( \'monthcounts\', this )">[{#hide#}]</span></h4>
        </div>
        <div class="panel-body" id="monthcounts" >
            {$monthcounts$}
            <br />
        </div>
    </div>

    <a name="topedited"></a>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#topeditedpages#} <span class="showhide" onclick="javascript:switchShow( \'topedited\', this )">[{#hide#}]</span></h4>
        </div>
        <div class="panel-body" id="topedited">
            {$topedited$}
            <br />
        </div>
    </div>
            
    <a name="autoeditslist"></a>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#autoedits#} ({#approximate#}) <span class="showhide" onclick="javascript:switchShow( \'autoeditslist\', this )">[{#hide#}]</span></h4>
        </div>
        <div class="panel-body" id="autoeditslist" >
            {$autoeditslist}
            <br />
        </div>
    </div>
    
</div>
</div>
    ';
    
    
    
    if( $type == "form" ) { return $templateForm; }
    if( $type == "result" ) { return $templateResult; }
    
    }
?>
