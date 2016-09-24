<!doctype html>
<?php include_once("inc/i18n.inc.php"); ?>
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
<span id="article_title"><?=_("Article Statistics")?></span>
</div>
</div>

<div id="content">
<h1 id="topheader"></h1>

<h1><?=_("Search")?></h1>

<form action='wh.php'>
<p><?=_("Please choose article:")?></p>
<div>
<input type="text" name="page_title" style="width:942px;font-size:28pt;font-family:Open Sans,sans-serif;" />
<input type="submit" value="<?=_("Go!")?>" style="width:100%; font-size:16pt; font-family:Open Sans,sans-serif; padding:10px; background-color:#515151; color:#FFFFFF; border:0; margin-top:15px;" />
</div>
<p><?=_("Please enter the article title. Only works for articles in the article namespace and only for the english wikipedia.")?></p> 

</form>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<h1><?=_("General Information")?></h1>

<p class="info">
<?=_("Further Information about this Tool and a help page will follow.")?>
</p>
<p class="info">
<?=_("Made by Christian Thiele")?> (<a href="//de.wikipedia.org/wiki/Benutzer:APPER">APPER</a>).
</p>

<p>&nbsp;</p>

</div>
</div>

</body>
</html>

