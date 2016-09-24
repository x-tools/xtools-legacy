<?php include("inc/i18n.inc.php"); ?>
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
<div id="translations"><?php show_flags();?></div>
WikiHistory
</div>

<div id="article_header_container">
<div id="article_header">
<span id="article_title"><?=t(100);?></span>
</div>
</div>

<div id="content">
<h1 id="topheader"></h1>

<h1><?=t(101);?></h1>

<form action='wh.php'>
<p><?=t(102);?></p>
<div>
<input type="text" name="page_title" style="width:100%;font-size:28pt;font-family:Open Sans,sans-serif;" />
<input type="submit" value="<?=t(107);?>" style="width:100%; font-size:16pt; font-family:Open Sans,sans-serif; padding:10px; background-color:#515151; color:#FFFFFF; border:0; margin-top:15px;" />
</div>
<p><?=t(103);?></p> 

</form>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<h1><?=t(104);?></h1>

<p class="info">
<?=t(105);?>
</p>
<p class="info">
<?=t(106);?>
</p>

<p>&nbsp;</p>

</div>
</div>

</body>
</html>

