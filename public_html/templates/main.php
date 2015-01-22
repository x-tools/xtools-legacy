<!DOCTYPE html>
<html>
<head>
	<title>X!'s tools</title>
	<link rel="stylesheet" type="text/css" href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/static/style.css" />
	<script type="text/javascript" src="//<?php echo XTOOLS_BASE_WEB_DIR ?>/static/sortable.js"></script>
	<?php echo $wt->moreheader ?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<script type="text/javascript">
		function switchShow( id, elmnt ) {
			if(document.getElementById(id).style.display == "none") {
				document.getElementById(id).style.display = "block";
				elmnt.innerText = '[<?php echo $I18N->msg('hide') ?>]';
			}
			else{
				document.getElementById(id).style.display = "none";
				elmnt.innerText = '[<?php echo $I18N->msg('show') ?>]';
			}
		}
	</script>
</head>

<body>
<div id="wrap">

	<div id="header">
		<span>X!'s tools</span> <span class="statuslink" ><?php echo $wt->statusLink?></span>
	</div>
	
	<div id="content">
		<div id="navigation" class="center container">
			<a href="//tools.wmflabs.org/supercount/">User Analysis Tool</a> &middot; 
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/ec/">Edit counter<sup style="color:green; font-size:70%; position:relative;left:-27px; top:-5px; margin-right:-30px">classic</sup></a> &middot; 
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/articleinfo/">Page history</a> &middot; 
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/pages/">Pages created</a> &middot;
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/topedits/">Top edits</a> &middot; 
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/autoedits/">Automated edits</a> &middot; 
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/blame/">Article blamer</a> &middot; 
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/rangecontribs/">Range contribs</a> &middot; 
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/autoblock/">Autoblock</a> &middot; 
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/rfa/">RfX</a> &middot; 
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/rfap/">RfX Vote</a> &middot; 
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/bash/">RQ</a> &middot;
			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/sc">SC</a> &middot;
		</div>

		<div id="alerts">
			<?php echo ($wt->sitenotice) ? "<h4 class='notice'> $wt->sitenotice </h4>" : "" ?>
			<h2 style="margin-bottom: 0.4em"><?php echo $wt->toolTitle ?><span style="font-size: 75%;font-weight:normal; "> &bull; <?php echo $wt->toolDesc ?></span></h2>
			<?php echo ($wt->alert) ? "<h3 class='alert'> $wt->alert </h3>" : "" ?>
			<?php echo ($wt->error) ? "<div class='alert'> $wt->error </div>" : "" ?>
			<?php echo ($wt->replag) ? "<div class='alert'> $wt->replag </div>" : "" ?>
		</div>
		
		<div id="contentmain">
			<?php echo $wt->content ?>
		</div>
		
		<br />
		<span><small><span><?php echo $wt->executed ?></span> &middot; <span><?php echo $wt->memused ?></span></small></span>
	</div>

	<div id="footer">
		<hr style="margin-top:0px;"/>
		<div style="float:right; display:inline-block">
			<span >
				<a style="margin-right:5px;" href="//translatewiki.net/?setlang=<?php echo $wt->uselang ?> "><img height="36px" src="//upload.wikimedia.org/wikipedia/commons/5/51/Translatewiki.net_logo.svg" alt="translatewiki.net logo"/></a>
				<a href="//tools.wmflabs.org"><img height="40px" src="//tools.wmflabs.org/xtools/static/images/labs.png" alt="Powered by WMF Labs" /></a>
			</span>
		</div>	
		<div style="float:left; display:inline-block; line-height:1.5em;">
			<span>&copy;2014 
				<a href="//en.wikipedia.org/wiki/User:Cyberpower678"><b>Cyberpower678</b></a> &middot;
                <a href="//de.wikipedia.org/wiki/User:Hedonil">Hedonil</a> &middot;
                <a href="//en.wikipedia.org/wiki/User:MusikAnimal"><b>MusikAnimal</b></a> &middot;
                <a href="//en.wikipedia.org/wiki/User:Technical 13"><b>Technical 13</b></a> &middot;
                <a href="//en.wikipedia.org/wiki/User:YuviPanda"><b>YuviPanda</b></a> &middot;
                <a href="//en.wikipedia.org/wiki/User:TParis">TParis</a> &middot;
                <a href="//en.wikipedia.org/wiki/User:X!">X!</a> &bull;  
				<?php echo $wt->sourcecode ?>
				<?php echo $wt->bugreport ?>
				<a href="irc://irc.freenode.net/#wikimedia-labs" >#wikimedia-labs</a>
				<sup><a  style="color:green" href="https://webchat.freenode.net/?channels=#wikimedia-labs">WebChat</a></sup>
			</span>
			<br />
			<span><?php echo $wt->langLinks ?></span>
		</div>
	</div>
</div>

<script type="text/javascript">
	if (window.sortables_init) sortables_init();
</script>

</body>
</html>
