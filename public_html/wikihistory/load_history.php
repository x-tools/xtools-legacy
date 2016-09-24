<?php
  error_reporting(E_ALL ^ E_NOTICE);

  include("inc/i18n.inc.php");
  $lang = getLang();

  date_default_timezone_set('UTC');
  if ($lang == "de")
    setlocale(LC_ALL, 'de_DE.UTF8');
  else
    setlocale(LC_ALL, 'en_US.UTF8');

  global $page_title;

  if (!isset($_REQUEST['page_id'])) { print "Error."; return; }
  $page_id = $_REQUEST['page_id'];

  include_once("db.inc.php");
  $project = "en.wikipedia.org";
  $wikidb = db_enwiki(); 
  $baseurl = "//" . $project . "/w/";

  // Get List of Revision
  $revisions = load_revisions_db($page_id);

  $totaledits = count($revisions);

  $users = array();
  $editsminor = 0;
  $editsanon = 0;
  
  $firstyear = 0;
  $years_totaledits = array(); $years_minoredits = array(); $years_anonedits = array(); $years_articlesize_max = array(); $years_articlesize_sum = array(); $years_articlesize_min = array();
  $firstmonth = 0;
  $month_totaledits = array(); $month_minoredits = array(); $month_anonedits = array(); $month_articlesize_max = array(); $month_articlesize_sum = array(); $month_articlesize_min = array();
  $firstweek = 0;
  $week_totaledits = array(); $week_minoredits = array(); $week_anonedits = array(); $week_articlesize_max = array(); $week_articlesize_sum = array(); $week_articlesize_min = array();
  
  $firstedit = -1; $lastedit = -1;
  $articlesize = 0;
  foreach ($revisions as $r)
  {
    //(
    //    [revid] => 109100685
    //    [parentid] => 97577479
    //    [minor] => 
    //    [user] => Spielvogel
    //    [timestamp] => 2012-10-09T06:18:18Z
    //    [size] => 6933
    //    [sha1] => 4da79f1c01e19ba3859d31964feb2d0aab280272
    //    [comment] => Hinweis auf Ã„hnlichkeit mit VW eT!  Danke den Sichtern.
    //)
    if (isset($r['minor'])) $editsminor++;
    if (isset($r['anon'])) $editsanon++;
    
    $users[$r['user']]['edits']++;
    if (isset($r['minor'])) $users[$r['user']]['minoredits']++;
    if (isset($r['anon'])) $users[$r['user']]['anon'] = 1;
    
    $t = strtotime($r['timestamp']);
    if ((!isset($users[$r['user']]['firstedit'])) || ($t < $users[$r['user']]['firstedit'])) $users[$r['user']]['firstedit'] = $t;
    if ((!isset($users[$r['user']]['lastedit'])) || ($t > $users[$r['user']]['lastedit'])) $users[$r['user']]['lastedit'] = $t;
    
    if (($firstedit == -1) || ($t < $firstedit)) { $firstedit = $t; $firstedit_user = $r['user']; }
    if (($lastedit == -1) || ($t > $lastedit)) { $lastedit = $t; $lastedit_user = $r['user']; $articlesize = $r['size']; }
    
    // yearly values
    $year = strftime("%Y", $t);
    if ($firstyear == 0) $firstyear = $year;
    $years_totaledits[$year]++;
    if (isset($r['minor'])) $years_minoredits[$year]++;
    if (isset($r['anon'])) $years_anonedits[$year]++;
    if ($r['size'] > $years_articlesize_max[$year]) $years_articlesize_max[$year] = $r['size'];
    $years_articlesize_sum[$year] += $r['size'];
    if ((!isset($years_articlesize_min[$year])) || ($r['size'] < $years_articlesize_min[$year])) $years_articlesize_min[$year] = $r['size'];
    // monthly values
    $month = strftime("%Y", $t) * 100 + strftime("%m", $t);
    if ($firstmonth == 0) $firstmonth = $month;
    $month_totaledits[$month]++;
    if (isset($r['minor'])) $month_minoredits[$month]++;
    if (isset($r['anon'])) $month_anonedits[$month]++;
    if ($r['size'] > $month_articlesize_max[$month]) $month_articlesize_max[$month] = $r['size'];
    $month_articlesize_sum[$month] += $r['size'];
    if ((!isset($month_articlesize_min[$month])) || ($r['size'] < $month_articlesize_min[$month])) $month_articlesize_min[$month] = $r['size'];
    // weekly values
    $week = strftime("%G", $t) * 100 + strftime("%V", $t);
    if ($firstweek == 0) $firstweek = $week;
    $week_totaledits[$week]++;
    if (isset($r['minor'])) $week_minoredits[$week]++;
    if (isset($r['anon'])) $week_anonedits[$week]++;
    if ($r['size'] > $week_articlesize_max[$week]) $week_articlesize_max[$week] = $r['size'];
    $week_articlesize_sum[$week] += $r['size'];
    if ((!isset($week_articlesize_min[$week])) || ($r['size'] < $week_articlesize_min[$week])) $week_articlesize_min[$week] = $r['size'];
  }
  
  $totalusers = count($users);
  $usersanon = 0; foreach($users as $u) if (isset($u['anon'])) $usersanon++;
  
  // ------- Output -----------------------------------------------------------
  // Title
  print "$('#article_title').html('<a href=\'//" . $project . "/wiki/" . urlencode(str_replace(" ", "_", $page_title)) . "\'>" . addslashes($page_title) . "</a>');\n";
  
  // Number of Edits
  print "$('#editstotal').html('" . $totaledits . "');\n";
  print "$('#editsminor').html('" . $editsminor . "');\n";
  print "$('#editsminorpercent').html('&nbsp;(" . num_format($editsminor / $totaledits * 100, 1) . "&#x202f;%)');\n";
  print "$('#editsanon').html('" . $editsanon . "');\n";
  print "$('#editsanonpercent').html('&nbsp;(" . num_format($editsanon / $totaledits * 100, 1) . "&#x202f;%)');\n";
  print "chart_edits(" . $editsanon . "," . $editsminor . "," . $totaledits . ",['" . t(420) . "','" . t(421) . "','" . t(422) . "']);";

  // Users
  print "$('#userstotal').html('" . $totalusers . "');\n";
  print "$('#usersanon').html('" . $usersanon . "');\n";
  print "$('#usersanonpercent').html('&nbsp;(" . num_format($usersanon / $totalusers * 100, 1) . "&#x202f;%)');\n";
  print "$('#editsperuser').html('" . num_format($totaledits / $totalusers, 2) . "');\n";

  // First/Last Edit
  print "$('#firstedit').html('" . strftime(day_format(), $firstedit) . "<br />(" . get_output_for_date($firstedit) . ")<br />" . t(410) . ": " . user_link($firstedit_user) . "');\n";
  print "$('#lastedit').html('" . strftime(day_format(), $lastedit) . "<br />(" . get_output_for_date($lastedit) . ")<br />" . t(410) . ": " . user_link($lastedit_user) . "');\n";
  print "$('#editsperday').html('" . num_format($totaledits / ((time() - $firstedit) / 86400), 2) . "');\n";
  print "$('#editspermonth').html('" . num_format($totaledits / ((time() - $firstedit) / 86400 / 30), 2) . "');\n";
  print "$('#editsperyear').html('" . num_format($totaledits / ((time() - $firstedit) / 86400 / 365.25), 2) . "');\n";

  print "$('#articlesize').html('" . num_format($articlesize, 0) . " Byte');\n";

  // User Table
  foreach($users as $username => $u)
  {
    print "usertable_addrow('" . addslashes($username) . "', " . intval($u['edits']) . ", " . intval($u['minoredits']) . ", '" . strftime("%Y-%m-%d %H:%M", $u['firstedit']) . "', '" . strftime("%Y-%m-%d %H:%M", $u['lastedit']) . "');\n";
  }
  print "$('#usertable').tablesorter({sortList: [[1,1]], headers: { 3: { sorter:'p_parser' }, 6: { sorter:'p_parser' } }});\n";

  // Time Chart
  print "function chart_time_year(){\n $('#button_year').addClass('button_selected');$('#button_week').removeClass('button_selected'); $('#button_month').removeClass('button_selected');";
  $currentyear = strftime("%Y");
  $lastyear = $currentyear;
  for ($y = $firstyear; $y <= $currentyear; $y++)
    if ($years_totaledits[$y] > 0) $lastyear = $y;
  $o1 = ""; $o2 = ""; $o3 = ""; $o4 = ""; $o5 = ""; $o6 = ""; $o7 = "";
  for ($y = $firstyear; $y <= $currentyear; $y++)
  {
    $o1 .= "'" . $y . "',";
    $o2 .= intval($years_totaledits[$y]) . ",";
    $o3 .= intval($years_minoredits[$y]) . ",";
    $o4 .= intval($years_anonedits[$y]) . ",";
    $o5 .= intval($years_articlesize_max[$y]) . ",";
    $o6 .= (($years_totaledits[$y] == 0) ? 0 : intval($years_articlesize_sum[$y] / $years_totaledits[$y])) . ",";
    $o7 .= intval($years_articlesize_min[$y]) . ",";
  }
  $o1 = substr($o1, 0, -1); $o2 = substr($o2, 0, -1); $o3 = substr($o3, 0, -1); $o4 = substr($o4, 0, -1); $o5 = substr($o5, 0, -1); $o6 = substr($o6, 0, -1); $o7 = substr($o7, 0, -1);
  print "  var names = [ $o1 ]; var totaledits = [ $o2 ]; var minoredits = [ $o3 ]; var anonedits = [ $o4 ]; var articlesize_max = [ $o5 ]; var articlesize_avg = [ $o6 ]; var articlesize_min = [ $o7 ];
  chart_time('year', true, true, true, names, totaledits, minoredits, anonedits, articlesize_max, articlesize_avg, articlesize_min );
  }\n";
  
  print "function chart_time_month(){\n $('#button_year').removeClass('button_selected');$('#button_week').removeClass('button_selected'); $('#button_month').addClass('button_selected');";
  $currentmonth = strftime("%Y") * 100 + strftime("%m");
  $lastmonth = $currentmonth;
  for ($y = $firstmonth;$y<=$currentmonth;$y++)
    if ($month_totaledits[$y] > 0) $lastmonth = $y;
  $monthcount = 0;
  $o1 = ""; $o2 = ""; $o3 = ""; $o4 = ""; $o5 = ""; $o6 = ""; $o7 = "";
  for ($y = $firstmonth;$y<=$lastmonth;$y++)
  {
    $year = intval($y / 100);
    $month = $y % 100;
    if (($month < 1) || ($month > 12)) continue;
    $o1 .= "'" . $month . "\\n" . str_pad(($year % 100),2,'0',STR_PAD_LEFT) . "',";
    $o2 .= intval($month_totaledits[$y]) . ",";
    $o3 .= intval($month_minoredits[$y]) . ",";
    $o4 .= intval($month_anonedits[$y]) . ",";
    $o5 .= intval($month_articlesize_max[$y]) . ",";
    $o6 .= (($month_totaledits[$y] == 0) ? 0 : intval($month_articlesize_sum[$y] / $month_totaledits[$y])) . ",";
    $o7 .= intval($month_articlesize_min[$y]) . ",";
    $monthcount++;
  }
  $o1 = substr($o1, 0, -1); $o2 = substr($o2, 0, -1); $o3 = substr($o3, 0, -1); $o4 = substr($o4, 0, -1); $o5 = substr($o5, 0, -1); $o6 = substr($o6, 0, -1); $o7 = substr($o7, 0, -1);
  print "  var names = [ $o1 ]; var totaledits = [ $o2 ]; var minoredits = [ $o3 ]; var anonedits = [ $o4 ]; var articlesize_max = [ $o5 ]; var articlesize_avg = [ $o6 ]; var articlesize_min = [ $o7 ];
  chart_time('month', true, true, true, names, totaledits, minoredits, anonedits, articlesize_max, articlesize_avg, articlesize_min );
  }\n";

  print "function chart_time_week(){\n $('#button_year').removeClass('button_selected');$('#button_week').addClass('button_selected'); $('#button_month').removeClass('button_selected');";
  $currentweek = strftime("%G") * 100 + strftime("%V");
  $lastweek = $currentweek;
  for ($y = $firstweek;$y<=$currentweek;$y++)
    if ($week_totaledits[$y] > 0) $lastweek = $y;
  $weekcount = 0;
  $o1 = ""; $o2 = ""; $o3 = ""; $o4 = ""; $o5 = ""; $o6 = ""; $o7 = "";
  for ($y = $firstweek;$y<=$lastweek;$y++)
  {
    $year = intval($y / 100);
    $week = $y % 100;

    $countWeeksForYear = date("W",strtotime($year . "-12-31"));
    if ($countWeeksForYear == 1) $countWeeksForYear = 52;
    
    if (($week < 1) || ($week > $countWeeksForYear)) continue;
    $o1 .= "'" . $week . "\\n" . str_pad(($year % 100),2,'0',STR_PAD_LEFT) . "',";
    $o2 .= intval($week_totaledits[$y]) . ",";
    $o3 .= intval($week_minoredits[$y]) . ",";
    $o4 .= intval($week_anonedits[$y]) . ",";
    $o5 .= intval($week_articlesize_max[$y]) . ",";
    $o6 .= (($week_totaledits[$y] == 0) ? 0 : intval($week_articlesize_sum[$y] / $week_totaledits[$y])) . ",";
    $o7 .= intval($week_articlesize_min[$y]) . ",";
    $weekcount++;
  }
  $o1 = substr($o1, 0, -1); $o2 = substr($o2, 0, -1); $o3 = substr($o3, 0, -1); $o4 = substr($o4, 0, -1); $o5 = substr($o5, 0, -1); $o6 = substr($o6, 0, -1); $o7 = substr($o7, 0, -1);
  print "  var names = [ $o1 ]; var totaledits = [ $o2 ]; var minoredits = [ $o3 ]; var anonedits = [ $o4 ]; var articlesize_max = [ $o5 ]; var articlesize_avg = [ $o6 ]; var articlesize_min = [ $o7 ];
  chart_time('week', true, true, true, names, totaledits, minoredits, anonedits, articlesize_max, articlesize_avg, articlesize_min );
  }\n";
  
  if ($weekcount < 48)
    print "chart_time_week();"; // start with month
  else if ($monthcount < 48)
    print "chart_time_month();"; // start with month
  else
    print "chart_time_year();"; // start with year

  // Unmask UI
  print "$('#main').unmask();updateui();\n";
  
  // Now load Authors
  print "$.getScript( 'authors_get.php?page_id=" . $page_id . "' );";

  // -------- Help Functions (Output) -----------------------------------------

  function get_output_for_date($date)
  {
    $diff = time() - $date;
    global $lang;
    
    if ($lang == "de")
    {
        if ($diff < 60)
          return "vor weniger als einer Minute";
        else if ($diff < 90)
          return "vor einer Minute";
        else if ($diff < 3600) // <1h
          return "vor " . intval(round($diff / 60)) . " Minuten";
        else if ($diff < 5400) // <1.5h
          return "vor einer Stunde";
        else if ($diff < 86400) // <24h
          return "vor " . intval(round($diff / 3600)) . " Stunden";
        else if ($diff < 172800) // <2d (2*86400)
          return "vor einem Tag";
        else if ($diff < 31536000) // <1y (365*86400)
          return "vor " . intval(round($diff / 86400)) . " Tagen";
        else if ($diff < 63072000) // <2y (2*365*86400)
          return "vor einem Jahr und " . intval(round(($diff - 31536000) / 86400)) . " Tagen";
        else
        {
          $years = intval($diff / 31536000);
          return "vor " . $years . " Jahren und " . intval(round(($diff - $years * 31536000) / 86400)) . " Tagen";
        }
    }
    else
    {
        if ($diff < 60)
          return "less than one minute ago";
        else if ($diff < 90)
          return "one minute ago";
        else if ($diff < 3600) // <1h
          return intval(round($diff / 60)) . " minutes ago";
        else if ($diff < 5400) // <1.5h
          return "one hour ago";
        else if ($diff < 86400) // <24h
          return intval(round($diff / 3600)) . " hours ago";
        else if ($diff < 172800) // <2d (2*86400)
          return "one day ago";
        else if ($diff < 31536000) // <1y (365*86400)
          return intval(round($diff / 86400)) . " days ago";
        else if ($diff < 63072000) // <2y (2*365*86400)
          return "one year and " . intval(round(($diff - 31536000) / 86400)) . " days ago";
        else
        {
          $years = intval($diff / 31536000);
          return $years . " years and " . intval(round(($diff - $years * 31536000) / 86400)) . " days ago";
        }    
    }
  }
  
  function day_format()
  {
    global $lang;
    if ($lang == "de") return "%d. %B %Y %H:%M";
    else return "%B %d %Y %H:%M";
  }
  
  function num_format($num, $decimals)
  {
    global $lang;
    if ($lang == "de")
      return number_format($num, $decimals, ',', '.');
    else
      return number_format($num, $decimals, '.', ',');
  }
  
  function user_link($user)
  {
    global $project;
    return "<a href=\'//$project/wiki/User:" . urlencode(str_replace(" ", "_", $user)) . "\'>" . $user . "</a>";
  }

  // -------- Help Functions (API) --------------------------------------------

  function load_revisions($baseurl, $page_id)
  {
    global $page_title;
    $rvcontinue = 0;
    $revisions = array();

    do
    {
      // get next 500
      $url = $baseurl . "api.php?action=query&rawcontinue=1&prop=revisions&pageids=" . urlencode($page_id) . "&rvlimit=500&format=php&rvprop=timestamp|flags|user|size|sha1";
      if ($rvcontinue != 0) $url .= "&rvcontinue=" . $rvcontinue;  
      $data = unserialize(load_api($url));
      
      // rvcontinue    
      if (isset($data['query-continue'])) $rvcontinue = $data['query-continue']['revisions']['rvcontinue']; else $rvcontinue = 0;
      
      $page_title = $data['query']['pages'][$page_id]['title'];
      
      // load the revisions into $revisions array
      $revs = $data['query']['pages'][$page_id]['revisions'];
      foreach($revs as $r) array_push($revisions, $r);
    } while ($rvcontinue != 0);
    
    return $revisions; 
  }
  
  function load_api($url)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WikiHistory');
    $result = curl_exec($ch);

    if (!$result) { exit('cURL Error: '.curl_error($ch)); }

    return $result;
  }

  // -------- Help Functions (DB) ---------------------------------------------
  
  function load_revisions_db($page_id)
  {
    global $page_title;
    global $wikidb;
    $revisions = array();
    
    $query = "SELECT page_title FROM page WHERE page_id='" . mysqli_escape_string($wikidb, $page_id) . "'";
    $result = mysqli_query($wikidb, $query);
    $row = mysqli_fetch_assoc($result);
    $page_title = str_replace("_", " ", $row['page_title']);

    $query = "SELECT rev_user,rev_user_text,rev_len,rev_sha1,rev_timestamp,rev_minor_edit FROM revision WHERE rev_page='" . mysql_escape_string($page_id) . "'";
    $result = mysqli_query($wikidb, $query);
    while ($row = mysqli_fetch_assoc($result))
    {
      $rev = array('user' => $row['rev_user_text'], 'size' => $row['rev_len'], 'sha1' => $row['rev_sha1']);
      $rev['timestamp'] = $row['rev_timestamp'];
      if ($row['rev_minor_edit'] == 1) $rev['minor'] = 1;
      if ($row['rev_user'] == 0) $rev['anon'] = 1;
      array_push($revisions, $rev);
    }

    return $revisions; 
  }

?>
