<?php
if( $_SERVER['SCRIPT_NAME'] == "/xtools/articleinfo/index.php" ) {
header('HTTP/1.1 301 Moved Permanently');
?><html>
<head>
<script type="text/javascript">
<!--
function delayer(){
    window.location = "//tools.wmflabs.org/xtools-articleinfo/index.php?<?php echo $_SERVER['QUERY_STRING']; ?>"
}
//-->
</script>
</head>
<body onLoad="setTimeout('delayer()', 5000)">
<h1>301 Moved Permanently</h1>
<p>This tool has moved to a new location.  You will be redirected to tools.wmflabs.org/xtools-articleinfo/index.php?<?php echo $_SERVER['QUERY_STRING']; ?> shortly.</p>

</body>
</html>
<?php die();
}

//Requires
    set_include_path( get_include_path() . PATH_SEPARATOR . dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR );
    require_once( 'modules/WebTool.php' );
    $ini = php_ini_loaded_file();


//Load WebTool class
    $wt = new WebTool( 'articleinfo' ); 
    $wt->setLimits( 600,45 );
    $wt->getPageTemplate( "form" );
    
    $article = $wgRequest->getVal( 'article' );
    $article = $wgRequest->getVal( 'page', $article );
    $pageid = $wgRequest->getVal( 'pageid', null );
    $article = ($pageid) ? null : $article;
    
    $begintime = $wgRequest->getVal( 'begin' );
    $endtime = $wgRequest->getVal( 'end' );
    $nofollow = !$wgRequest->getBool( 'nofollowredir');
    $editorlimit = $wgRequest->getVal( 'editorlimit', 30 );
    $reloadpurge = $wgRequest->getVal('reloadpurge', null);
    
    $wi = $wt->wikiInfo;
        $lang = $wi->lang;
        $wiki = $wi->wiki;
        $domain = $wi->domain;

//Show form if &article parameter is not set (or empty)
    if( !$article && !$pageid ) {
        $wt->showPage();
    }

    require_once( 'ArticleInfo.php' );
    
    
//Start dbr, site = global Objects init in WebTool
    $m1 = microtime( true );

    $ttl = 120;
    $reloadhash = XTOOLS_REDIS_FLUSH_TOKEN.'002'.hash('crc32', $lang.$wiki.$article.$begintime.$endtime.$nofollow.$pageid);
    $hash = "xtoolsAI".$reloadhash;
    $lc = $redis->get($hash);
    
    if ($lc === false || $reloadhash == $reloadpurge ){
        $dbr = $wt->loadDatabase( $lang, $wiki );
        $ai = new ArticleInfo( $dbr, $wi, $article, $begintime, $endtime, $nofollow, $pageid );
        if( $ai->historyCount > 60000 ) { $ttl = 1800; }
        if( $ai->historyCount > 100000 ) { $ttl = 86400; }
        if( !$ai->error ) { $redis->setex( $hash, $ttl, serialize( $ai ) ); }
        
    }    
    else{
        $ai = unserialize( $lc );
        unset( $lc );
        $perflog->add('AI', 0, 'from Redis');
    }
    
    
$perflog->add('create ai', microtime(true) - $m1 ); 
    
    if( $ai->historyCount == 0 ) {
        $wt->toDie( 'norevisions', $article ); 
    }
    if( $ai->error ) {
        $wt->toDie( 'error' , $ai->error ) ;
    }

    require_once( 'Graph.php' );
    


//Now we can assign the Smarty variables!
    $wt->content = getPageTemplate( "result" );
    
    $wt->assign( 'reloadpurge', '');
    #$wt->assign( 'reloadpurge', '<a title="reload & purge" href="//'.XTOOLS_BASE_WEB_DIR."/articleinfo/?".$_SERVER["QUERY_STRING"]."&reloadpurge=".$reloadhash."0".'"><img height=15px src="//upload.wikimedia.org/wikipedia/commons/d/dd/Reload_Icon_Red.svg" /></a>'  );
    
    $msgMonth = $I18N->msg('months', array("variables" => array(1) ));
    $msgYear = $I18N->msg('years', array("variables" => array(1) ));
    $wt->assign( 'avgeditspermonth', $I18N->msg('avg_edits_per_time_sign', array("variables" => array($msgMonth) )) );
    $wt->assign( 'avgeditsperyear', $I18N->msg('avg_edits_per_time_sign', array("variables" => array($msgYear) )) );
    
    $wt->assign( "page", $ai->pagetitleFull );
    $wt->assign( "urlencodedpage", $ai->pagetitleFullUrl );
    $wt->assign( 'pageid', $ai->pageid );
    $wt->assign( "totaledits", $wt->numFmt( $ai->data['count'] ) );
    $wt->assign( "editorcount", $wt->numFmt( $ai->data['editor_count'] ) );
    $wt->assign( "pagesize", $wt->numFmt( $ai->data['current_size'] ) );
    $wt->assign( "minoredits", $wt->numFmt( $ai->data['minor_count'] ) );
    $wt->assign( "minoredits", $wt->numFmt( $ai->data['minor_count'] ) );
    $wt->assign( "anonedits", $wt->numFmt( $ai->data['anon_count'] ) );
    $wt->assign( "minorpct", $wt->numFmt( ( $ai->data['minor_count'] / $ai->data['count'] ) * 100, 1 ) );
    $wt->assign( "anonpct", $wt->numFmt( ( $ai->data['anon_count'] / $ai->data['count'] ) * 100, 1 ) );
    $wt->assign( 'botedits', $wt->numFmt( $ai->data['botedit_count'] ) );
    $wt->assign( 'boteditspct', $wt->numFmt( ( $ai->data['botedit_count'] / $ai->data['count'] ) * 100, 1 ) );
    $wt->assign( "autoedits", $wt->numFmt( ( $ai->data['automated_count']) ) );
    $wt->assign( "autoeditspct", $wt->numFmt( ( $ai->data['automated_count'] / $ai->data['count'] ) * 100, 1 ) );
    $wt->assign( "firstedit", $wt->dateFmt( date('Y-m-d H:i:s', strtotime($ai->data['first_edit']['timestamp']) ) ) );
    $wt->assign( "firstuser", $ai->data['first_edit']['user'] );
    $wt->assign( "latestedit", $wt->dateFmt( date( 'Y-m-d H:i:s', strtotime( $ai->data['last_edit']['timestamp'] ) ) ) );
    $wt->assign( "latestuser", $ai->data['last_edit']['user'] );

    $wt->assign( "maxadd", $wt->dateFmt( date( 'Y-m-d H:i:s', strtotime( $ai->data['max_add']['timestamp'] ) ) ) );
    $wt->assign( "maxadduser", $ai->data['max_add']['user'] );
    $wt->assign( "maxaddnum", $wt->numFmt($ai->data['max_add']['size'] ) );
    $wt->assign( "maxadddiff", $ai->data['max_add']['revid'] );
    $wt->assign( "maxdel", $wt->dateFmt( date( 'Y-m-d H:i:s', strtotime( $ai->data['max_del']['timestamp'] ) ) ) );
    $wt->assign( "maxdeluser", $ai->data['max_del']['user'] );
    $wt->assign( "maxdelnum", $wt->numFmt($ai->data['max_del']['size'] ) );
    $wt->assign( "maxdeldiff", $ai->data['max_del']['revid'] );
    
    $wt->assign( "timebwedits", $wt->numFmt($ai->data['average_days_per_edit'] ,1).' '.$I18N->msg('days', array("variables" => array(2))));
    $wt->assign( "editspermonth", $wt->numFmt($ai->data['edits_per_month'] , 1));
    $wt->assign( "editsperyear", $wt->numFmt($ai->data['edits_per_year'] , 1));
    $wt->assign( "lastday", $wt->numFmt( $ai->data['count_history']['today'] ) );
    $wt->assign( "lastweek", $wt->numFmt( $ai->data['count_history']['week'] ) );
    $wt->assign( "lastmonth", $wt->numFmt( $ai->data['count_history']['month'] ) );
    $wt->assign( "lastyear", $wt->numFmt( $ai->data['count_history']['year'] ) );
    $wt->assign( "editsperuser", $wt->numFmt( $ai->data['edits_per_editor'],1 ));
    $wt->assign( "toptencount", $wt->numFmt( $ai->data['top_ten']['count'] ) );
    $wt->assign( "toptenpct", $wt->numFmt( ( $ai->data['top_ten']['count'] / $ai->data['count'] ) * 100, 1 ) );
    
    $wt->assign( 'pagewatchers', $ai->pagewatchers );
    $wt->assign( 'pageviews60', @$wt->numFmt( $ai->pageviews->sumhits ) );
    $wt->assign( 'pageviews_days', '(60 '.$I18N->msg('days', array("variables" => array(2))).')' );
    $wt->assign( "totalauto", $ai->data["automated_count"]);

    $wt->assign( 'linkLanguageTool', ' <a href="//tools.wmflabs.org/languagetool/pageCheck/index?lang='.$wi->lang.'&url='.$ai->pagetitleFullUrl.'" >LanguageTool WikiCheck</a>' );
    
    $wdlink = "–";
    if ( $ai->wikidataItemID ){
        $wdlink = '<span><a href="//www.wikidata.org/wiki/'.$ai->wikidataItemID.'#sitelinks-wikipedia" >'.$ai->wikidataItemID.'</a> &middot; '.count($ai->wikidataItems).' Items</span>';
    }
    $wt->assign( 'wikidatalink', $wdlink );
    $wt->assign( 'linkreasonator', ( $ai->wikidataItemID )  ? '<a href="//tools.wmflabs.org/reasonator/?q='.$ai->wikidataItemID.'" >Reasonator (Wikidata)</a> &middot;&nbsp;' : '' );
    
    $disamblink = '';
    if ($ai->isDisamb ){
        $disamblink = '<span><img height=16px src="//upload.wikimedia.org/wikipedia/commons/c/c3/Disambiguation.svg" alt="disambiguation" />
                        <a href="//tools.wmflabs.org/wikiviewstats/?locale='.$wt->uselang.'&lang='.$wi->lang.'&project='.$wi->wiki.'&type=disamb&page='.$ai->pagetitleFullUrl.'&latest=30" >'.$I18N->msg('disambPage', 'wikiviewstats').'</a></span>';
    }
    $wt->assign( 'disamblink', $disamblink );
    
    $exportlink ='';
#    if ($wt->debug ){
        $exportimg = '<img style="height:30px; padding-right:5px;" src="//upload.wikimedia.org/wikipedia/commons/3/30/Exporte.svg" />';
        $exportlink = $exportimg . '<a href="//'.XTOOLS_BASE_WEB_DIR.'/Export.php?token='.$reloadhash.'" >Export as Wikitable<sup style="color:green"> beta</sup></a>';
#    }
    $wt->assign( 'exportlink', $exportlink);
    
    $wt->assign('enwp10table', $ai->enwp10Html );
    
    
//Colors
    $pixelcolors = array( 
                'all' => '3399FF', 
                'anon' => '66CC00', // 55FF55', 
                'minor' => 'cc9999',
                'size' => '999999',
                'protect' => 'ff3300',
            );


//make minicharts
    $graphanonpct = number_format( ( $ai->data['anon_count'] / $ai->data['count'] ) * 100, 1 );
    $graphuserpct = number_format( 100 - $graphanonpct, 1 );
    $graphminorpct = number_format( ( $ai->data['minor_count'] / $ai->data['count'] ) * 100, 1 );
    $graphmajorpct = number_format( 100 - $graphminorpct, 1 );
    $graphtoptenpct = number_format( ( $ai->data['top_ten']['count'] / $ai->data['count'] ) * 100, 1 );
    $graphbottomninetypct = number_format( 100 - $graphtoptenpct, 1 );
    
    $gcolor1 = '99CCFF';
    $gcolor2 = '99CC00';
    
    $mdata = array( $graphuserpct, $graphanonpct );
    $mlabels = array( $I18N->msg('users', array("variables"=>array(2))).' ('.$graphuserpct.'%)' , $I18N->msg('ips').' ('.$graphanonpct.'%)' );
    $mcolors = array( $gcolor1, $gcolor2 );
    $graphuser = '<img alt="bla" src="'.xGraph::makeMiniPie( $mdata, $mlabels, $mcolors, $wi->lang ).'" />';
    $wt->assign( 'graphuser', $graphuser );
    
    $mdata = array( $graphmajorpct, $graphminorpct );
    $mlabels = array( $I18N->msg('major').' ('.$graphmajorpct.'%)' , $I18N->msg('minor').' ('.$graphminorpct.'%)' );
    $mcolors = array( $gcolor1, $gcolor2 );
    $graphminor = '<img alt="bla" src="'.xGraph::makeMiniPie( $mdata, $mlabels, $mcolors, $wi->lang ).'" />';
    $wt->assign( 'graphminor', $graphminor );
    
    $mdata = array( $graphtoptenpct, $graphbottomninetypct );
    $mlabels = array( $I18N->msg('topten').' ('.$graphtoptenpct.'%)' , $I18N->msg('bottomninety').' ('.$graphbottomninetypct.'%)' );
    $mcolors = array( $gcolor1, $gcolor2 );
    $graphtopten = '<img alt="bla" src="'.xGraph::makeMiniPie( $mdata, $mlabels, $mcolors, $wi->lang ).'" />';
    $wt->assign( 'graphtopten', $graphtopten );
    
    unset($mdata, $mlabels, $graphtopten, $graphminor, $graphuser );
    
    

//Year counts 
    //$yearpixels = $ai->getYearPixels();
    $chartImgYears = xGraph::makeChartArticle("year", $ai->data['year_count'], $ai->pageLogs["years"], $pixelcolors );
    $wt->assign('chartImgYears', "<img class='img-responsive' src='$chartImgYears' alt='bla' />" );
    
    $list = '
        <tr>
        <th>{#years#}</th>
        <th><span class=legendicon style="background-color:#'.$pixelcolors["all"].'"> </span> {#all#}</th>
        <th><span class=legendicon style="background-color:#'.$pixelcolors["anon"].'"> </span> {#ips#}</th>
        <th><span class=legendicon style="background-color:#'.$pixelcolors["anon"].'"> </span> {#ips#} <small>%</small></th>
        <th><span class=legendicon style="background-color:#'.$pixelcolors["minor"].'"> </span> {#minor#}</th>
        <th><span class=legendicon style="background-color:#'.$pixelcolors["minor"].'"> </span> {#minor#} <small>%</small></th>
        <th> {#events#}</th>
        </tr>
      ';
    foreach ( $ai->data['year_count'] as $year => $val ){
        $list .= '
            <tr>
            <td class=date >'.$year.'</td>
            <td class=tdnum >'.$val["all"].'</td>
            <td class=tdnum >'.$val["anon"].'</td>
            <td class=tdnum >'.$wt->numFmt( $val["pcts"]["anon"],1 ).'<small>%</small></td>
            <td class=tdnum >'.$val["minor"].'</td>
            <td class=tdnum >'.$wt->numFmt( $val["pcts"]["minor"],1 ).'<small>%</small></td>
        ';

            if ( !isset($ai->pageLogs["years"][ $year ]) ){ $ai->pageLogs["years"][ $year ] = array(); }
            ksort( $ai->pageLogs["years"][ $year ] );
            
            $actions = array();
            foreach ( $ai->pageLogs["years"][ $year ] as $logaction => $count ){
                $actions[] = "$logaction: $count";
            }
            
        $list .= "<td>".implode(" &middot; ", $actions)."</td>";
        $list .= '</tr>';
    }
    $wt->assign( "yearcountlist", $list);
    unset( $list, $yearpixels );
        
        
//Month graphs    
    $monthpixels = $ai->getMonthPixels();
#    $wt->assign( "monthpixels", $monthpixels );
#    $wt->assign( "evenyears", $ai->getEvenYears() );
    
    $list = '';
    foreach ( $ai->data['year_count'] as $key => $val ){
        $list .= '
            <tr>
            <th>{#months#}</th>
            <th>{#count#}</th>
            <th>{#ips#}</th>
            <th>{#ips#} %</th>
            <th>{#minor#}</th>
            <th>{#minor#} %</th>
            <th>
                <span class=legendicon style="background-color:#'.$pixelcolors["all"].' !important"> </span> {#alledits#} &nbsp;&bull;&nbsp; 
                <span class=legendicon style="background-color:#'.$pixelcolors["anon"].'"> </span> {#ips#} &nbsp;&bull;&nbsp; 
                <span class=legendicon style="background-color:#'.$pixelcolors["minor"].'"> </span> {#minor#} 
            </th>
            </tr>
          ';
        foreach ( $val["months"] as $month => $info ){
            $list .= '
                <tr>
                <td class="date">'.$key.' / '.$month.'</td>
                <td class=tdnum >'.$info["all"].'</td>
                <td class=tdnum >'.$info["anon"].'</td>
                <td class=tdnum >'.$wt->numFmt( $info["pcts"]["anon"],1 ).'%</td>
                <td class=tdnum >'.$info["minor"].'</td>
                <td class=tdnum >'.$wt->numFmt( $info["pcts"]["minor"],1 ).'%</td>
                <td style="height:10px;">
             ';
            if ( $info["all"] != 0 ){
                $list .= '
                <div class="bar" style="height:70%;background-color:#'.$pixelcolors["all"].';width:'.$monthpixels[$key][$month]["all"].'px;"></div>
                <div class="bar" style="height:70%;border-left:'.$monthpixels[$key][$month]["anon"].'px solid #'.$pixelcolors["anon"].'"></div>
                <div class="bar" style="height:70%;border-left:'.$monthpixels[$key][$month]["minor"].'px solid #'.$pixelcolors["minor"].'"></div>
              ';
            }
            $list .= '</td></tr>';
        }
        $list .= '<tr class=monthsep style="border:none"; ><td colspan=20 style="border:none" ></td></tr>';
    }
    $wt->assign( "monthcountlist", $list);
    unset( $list, $monthpixels );

    
//usertable    

    $list = '';
    $countrevs = 0;
    $count = 0;
    foreach( $ai->data['editors'] as $user => $info ){
        
        $textshare = ( isset($ai->data["textshares"][$user]["all"]) ) ? $ai->data["textshares"][$user]["all"] : 0;
        
        $list .= '
            <tr>
            <td><a href="//{$domain}/wiki/User:'.$info["urlencoded"].'" >'.$user.'</a></td>
            <td>
                <a title="edit count" href="//'.XTOOLS_BASE_WEB_DIR.'/ec/?user='.$info["urlencoded"].'&amp;lang='.$lang.'&amp;wiki='.$wiki.'" >ec</a>
                <a title="edit count" href="//'.XTOOLS_BASE_WEB_DIR.'/topedits/?user='.$info["urlencoded"].'&amp;lang='.$lang.'&amp;wiki='.$wiki.'&amp;page='.$ai->pagetitleFullUrl.'" > &middot topedits</a>
            </td>
            <td class=tdnum >'.$info["all"].'</td>
            <td class=tdnum >'.$info["minor"].'</td>
            <td class=tdnum >'.$wt->numFmt( $info["minorpct"],1 ).'<small>%</small></td>
            <td class=tddate >'.$info["first"].'</td>
            <td class=tddate >'.$info["last"].'</td>
            <td class=tdnum >'.$wt->numFmt( $info["atbe"],1 ).'</td>
            <td class=tdnum >'.$wt->numFmt( $textshare ).'</td>
            </tr>
        ';
        $countrevs += $info["all"];
        $count++;

        if ( $count >= $editorlimit ) {
            $newlimit = $editorlimit *10;
            $list .= '
                <tr><td colspan=22 ></td></tr>
                <tr>
                    <td colspan=2 >'. ($ai->data['editor_count'] - $count).' {#others#}</td>
                    <td class=tdnum >'. $wt->numFmt( ($ai->data['count'] - $countrevs) ).'</td>
                    <td colspan=22 ><a href="//tools.wmflabs.org'. $_SERVER['REQUEST_URI'] .'&editorlimit='.$newlimit.'#topeditors" >&nbsp;&nbsp;-{#more#}-</a></td>
                </tr>    
                ';
            break;                    
        }
    }
#<td class=tdnum >'.$info["size"].' KB</td>
    $wt->assign( "usertable", $list );
    
    
    $chartImgTopEditors = xGraph::makePieTopEditors( $I18N->msg('toptenbyedits'), $ai->data["count"], $ai->data["editors"], $wi->lang );
    $wt->assign( 'chartTopEditorsByCount', "<img src='$chartImgTopEditors' alt='bla' />" );
    $chartImgTopEditors = xGraph::makePieTopEditors( $I18N->msg('toptenbytext'), $ai->data["textshare_total"], $ai->data["textshares"], $wi->lang );
    $wt->assign( 'chartTopEditorsByText', "<img src='$chartImgTopEditors' alt='bla' />" );
    

//bots list
    $list = '';
    foreach( $ai->data["bots"] as $bot => $count ){
        $list .= '<tr>
                <td><a href="//'.$domain.'/wiki/User:'.$bot.'">'.$bot.'</a></td>
                <td>
                    <a title="edit count" href="//'.XTOOLS_BASE_WEB_DIR.'/ec/?user='.$bot.'&amp;lang='.$lang.'&amp;wiki='.$wiki.'" >ec</a>
                    <a title="edit count" href="//'.XTOOLS_BASE_WEB_DIR.'/topedits/?user='.$bot.'&amp;lang='.$lang.'&amp;wiki='.$wiki.'&amp;page='.$ai->pagetitleFullUrl.'" > &middot topedits</a>
                </td>
                <td class="tdnum" >'.$wt->numFmt($count).'</td>
                </tr>';
    }
    $wt->assign( 'botslist', $list );
    
    unset($list);
    
//maintenance list
    $list = '';
    if ( $ai->checkResults['list'] ){
        
        $edit['checkwiki'] ='<span ><a target="_new" href="//'.$domain.'/w/index.php?title='.$ai->pagetitleFullUrl.'&action=edit ">Edit</a>';
        $edit['languagetool'] = $edit['checkwiki'];
        $edit['wikidata']  ='<span ><a target="_new" href="//www.wikidata.org/wiki/'.$ai->wikidataItemID.'?uselang='.$wi->lang.'">Edit</a>';
        
        $list = '<tr>
            <th>Prio</th>
            <th>Name</th>
            <th>Notice</th>
            <th>Edit</th>
            <th>Explanation</th>
            
            </tr>
            ';
    
        foreach( $ai->checkResults['list'] as $i => $row ){
            $list .= '<tr>
                    <td>'.$row["prio"].'</td>
                    <td>'.$row["name"].'</td>
                    <td>'.$row["notice"].'</td>
                    <td>'.$edit[ $row['source'] ].'</td>
                    <td>'.$row["explain"].'</td>
                    </tr>';
        }
        $imgbugs = '<img height=20px style="padding-right:8px;margin-bottom:4px;" src="//upload.wikimedia.org/wikipedia/commons/0/04/Icon_attention.png" />' ;
    }
    else{
        $list .= '<tr><td colspan=22 >No known bugs or errors.</td></tr>';
        $imgbugs = '<img height=18px style="padding-right:7px" src="//upload.wikimedia.org/wikipedia/commons/b/bf/Crystal_button_ok.png" />';
    }
    $wt->assign( 'maintenance', $list );
    $wt->assign( 'numbugs', count($ai->checkResults['list'] ) );
    $wt->assign( 'imgbugs', $imgbugs );
    
    unset($list);
    

//Pageviews

    $charttitle = $I18N->msg('pageviews')." (60 ".$I18N->msg('days', array("variables" => array(2)))."):  ".$wt->numFmt( $ai->pageviews->sumhits );
    $cImg = xGraph::makeBarPageViews( $charttitle, $ai->pageviews, $wi->lang );
    $chartImgPageviews = "<a href=\"//tools.wmflabs.org/wikiviewstats/?latest=60&amp;lang=$lang&amp;wiki=$wiki&amp;page=$ai->pagetitleFullUrl\" ><img class=\"img-responsive\" src=\"".$cImg."\" alt='bla' /></a>";
    $wt->assign( 'chartImgPageviews', $chartImgPageviews);
    

//Assign links
    foreach ( $ai->links as $link ){
        $wt->assign( $link["type"], $wt->numFmt( $link["value"] ) );
    }
    
//tools list
    $list = '<table>';
    foreach( $ai->data["tools"] as $tool => $count ){
        $list .= '<tr><td>'.$tool.'</td><td> &middot; '.$wt->numFmt($count).'</td></tr>';
    }
    $list .= '</table>';            
    $wt->assign( 'toolslist', $list );



    $wt->assign( "domain", $domain );
    $wt->assign( "lang", $lang );
    $wt->assign( "wiki", $wiki );
    $wt->assign( 'days', $I18N->msg('days', array("variables"=>array(2) ) ) );
    

unset( $ai, $list );


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
                <a href="//{$domain}/wiki/{$urlencodedpage}">{$page}</a>
                <small><span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span></small> 
            </p>
        </div>
        <div class="panel-body xt-panel-body-top"  >
            <p>
                <a href="//{$domain}/w/index.php?title=Special:Log&type=&page={$urlencodedpage}" >Page log</a> &middot;
                {$linkLanguageTool} &middot;
                {$linkreasonator}
                {$reloadpurge}
            </p>
        
    
    
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4  class="topcaption" >{#generalstats#} <span class="showhide" onclick="javascript:switchShow( \'generalstats\', this )">[{#hide#}]</span></h4>
        </div>
        <div class="panel-body" id="generalstats">
            <table><tr>
                <td style="vertical-align:top;" >
                <table class="table-condensed  xt-table">
                    <tr><td>ID:</td><td class="tdtop1" ><a href="//{$domain}/w/index.php?title={$urlencodedpage}&action=info" >{$pageid}</a></td></tr>
                    <tr><td>Wikidata ID:</td><td class="tdtop1" >{$wikidatalink}</td></tr>
                    <tr><td>{#pagesize#}:</td><td class="tdtop1" >{$pagesize} Bytes</td></tr>
                    <tr><td style="border-left:1px solid blue" >{#totaledits#}:</td><td class="tdtop1" >{$totaledits}</td></tr>
                    <tr><td style="border-left:1px solid blue" ><a href="#topeditors" >{#editorcount#}:</a></td><td class="tdtop1" >{$editorcount}</td></tr>
                    <tr><td colspan=20 >&nbsp;</td></tr>
        
                    <tr><td>{#minoredits#}:</td><td class="tdtop1" >{$minoredits} &nbsp;&middot;&nbsp;({$minorpct}%)</td></tr>
                    <tr><td>{#anonedits#}:</td><td class="tdtop1" >{$anonedits} &nbsp;&middot;&nbsp;({$anonpct}%)</td></tr>
                    <tr><td>{#botedits#}:</td><td class="tdtop1" >{$botedits} &nbsp;&middot;&nbsp;({$boteditspct}%)</td></tr>
                <!--    <tr><td>{#autoedits#}:</td><td class="tdtop1" >{$autoedits} &nbsp;<small>({$autoeditspct}%)</small></td> -->
                    <tr><td colspan=20 ></td></tr>
                    <tr><td title="{#timebwedits#}" >{#avg_timebwedits_sign#}:</td><td class="tdtop1" >{$timebwedits}</td></tr>
                    <tr><td title="{#editsperuser#}" >{#avg_edits_per_user_sign#}:</td><td class="tdtop1" >{$editsperuser}</td></tr>
                    <tr><td title="{#editspermonth#}" >{$avgeditspermonth}:</td><td class="tdtop1" >{$editspermonth}</td></tr>
                    <tr><td title="{#editsperyear#}" >{$avgeditsperyear}:</td><td class="tdtop1" >{$editsperyear}</td></tr>
                    <tr><td colspan=20 ></td></tr>
                    <tr><td>{#lastday#}:</td><td class="tdtop1" >{$lastday}</td></tr>
                    <tr><td>{#lastweek#}:</td><td class="tdtop1" >{$lastweek}</td></tr>
                    <tr><td>{#lastmonth#}:</td><td class="tdtop1" >{$lastmonth}</td></tr>
                    <tr><td>{#lastyear#}:</td><td class="tdtop1" >{$lastyear}</td></tr>
                    <tr><td colspan=20 ></td></tr>
                    <tr><td>{#toptencount#}:</td><td class="tdtop1" >{$toptencount} &nbsp;&middot;&nbsp;({$toptenpct}%)</td></tr>
                </table>
                </td>
                <td style="vertical-align:top; padding-left:70px;" >
                <table class="table-condensed xt-table" >
                    <tr><td colspan=20 >&nbsp;</td></tr>
                    <tr><td>{#firstedit#}:</td><td class="tdtop1" ><span style="font-size:97%" >{$firstedit}</span> &nbsp;&bull;&nbsp; <a href="//{$domain}/wiki/User:{$firstuser}" >{$firstuser}</a></td></tr>
                    <tr><td>{#latestedit#}:</td><td class="tdtop1" ><span style="font-size:97%" >{$latestedit}</span> &nbsp;&bull;&nbsp; <a href="//{$domain}/wiki/User:{$latestuser}" >{$latestuser}</a></td></tr>
                    <tr><td>{#maxadd#}:</td><td class="tdtop1" ><span style="font-size:97%" >{$maxadd}</span> &nbsp;&bull;&nbsp; <a href="//{$domain}/wiki/User:{$maxadduser}" >{$maxadduser}</a> &nbsp;&bull;&nbsp; <a style="color:green" href="//{$lang}.{$wiki}.org/w/index.php?diff=prev&oldid={$maxadddiff} " >+{$maxaddnum}</td></tr>
                    <tr><td>{#maxdel#}:</td><td class="tdtop1" ><span style="font-size:97%" >{$maxdel}</span> &nbsp;&bull;&nbsp; <a href="//{$domain}/wiki/User:{$maxdeluser}" >{$maxdeluser}</a> &nbsp;&bull;&nbsp; <a style="color:#cc0000" href="//{$lang}.{$wiki}.org/w/index.php?diff=prev&oldid={$maxdeldiff} " >{$maxdelnum}</a></td></tr>
                    <tr><td colspan=20 >&nbsp;</td></tr>
                    <tr><td>{#links_in#}:</td><td><span class="tdgeneral" ><a href="//{$domain}/w/index.php?title=Special:WhatLinksHere/{$urlencodedpage}&hidetrans=1" >{$links_in}</a><span></td></tr>
                    <tr><td>{#redirects#}:</td><td><span class="tdgeneral" ><a href="//{$domain}/w/index.php?title=Special:WhatLinksHere/{$urlencodedpage}&hidetrans=1&hidelinks=1&limit=500" >{$redirects}</a><span></td></tr>
                    <tr><td>{#links_out#}:</td><td><span class="tdgeneral" >{$links_out}</span></td></tr>
                    <tr><td>{#links_ext#}:</td><td><span class="tdgeneral"" >{$links_ext}</span></td></tr>
                    <tr><td>{#pagewatchers#}:</td><td><span class="tdgeneral" >{$pagewatchers}</span></td></tr>
                    <tr><td>{#pageviews#} {$pageviews_days}:</td><td><span class="tdgeneral" ><a href="//tools.wmflabs.org/wikiviewstats/?page={$urlencodedpage}&amp;latest=60&amp;lang={$lang}&amp;wiki={$wiki}" >{$pageviews60}</a></span></td></tr>
                    <tr><td colspan=20 >&nbsp;</td></tr>
                    <tr><td>{$imgbugs}<a href="#maintenance" >{#bugs#} / Todo:</a></td><td><span class="tdgeneral" >{$numbugs}</span></td></tr>
                    <tr><td>{$disamblink}</td></tr>
                </table>
                </td>
                </tr>
            </table>
            <br />
            <table>
                <tr>
                <td>{$graphuser}</td>
                <td>{$graphminor}</td>
                <td>{$graphtopten}</td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- yeargraphs -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#yearcounts#} <span class="showhide" onclick="javascript:switchShow( \'yearcounts\', this )">[{#hide#}]</span></h4>
        </div>
        <div class="panel-body" id="yearcounts">
            <div style="padding:20px">
                {$chartImgYears}
            </div>
            <table class="leantable2 months sortable table-striped xt-table" style="margin-left:60px;">
                {$yearcountlist}
            </table>
            <br />
        </div>
    </div>
    
    <!-- pageviews -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#pageviews#} <span class="showhide" onclick="javascript:switchShow( \'pageviews\', this )">[{#hide#}]</span></h4>
        </div>
        <div class="panel-body" id="pageviews" style="padding:20px" >
            {$chartImgPageviews}
            <br />
        </div>
    </div>
    
    <!-- $usertable -->
    <a id="topeditors"></a>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#usertable#} <span class="showhide" onclick="javascript:switchShow( \'topeditors_div\', this )">[{#hide#}]</span>
                <small style="padding-left:30px;">
                {$exportlink}
                </small>
            </h4>
        </div>
        <div class="panel-body" id="topeditors_div">
            <table>
                <tr>
                <td>{$chartTopEditorsByCount}</td>
                <td>{$chartTopEditorsByText}</td>
                </tr>
            </table>
            <br />
            <span><sup>1</sup> {#count#} </span><br />
            <span><sup>2</sup> {#atbe#} ({$days})</span>
            <table class="sortable table-striped table-condensed xt-table" >
                <tr>
                    <th>{#username#}</th>
                    <th>Links</th>
                    <th># <sup>1</sup></th>
                    <th>{#minor#}</th>
                    <th>%</th>
                    <th>{#firstedit#}</th>
                    <th>{#latestedit#}</th>
                    <th>atbe <sup>2</sup></th>
                    <th>{#added#} (Bytes)</th>
                </tr>
                {$usertable}
            </table>
            <br />
            <div>
                <p><strong>Botlist</strong></p>
                <table class="sortable table-striped table-condensed xt-table" >
                    <tr>
                    <th>Bot</th>
                    <th>Links</th>
                    <th>{#count#}</th>
                    </tr>
                    {$botslist}
                </table>
            </div>
        </div>
    </div>
            
    <!-- maimtenance -->
    <a id="maintenance"></a>
    <a id="bugs"></a>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#bugs#} <span class="showhide" onclick="javascript:switchShow( \'maintenance_div\', this )">[{#hide#}]</span>
            &nbsp;&nbsp;&nbsp;<small>powered by: <a href="//tools.wmflabs.org/languagetool/pageCheck/index?lang={$lang}&url={$urlencodedpage}" >LanguageTool</a> &nbsp;&bull;&nbsp;
            <a href="//tools.wmflabs.org/checkwiki/" >Project Check Wikipedia</a>
            </small>
            </h4>
        </div>
        <div class="panel-body" id="maintenance_div">
            <table class="table-condensed table-striped  xt-table">
                {$maintenance}
            </table>
        </div>
    </div>

    <!-- assessment -->
    <a id="enwp10"></a>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>Assessment <span class="showhide" onclick="javascript:switchShow( \'enwp10_div\', this )">[{#hide#}]</span>
            &nbsp;&nbsp;&nbsp;<small>powered by: <a href="//tools.wmflabs.org/enwp10/cgi-bin/list2.fcgi/" >EnWP Assessment</a> &bull;
            <a href="//en.wikipedia.org/?title=Wikipedia:Version_1.0_Editorial_Team/Assessment" >Legend</a>
            </small>
            </h4>
        </div>
        <div class="panel-body" id="enwp10_div">
            {$enwp10table}
        </div>
    </div>
            
    <!-- monthgraphs -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>{#monthcounts#} <span class="showhide" onclick="javascript:switchShow( \'monthcounts\', this )">[{#hide#}]</span></h4>
        </div>
        <div class="panel-body" id="monthcounts">
            <table class="months leantable2 table-striped  xt-table">
                {$monthcountlist}
            </table>
        </div>
    </div>

</div>            
</div>
    ';
    
        
    if( $type == "form" ) { return $templateForm; }
    if( $type == "result" ) { return $templateResult; }
}




?>
