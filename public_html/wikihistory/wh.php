<?php
  // Vorbereitungen
  include("inc/i18n.inc.php");
  
  include_once("db.inc.php");
  $page_id = 0;
  if (isset($_REQUEST['page_title']))
  {
    $wikidb = db_enwiki(); 
    $query = "SELECT page_id FROM page WHERE page_namespace=0 AND page_title='" . mysqli_escape_string($wikidb, str_replace(" ", "_", $_REQUEST['page_title'])) . "';";
    $result = mysqli_query($wikidb, $query);
    $row = mysqli_fetch_assoc($result);
    $page_id = $row['page_id'];
    /*if ($page_id < 1) {
        $name = explode( ":", str_replace(" ", "_", $_REQUEST['page_title']), 2 );
        $name = $name[1];
        $query = "SELECT page_id FROM page WHERE page_title='" . mysqli_escape_string($wikidb, $name) . "';";
        $result = mysqli_query($wikidb, $query);
        $row = mysqli_fetch_assoc($result);
        $page_id = $row['page_id'];    
    }     */ //This will take more doing.
       
    mysqli_close($wikidb);
  }
  
  if ($page_id < 1)
  {
    if (!isset($_REQUEST['page_id'])) { print "Error. Unknown article."; return; }
    $page_id = $_REQUEST['page_id'];
  }
  
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>WikiHistory</title>
<link rel="stylesheet" href="style.css" />
<link href='//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800&amp;subset=latin,cyrillic-ext,greek-ext,greek,vietnamese,latin-ext,cyrillic' rel='stylesheet' type='text/css'>
<link href='//fonts.googleapis.com/css?family=Oswald:700' rel='stylesheet' type='text/css'>
<!--[if IE]>
<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

<script src="js/jquery/jquery-2.1.0.min.js"></script>
<script src="js/jquery/jquery.loadmask.js"></script>  <link href="js/jquery/jquery.loadmask.css" rel="stylesheet" type="text/css" />
<script src="js/jquery/jquery.tablesorter.min.js"></script>
<script src="js/jquery/jcanvas.min.js"></script>
<script src="js/flot/jquery.flot.js"></script>
<script src="js/flot/jquery.flot.pie.js"></script>
<script src="js/scrolling.js"></script>
<script src="js/usertable.js"></script>
<script src="js/chart_edits.js"></script>
<script src="js/chart_time.js"></script>
<script src="js/authors.js"></script>
<script>
$( document ).ready(function() {
  $('#main').mask('<?=t(200);?>');
  $.getScript( "authors_start.php?page_id=<?=$page_id?>" );
  $.getScript( "load_history.php?page_id=<?=$page_id?>" );
});
</script>
</head>
<body>

<div id="main">

<div id="wh_header">
<div id="translations"><?=show_flags();?></div>
WikiHistory
</div>

<div id="article_header_container">
<div id="article_header">
<?=t(201);?><br />
<span id="article_title">&nbsp;</span>
</div>
</div>

<div id="content">
<h1 id="topheader"></h1>

<h1><?=t(202);?></h1>

<div id="chart_edits" class="chart" style="float:right; margin:30px; width:300px; height:300px;"></div>

<div>
<table>
<tr>
  <td style="width:250px;"><?=t(300);?></td>
  <td style='text-align:right'><b id="editstotal"></b></td>
  <td></td>
</tr>
<tr>
  <td><?=t(301);?></td>
  <td style='text-align:right' id="editsminor"></td>
  <td id="editsminorpercent"></td>
</tr>
<tr class="spaceUnder">
  <td><?=t(302);?></td>
  <td style='text-align:right' id="editsanon"></td>
  <td id="editsanonpercent"></td>
</tr>

<tr>
  <td><?=t(303);?></td>
  <td style='text-align:right' id="userstotal"></td>
  <td></td>
</tr>
<tr>
  <td><?=t(304);?></td>
  <td style='text-align:right' id="usersanon"></td>
  <td id="usersanonpercent"></td>
</tr>
<tr>
  <td><?=t(305);?></td>
  <td style='text-align:right' id="editsperuser"></td>
  <td></td>
</tr>
</table>
</div>

<div>
<table>
<tr>
  <td style="width:250px;"><?=t(310);?></td>
  <td id="firstedit"></td>
  <td></td>
</tr>
<tr>
  <td><?=t(311);?></td>
  <td id="lastedit"></td>
  <td></td>
</tr>
</table>
</div>

<div>
<table>
<tr>
  <td style="width:250px;"><?=t(320);?> <?=t(400);?></td>
  <td style='text-align:right' id="editsperday"></td>
</tr>
<tr>
  <td><?=t(320);?> <?=t(402);?></td>
  <td style='text-align:right' id="editspermonth"></td>
</tr>
<tr>
  <td><?=t(320);?> <?=t(403);?></td>
  <td style='text-align:right' id="editsperyear"></td>
</tr>
</table>
</div>

<div>
<table>
<tr>
  <td style="width:250px;"><?=t(330);?></td>
  <td style='text-align:right' id="articlesize"></td>
</tr>
</table>
</div>

<h1><?=t(203);?></h1>

<div>
<a id="button_year" href="#" onclick="chart_time_year(); return false;" class="button button_selected"><?=t(403);?></a>
<a id="button_month" href="#" onclick="chart_time_month(); return false;" class="button"><?=t(402);?></a>
<a id="button_week" href="#" onclick="chart_time_week(); return false;" class="button"><?=t(401);?></a>
</div>

<div id="chart_time" style="clear:both; padding-top:20px; padding-bottom:20px; height:300px;"></div>

<h1><?=t(410);?></h1>

<p id="authors_loading">Attention: The data for authorship is loading. Depending on the data
and the size of the article, this may take a while.
The page does not need to be reloaded.  The data will automatically appear.</p>

<p id="authors_old" style="display:none;">Attention: The data for authorship is from older article revision.
A refresh is recommended but may take time depending on the size of the article.
The data will automatically be updated as soon as they are available.</p>

<div id="chart_authors" class="chart" style="width:700px; height:300px; display:none;"></div>

<div>
<table id="usertable" class="tablesorter">
<thead>
<tr>
  <th><?=t(411);?></th>
  <th><?=t(412);?></th>
  <th><?=t(413);?></th>
  <th><?=t(413);?> (%)</th>
  <th><?=t(310);?></th>
  <th><?=t(311);?></th>
  <th><?=t(414);?></th>
</tr>
</thead>
<tbody>
</tbody>
</table>
</div>

<h1><?=t(104);?></h1>

<p class="info">
The determination of authorship is done through several methods. The shown methods
are based off of the program WikiHistory. To report a problem, you may leave a message <a href="//de.wikipedia.org/wiki/Benutzer:APPER/WikiHistory/Autorenbestimmung">here</a>.
</p>
<p class="info">
All times are in UTC.
</p>

<p>
&nbsp;
</p>

</div>
</div>

</body>
</html>
