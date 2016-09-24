<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8" />
<title>WikiHistory</title>
<link rel="stylesheet" href="style.css" />
<link href='//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800&amp;subset=latin,cyrillic-ext,greek-ext,greek,vietnamese,latin-ext,cyrillic' rel='stylesheet' type='text/css'>
<link href='//fonts.googleapis.com/css?family=Oswald:700' rel='stylesheet' type='text/css'>
<!--[if IE]>
<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

</head>
<body>

<div id="main">

<div id="wh_header">
WikiHistory
</div>

<div id="article_header_container">
<div id="article_header">
<span id="article_title">Informationen</span>
</div>
</div>

<div id="content">
<h1 id="topheader"></h1>

<h1>Request-Prioritäten</h1>

<?php
  include_once("/data/project/xtools/public_html/wikihistory/db.inc.php");
  $userdb = db_user_data();
  
  for ($i=2;$i<4;$i++)
  {  
    $query = "SELECT count(*) AS c FROM enwiki_data WHERE request_prio='" . $i . "';";
    $result = mysqli_query($userdb, $query);
    $row = mysqli_fetch_assoc($result);
    print "<p>Request-Prio $i: <b>" . $row['c'] . "</b></p>";
  }
?>

<h1>Abarbeitungs-Queues</h1>

<?php

  function show_queue($nr, $where, $key)
  {
    global $userdb;
  
    $query = "SELECT count(*) AS c FROM enwiki_data " . (($key == "")?"":"USE KEY($key)") . " WHERE $where;";
    $result = mysqli_query($userdb, $query);
    $row = mysqli_fetch_assoc($result);
    print "<p>Queue $nr - Count: <b>" . $row['c'] . "</b> ($where)</p>\n";
  
    if ($row['c'] < 50)
    {
      print "<p class='info' style='margin-left:40px;'>";
      $query = "SELECT * FROM enwiki_data " . (($key == "")?"":"USE KEY($key)") . " WHERE $where ORDER BY request_timestamp ASC LIMIT 0,5";
      $result = mysqli_query($userdb, $query);
      while ($row = mysqli_fetch_assoc($result))
      {
        $id = $row['page_id'];
        $zeit = "Last done: " . $row['analysistime'];
        if ($row['file'] == "") $zeit = "Not yet analyzed";
        print "<a href='//en.wikipedia.org/w/index.php?curid=$id'>Page-ID $id</a> (Size: " . $row['page_len'] . "; " . $zeit . ")<br />";
      }
      print "</p>\n\n";
    }  
  }

  show_queue(1, "request_prio=1","request_prio");
  show_queue(2, "request_prio>1 AND page_len<10000","queue_data");
  show_queue(3, "request_prio>1 AND page_len>=10000 AND page_len<50000","queue_data");
  show_queue(4, "request_prio>1 AND page_len>=50000 AND page_len<150000","queue_data");
  show_queue(5, "request_prio>1 AND page_len>=150000","queue_data");

?>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<h1>General Information</h1>

<p class="info">
Created by Christian Thiele (<a href="//de.wikipedia.org/wiki/Benutzer:APPER">APPER</a>).
</p>

<p>&nbsp;</p>

</div>
</div>

</body>
</html>

