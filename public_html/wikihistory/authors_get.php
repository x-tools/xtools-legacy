<?php

  if (!isset($_REQUEST['page_id'])) return;
  $page_id = intval($_REQUEST['page_id']);
  if ($page_id == 0) return;
  $onlynew = (isset($_REQUEST['onlynew'])) ? 1 : 0; 
  $requestnr = (isset($_REQUEST['c'])) ? intval($_REQUEST['c']) : 0;

  include_once("/data/project/xtools/public_html/wikihistory/db.inc.php");
  $wikidb = db_enwiki();
  $userdb = db_user_data();

  $query = "SELECT page_latest,page_len FROM page WHERE page_id='" . mysqli_escape_string($wikidb, $page_id) . "';";
  $result = mysqli_query($wikidb, $query);
  if (mysqli_num_rows($result) == 0) return;
  $row = mysqli_fetch_assoc($result);
  $page_latest = $row['page_latest'];
  $page_len = $row['page_len'];

  $query = "SELECT * FROM enwiki_data WHERE page_id='" . mysqli_escape_string($userdb, $page_id) . "';";
  $result = mysqli_query($userdb, $query);
  if (mysqli_num_rows($result) == 0) return;

  $row = mysqli_fetch_assoc($result);

  // result for current revision is known
  //if ($row['page_latest'] == $page_latest) { print "authors.stoploading();"; get_result($row['file'], 0); return; }
  
  if ($row['request_prio'] > 0)
  {
    if ($row['file'] == "") $row = wait_for_result($page_id, $requestnr);
    else
    {
      if ($onlynew) $row = wait_for_result($page_id, $requestnr);
      else 
      {
        get_result($row['file'], $page_id);
        print "$('#authors_old').show();";
        wait_for_result($page_id, $requestnr);
      }
    }
  }
  else
  {
    get_result($row['file'], $page_id);
  }

  mysqli_close($wikidb);
  mysqli_close($userdb);
  
  function wait_for_result($page_id, $requestnr)
  {
    $requestnr++;
    $time = min((($requestnr * $requestnr) + 2) * 1000, 60000);
    print "window.setTimeout(\"$.getScript( 'authors_get.php?page_id=" . $page_id . "&onlynew=1&c=" . $requestnr . "' )\", $time);";
  }

  function get_result($file, $page_id)
  {
    $xml = simplexml_load_file($file);
    $output = "var auth = [";
    foreach ($xml->children() as $node)
    {
      if (isset($node['percentage']))
      {  
        $username = $node['name'];
        $perc = doubleval($node['percentage']);
        $output .= "['" . addslashes($username) . "'," . number_format($perc, 1) . "],";
      }
    }
    if ($output != "") $output = substr($output, 0, -1);
    $output .= "];";
    
    print $output . "authors(auth);";
    print "$('#authors_loading').hide();";
    print "$('#authors_old').hide();";
  }

?>
