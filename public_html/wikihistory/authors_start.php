<?php

  if (!isset($_REQUEST['page_id'])) return;
  $page_id = intval($_REQUEST['page_id']);
  if ($page_id == 0) return;

  include_once("db.inc.php");
  $wikidb = db_enwiki();
  $userdb = db_user_data();

  $query = "SELECT page_latest, page_len FROM page WHERE page_id='" . mysqli_escape_string($wikidb, $page_id) . "';";
  $result = mysqli_query($wikidb, $query);
  if (mysqli_num_rows($result) == 0) return;
  $row = mysqli_fetch_assoc($result);
  $page_latest = $row['page_latest'];
  $page_len = $row['page_len'];

  $query = "SELECT * FROM enwiki_data WHERE page_id='" . mysqli_escape_string($userdb, $page_id) . "';";
  $result = mysqli_query($userdb, $query);
  if (mysqli_num_rows($result) == 0)
  {
    change_status($page_id, 3, $page_len);
  }
  else
  {
    $row = mysqli_fetch_assoc($result);

    // result for current revision is known
    if ($row['page_latest'] == $page_latest) { return; }
    
    // old result is known
    if ($row['file'] != "")
    {
      if ($row['request_prio'] < 2) change_status($page_id, 2, $page_len);
    }
    else
    { 
      if ($row['request_prio'] < 3) change_status($page_id, 3, $page_len);
    }    
  }
  
  function change_status($page_id, $prio, $page_len)
  {
    global $userdb;
    $query = "INSERT INTO enwiki_data (page_id, request_prio, request_timestamp, page_len) VALUES ('" . mysqli_escape_string($userdb, $page_id) ."', '" . mysqli_escape_string($userdb, $prio) . "','" . time() . "','" . mysqli_escape_string($userdb, $page_len) . "')
              ON DUPLICATE KEY UPDATE page_id=VALUES(page_id), request_prio=VALUES(request_prio), request_timestamp=VALUES(request_timestamp), page_len=VALUES(page_len);";
    $result = mysqli_query($userdb, $query);
  }

?>
