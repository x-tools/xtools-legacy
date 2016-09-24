<?

  include_once("/data/project/xtools/public_html/wikihistory/db.inc.php");

  function get_wikidb($wiki)
  {
    if ($wiki == "alswiki") return db_wiki("alswiki");
    if ($wiki == "enwiki") return db_enwiki();
    if ($wiki == "ndswiki") return db_wiki("ndswiki");
    die("wrong wiki (get_wikidb)");
  }

  // not in use for smallwiki
  function get_table($wiki)
  {
    if ($wiki == "enwiki") return "enwiki_data";
    die("wrong wiki (get_table)");
  }
    
  function get_userlink($wiki, $username)
  {
    $linkname = urlencode(str_replace(" ", "_", $username));
    if ($wiki == "alswiki") return "<a href='//als.wikipedia.org/wiki/Benutzer:" . $linkname . "'>" . $username . "</a>";
    if ($wiki == "enwiki") return "<a href='//en.wikipedia.org/wiki/User:" . $linkname . "'>" . $username . "</a>";
    if ($wiki == "ndswiki") return "<a href='//nds.wikipedia.org/wiki/Bruker:" . $linkname . "'>" . $username . "</a>";
    return "";
  }
  
  function get_project($wiki)
  {
    if ($wiki == "alswiki") return "als.wikipedia";
    if ($wiki == "enwiki") return "en.wikipedia";
    if ($wiki == "ndswiki") return "nds.wikipedia";
    die("wrong wiki (get_project)");
  }

?>
