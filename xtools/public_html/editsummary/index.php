<?php
//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );


//Load WebTool class
	$wt = new WebTool( 'Edit summary', 'editsummary', array() );
	$wt->setLimits();

#	$wt->content = getPageTemplate( 'form' );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");

	$namespace = $wgRequest->getVal('namespace');
	$redirects = $wgRequest->getVal('redirects');
	
	$wi = $wt->getWikiInfo();
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;
	
	$dbr = $wt->loadDatabase($lang, $wiki);
	
	$ui = $wt->getUserInfo();
		$user = $wt->user;
#print_r($dbr);

//Get array of namespaces
function getNamespaces() {
   global $http, $name, $lang, $wiki;

   $namespaces = $http->get( 'http://'.$lang.'.'.$wiki.'.org/w/api.php?action=query&meta=siteinfo&siprop=namespaces&format=php', false );
   $namespaces = unserialize( $namespaces );
   $namespaces = $namespaces['query']['namespaces'];
   if( !$namespaces[0] ) { toDie( 'Not a valid wiki.' ); };


   unset( $namespaces[-2] );
   unset( $namespaces[-1] );

   $namespaces[0]['*'] = 'Article';

   return $namespaces;
}


function getEditCounts( $dbr ) {
   global $name, $namespaces, $http, $oldwiki, $oldlang, $ui;

	$name = $ui->userDb;
   
   $edit_sum_maj = 0;
   $edit_sum_min = 0;
   $maj = 0;
   $minn = 0;
   $rmaj = 0;
   $rmin = 0;
   $redit_sum_maj = 0;
   $redit_sum_min = 0;
   $month_totals = array();
   $month_editsummary_totals = array();
   
   $query = "
   		SELECT rev_comment, rev_timestamp, rev_minor_edit 
   		FROM revision_userindex 
   		JOIN page ON page_id = rev_page 
   		WHERE rev_user_text = '$name' AND page_namespace = 0 
   		ORDER BY rev_timestamp DESC
   	";
   
   $result = $dbr->query( $query );

   foreach ($result as $i => $row ) {
   	
      preg_match('/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/', $row['rev_timestamp'], $d);
      list($arr,$year,$month,$day,$hour,$min,$sec) = $d;
      //print_r($d);
      
      $monthkey = $year."/".$month;
      $first_month = strtotime("$year-$month-$day");
      $month_totals[$monthkey]++;
      if ($row['rev_minor_edit'] == 0) {
         if ($row['rev_comment'] !== '') {
            $month_editsummary_totals[$monthkey]++;
            $edit_sum_maj++;
         }

         $maj++;
         if ($rmaj <= 149) {
            $rmaj++;
            if ($row['rev_comment'] != '') {
               $redit_sum_maj++;
            }
         }
      }
      else {
         if ($row['rev_comment'] !== '') {
            $month_editsummary_totals[$monthkey]++;
            $edit_sum_min++;
            $minn++;
         }
		else {
			   $minn++;
			}
         //$min++;
         if ($rmin <= 149) {
            $rmin++;
            if ($row['rev_comment'] != '') {
               $redit_sum_min++;
            }
         }
      }
   }
   
   
   $last_month = strtotime( date( 'Ymd' ) );
   
   return array($edit_sum_maj, $edit_sum_min, $maj, $minn, $redit_sum_maj, $redit_sum_min, $rmaj, $rmin, $month_totals, $month_editsummary_totals, $first_month, $last_month);
}


$temp = getEditCounts( $dbr);

$edit_sum_maj = $temp[0];
$edit_sum_min = $temp[1];
$maj = $temp[2];
$min = $temp[3];
$redit_sum_maj = $temp[4];
$redit_sum_min = $temp[5];
$rmaj = $temp[6];
$rmin = $temp[7];
$month_totals = $temp[8];
$month_editsummary_totals = $temp[9];
$first_month = $temp[10];
$last_month = $temp[11];



//Output general stats
echo '<h2>General user info</h2>';

echo "Username: <a href=\"//$lang.wiki.org/wiki/User:$name\">$name</a><br />";
echo "Edit summary for all major edits: ". (sprintf( '%.2f', $edit_sum_maj ? $edit_sum_maj / $maj : 0 ) * 100). "%<br />";
echo "Edit summary for all minor edits: ". (sprintf( '%.2f', $edit_sum_min ? $edit_sum_min / $min : 0 ) * 100). "%<br />";
echo "Edit summary for last $rmaj major edits: ". (sprintf( '%.2f', $redit_sum_maj ? $redit_sum_maj / $rmaj : 0 ) * 100). "%<br />";
echo "Edit summary for last $rmin minor edits: ". (sprintf( '%.2f', $redit_sum_min ? $redit_sum_min / $rmin : 0 ) * 100). "%<br />";

$months = array();
   for ($date=$first_month; $date<=$last_month; $date+=10*24*60*60) {
      $monthkey = date('Y/m', $date);
      if ($monthkey != $last_monthkey) {
         array_push($months, $monthkey);
         $last_monthkey = $monthkey;
      }
   }
   $monthkey = date('Y/m', $last_month);
   if ($monthkey != $last_monthkey) {
      array_push($months, $monthkey);
      $last_monthkey = $monthkey;
   }

echo "<small>Note: This is only a representation of the Main namespace.</small>";
echo "<table class=months>\n";
   $max_width = max($month_editsummary_totals);
   foreach ($months as $key) {
      $total = $month_editsummary_totals[$key];
      $no_summary = bcsub($month_totals[$key],$total,0);
      if (!$month_totals[$key]) {
         $month_totals[$key] = 0;
      }
      print "<tr><td class=date>$key</td><td>".$month_totals[$key]."</td>";
      print "<td><div class=green style='width:".bcdiv(bcmul(500, $total, 0), $max_width, 0)."px'></div>\n";
      print "<div class=red style='width:".bcdiv(bcmul(500, $no_summary, 0), $max_width, 0)."px'></div>\n";
   }

   echo "</table>";


