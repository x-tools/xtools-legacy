<?php

  include_once("/data/project/xtools/public_html/wikihistory/db.inc.php");
  $userdb = db_user_data();
  
  // check, if the file really exists on disk (sometimes they've got lost due to NFS failure)
  $query = "SELECT page_id, file FROM dewiki_data WHERE 1";
  $result = mysqli_query($userdb, $query);
  $c1 = 0; $c2 = 0;
  while ($row = mysqli_fetch_assoc($result))
  {
    $c2++;
    if (file_exists($row['file']) === false)
    {
      $c1++;
      //print $row['page_id'] . " / " . file_exists($row['file']) . "\n";
      $query = "UPDATE enwiki_data SET request_prio=2 WHERE page_id='" . mysqli_real_escape_string($userdb, $row['page_id']) . "'";
      mysqli_query($userdb, $query);
    }
  }
  print "$c1 / $c2 are affected\n";

?>
