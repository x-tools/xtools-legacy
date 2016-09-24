<?php

  // to add a new smallwiki:
  // - edit wikis.inc.php (get_wikidb, get_userlink, get_project)
  // - create folder /data/[wiki]/
  // - maybe WikiHistory.exe needs an update
  // restart queues

  include_once("/data/project/xtools/public_html/wikihistory/wikis.inc.php");

  setlocale(LC_ALL, 'en_EN.UTF8');

  if (!isset($_REQUEST['page_id'])) return;
  if (!isset($_REQUEST['wiki'])) return;
  $page_id = intval($_REQUEST['page_id']);
  if ($page_id == 0) return;
  $onlynew = (isset($_REQUEST['onlynew'])) ? 1 : 0;
  $wiki = $_REQUEST['wiki']; // checked later using get_wikidb()

  include_once("/data/project/xtools/public_html/wikihistory/db.inc.php");
  $wikidb = get_wikidb($wiki);
  $userdb = db_user_data();
  $table = "smallwiki_data";

  $query = "SELECT page_title, page_latest, page_len FROM page WHERE page_id='" . mysqli_escape_string($wikidb, $page_id) . "' AND page_namespace=0;";
  $result = mysqli_query($wikidb, $query);
  if (mysqli_num_rows($result) == 0) return;
  $row = mysqli_fetch_assoc($result);
  $page_latest = $row['page_latest'];
  $page_len = $row['page_len'];
  $page_title = $row['page_title'];

  $query = "SELECT * FROM $table WHERE wiki='" . mysqli_escape_string($userdb, $wiki) . "' AND page_id='" . mysqli_escape_string($userdb, $page_id) . "';";
  $result = mysqli_query($userdb, $query);
  if (mysqli_num_rows($result) == 0)
  {
    change_status($page_id, 3, $page_len);
    wait_for_result($page_id);
  }
  else
  {
    $row = mysqli_fetch_assoc($result);

    // result for current revision is known
    if ($row['page_latest'] == $page_latest)
    { 
      print "authors.stoploading();"; 
      get_result($row, 0); 
    }
    // old result is known
    else if ($row['file'] != "")
    {
      if ($onlynew == 0) get_result($row, 1);
      
      if ($row['request_prio'] < 2) change_status($page_id, 2, $page_len);
      
      if ($onlynew == 1) wait_for_result($page_id);
    }
    else
    { 
      if ($row['request_prio'] < 3) change_status($page_id, 3, $page_len);
      wait_for_result($page_id); 
    }    
  }

  mysqli_close($wikidb);
  mysqli_close($userdb);
  
  function change_status($page_id, $prio, $page_len)
  {
    global $userdb, $table, $wiki;
    $query = "INSERT INTO $table (wiki, page_id, request_prio, request_timestamp, page_len) VALUES ('" . mysqli_escape_string($userdb, $wiki) ."', '" . mysqli_escape_string($userdb, $page_id) ."', '" . mysqli_escape_string($userdb, $prio) . "','" . time() . "','" . mysqli_escape_string($userdb, $page_len) . "')
              ON DUPLICATE KEY UPDATE wiki=VALUES(wiki), page_id=VALUES(page_id), request_prio=VALUES(request_prio), request_timestamp=VALUES(request_timestamp), page_len=VALUES(page_len);";
    $result = mysqli_query($userdb, $query);
  }

  function wait_for_result($page_id)
  {
    global $wiki;
    // immer noch nicht da... frueher wurde hier gewartet, aber zu lange Verbindungen sind bloed, wenn man nur 5 gleichzeitig offen haben darf...
    // daher: mittels setinterval wird browser angewiesen, in 2 sekunden nochmal nachzufragen
    print "window.setTimeout(\"importScriptURI('//tools.wmflabs.org" . $_SERVER['PHP_SELF'] . "?page_id=$page_id&wiki=$wiki&onlynew=1&x=' + (new Date()).getTime())\", 2000);";
  }

  function get_result($dbrow, $old)
  {
    global $page_title, $wikidb, $wiki;
  
    $xml = simplexml_load_file($dbrow['file']);
    $persons_others = 0; $persons_othersperc = 0; $persons_output = 0; $persons_last = "";
    $output = "";
    foreach ($xml->children() as $node)
    {
      if (isset($node['percentage']))
      {  
        $username = $node['name'];
        $perc = doubleval($node['percentage']);
        if ((($persons_output < 5) || ($perc >= 5)) && ($perc >= 0.5))
        {
          $output .= get_userlink($wiki, $username) . " (" . number_format($perc, 0) . "&#x202f;%), ";
          $persons_output++;
        }
        else
        {
          $persons_othersperc += $perc;
          $persons_others++;
          $persons_last = $username;
        }
      }
    }
    if ($output != "") $output = substr($output, 0, strlen($output) - 2);
    if ($persons_others > 1) $output .= ", " . $persons_others . " other authors (" . number_format($persons_othersperc, 0) . "&#x202f;%)";
    else if ($persons_others == 1) $output .= ", " . get_userlink($wiki, $persons_last) . " (" . number_format($persons_othersperc, 0) . "&#x202f;%)";
    if ($old > 0)
    {
      preg_match('@([0-9]+)\.wha$@i', $dbrow['file'], $res);
      $oldid = $res[1];
      
      $query = "SELECT rev_timestamp FROM revision WHERE rev_id='" . mysqli_escape_string($wikidb, $oldid) . "';";
      $result = mysqli_query($wikidb, $query);
      $row = mysqli_fetch_assoc($result);
      $time = $row['rev_timestamp'];
      $time = mktime(0, 0, 0, substr($time, 4, 2), substr($time, 6, 2), substr($time, 0, 4));
      $time = strftime("%e. %B %Y", $time);
      $query = "SELECT count(*) AS c FROM revision WHERE rev_page='" . mysqli_escape_string($wikidb, $dbrow['page_id']) . "' AND rev_id>'" . mysqli_escape_string($wikidb, $oldid) . "';";
      $result = mysqli_query($wikidb, $query);
      $row = mysqli_fetch_assoc($result);
      $count = $row['c'];
      $output .= " (<span title='Version $oldid from $time ($count newer versions" . (($count > 1) ? "en" : "") . ")'>Data for older versions</span>) ";
    }
    
    print "authors.resultloaded(\"" . addslashes($output) . "\", $old);";
  }

?>
