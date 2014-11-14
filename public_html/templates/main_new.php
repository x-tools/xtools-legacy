<!DOCTYPE html>
<html>
<head>
	<title>X!'s tools</title>
	
	<link rel="stylesheet" type="text/css" href="//tools.wmflabs.org/static/res/bootstrap/3.1.1/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/static/css/stylenew.css" />
	<script type="text/javascript" src="//<?php echo XTOOLS_BASE_WEB_DIR ?>/static/sortable.js"></script>
	
	<!--
	    <link rel="stylesheet" type="text/css" href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/static/css/bootstrap.min.css" /> 
	    <link rel="stylesheet" type="text/css" href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/static/css/bootstrap-theme.min.css" />
	--> 
	
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
	<script type="text/javascript">
		function switchShow( id, elmnt ) {
			var ff = document.getElementById(id);
			if( ff.style.display == "none" || ff.style.display == undefined ) {
				ff.style.display = "block";
				if(elmnt && id != 'xt-notifications') elmnt.innerHTML = '[<?php echo $I18N->msg('hide') ?>]';
			}
			else{
				ff.style.display = "none";
				if(elmnt && id != 'xt-notifications') elmnt.innerHTML = '[<?php echo $I18N->msg('show') ?>]';
			}
		}
	</script>
	
	<?php echo $wt->moreheader ?>
</head>

<body>

	<div class="navbar navbar-default navbar-top" role="navigation" style="min-height:40px;">
		<div class="container-fluid">
			<div class="navbar-collapse collapse" style="padding-top:5px;">
				<?php echo $wt->statusLink ?>
			</div>
			<div class="navbar-collapse collapse">
				<ul class="nav navbar-nav navbar-left">
					<li class="<?php echo $wt->active["ec"]?>" >			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/ec/" title="Edit Counter"><?php echo $I18N->msg('tool_ec') ?></a></li> 
					<li class="<?php echo $wt->active["articleinfo"]?>" >	<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/articleinfo/" title="Page history" ><?php echo $I18N->msg('tool_articleinfo') ?></a></li> 
					<li class="<?php echo $wt->active["pages"]?>" >			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/pages/" title="Pages created" ><?php echo $I18N->msg('tool_pages') ?></a></li>
					<li class="<?php echo $wt->active["topedits"]?>" >		<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/topedits/" title="Top edits" ><?php echo $I18N->msg('tool_topedits') ?></a></li>
					<li class="<?php echo $wt->active["rangecontribs"]?>" >	<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/rangecontribs/" title="Range contribs"><?php echo $I18N->msg('tool_rangecontribs') ?></a></li>
					<li class="<?php echo $wt->active["blame"]?>" >			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/blame/" title="Article blamer" ><?php echo $I18N->msg('tool_blame') ?></a></li>
					
				<!-- <li class="<?php echo $wt->active["autoedits"]?>" >		<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/autoedits/" title="Automated edits" ><?php echo $I18N->msg('tool_autoedits') ?></a></li>  --> 
					<li class="<?php echo $wt->active["autoblock"]?>" >		<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/autoblock/" title="Autoblock" ><?php echo $I18N->msg('tool_autoblock') ?></a></li>
					<li class="<?php echo $wt->active["adminstats"]?>" >	<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/adminstats/" title="AdminStats" ><?php echo $I18N->msg('tool_adminstats') ?></a></li>
					<li class="<?php echo $wt->active["rfa"]?>" >			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/rfa/" title="RfX" ><?php echo $I18N->msg('tool_rfa') ?></a></li>
					<li class="<?php echo $wt->active["rfap"]?>" >			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/rfap/" title="RfX Vote"><?php echo $I18N->msg('tool_rfap') ?></a></li> 
				<!-- <li class="<?php echo $wt->active["bash"]?>" >			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/bash/">RQ</a></li>  -->
					<li class="<?php echo $wt->active["sc"]?>" >			<a href="//<?php echo XTOOLS_BASE_WEB_DIR ?>/sc" title="SC" ><?php echo $I18N->msg('tool_sc') ?></a></li>
					
				</ul>
			</div>
		</div>
	</div>
	<div class="container-fluid text-center" style="margin-top: -10px;margin-bottom:10px;">
		<?php echo $wt->langLinks ?>
	</div>
	
	<div class="container-fluid" style="margin-top: -10px;margin-bottom:10px;">
		<?php echo ($wt->sitenotice) ? "<p class='alert alert-info xt-alert'> $wt->sitenotice </p>" : "" ?>
		<?php echo ($wt->toolnotice) ? "<p class='alert alert-warning xt-alert'> $wt->toolnotice </p>" : "" ?>
		<?php echo ($wt->alert) ? "<p class='alert alert-warning xt-alert'> $wt->alert </p>" : "" ?>
		<?php echo ($wt->error) ? "<p class='alert alert-danger xt-alert'> $wt->error </p>" : "" ?>
		<?php echo ($wt->replag) ? "<p class='alert alert-danger xt-alert'> $wt->replag </p>" : "" ?>
	</div>	
	
	<div class="container-fluid" id="content">
		<?php echo $wt->content ?>
		
		<div class="container-fluid">
			<span><small><span><?php echo $wt->executed ?></span> &middot; <span><?php echo $wt->memused ?></span></small></span>
			<hr style="margin:5px 10px;" />
			<div class="row">
				<div class="col">
					<span>&copy; 2008-2014 &middot; </span>
					<a href="//de.wikipedia.org/wiki/User:Hedonil">Hedonil</a> &middot;
					<a href="//en.wikipedia.org/wiki/User:Cyberpower678">Cyberpower678</a> &middot;
					<a href="//en.wikipedia.org/wiki/User:TParis">TParis</a> &middot;
					<a href="//en.wikipedia.org/wiki/User:X!">X!</a> &bull;
					<?php echo $wt->sourcecode ?>
					<?php echo $wt->bugreport ?>
					<a href="irc://irc.freenode.net/#wikimedia-labs" >#wikimedia-labs</a>
					<span><sup><a  style="color:green" href="https://webchat.freenode.net/?channels=#wikimedia-labs">WebChat</a></sup></span><br />
					<span><?php echo $wt->langPromoLinks ?></span>
				</div>
				<div class="col pull-right">
					<a style="margin-right:5px;" href="//translatewiki.net/?setlang=<?php echo $wt->uselang ?> "><img height="36px" src="//upload.wikimedia.org/wikipedia/commons/5/51/Translatewiki.net_logo.svg" alt="translatewiki.net logo"/></a>
					<a href="//tools.wmflabs.org"><img height="40px" src="//tools.wmflabs.org/xtools/static/images/labs.png" alt="Powered by WMF Labs" /></a>
				</div>
			</div>
		</div>
	</div>
	<br />
	<br />
	
<script>
	if (window.sortables_init) sortables_init();
</script>
<script> 
	<?php echo $wt->moreScript ?>
</script>

</body>
</html>
