<?php

  include_once("/data/project/xtools/public_html/wikihistory/db.inc.php");
  $wikidb = db_dewiki(); 

  $r = wfRandom();
  $query = "SELECT page_title FROM page WHERE page_namespace=0 AND page_is_redirect=0 AND page_random>=" . mysqli_escape_string($wikidb, $r) . " ORDER BY page_random LIMIT 0,1;";
  $result = mysqli_query($wikidb, $query);
  $row = mysqli_fetch_assoc($result);
  $title = $row['page_title'];
  print $title;

// from mediawiki
function wfRandom() 
{
    # The maximum random value is "only" 2^31-1, so get two random
    # values to reduce the chance of dupes
    $max = mt_getrandmax();
    $rand = number_format( (mt_rand() * $max + mt_rand())
    / $max / $max, 12, '.', '' );
    return $rand;
}

?>
