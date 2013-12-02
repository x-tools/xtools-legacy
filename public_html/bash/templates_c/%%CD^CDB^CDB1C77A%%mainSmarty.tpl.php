<?php /* Smarty version 2.6.18, created on 2013-08-30 12:33:14
         compiled from ../../templates/mainSmarty.tpl */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "//www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="//www.w3.org/1999/xhtml" xml:lang="en" lang="en" >
<head>
	<title><?php if ($this->_tpl_vars['page'] != ""): ?><?php echo $this->_tpl_vars['page']; ?>
 -<?php endif; ?> <?php echo $this->_config[0]['vars']['tool']; ?>
 - <?php echo $this->_config[0]['vars']['title']; ?>
</title>
	<link rel="stylesheet" type="text/css" href="//tools.wmflabs.org/xtools/style.css" />
	<?php if ($this->_tpl_vars['moreheader'] != ""): ?><?php echo $this->_tpl_vars['moreheader']; ?>
<?php endif; ?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
<div id="wrap">
<div id="header">
<?php if ($this->_tpl_vars['page'] != ""): ?><?php echo $this->_tpl_vars['page']; ?>
 -<?php endif; ?> <?php echo $this->_config[0]['vars']['tool']; ?>
 - <?php echo $this->_config[0]['vars']['title']; ?>

</div>
<div id="content">
<div id="navigation" class="center container">
<!-- <a href="//tools.wmflabs.org/xtools/">Home</a> &middot; <a href="//tools.wmflabs.org/xtools/ec">Edit counter</a> &middot; <a href="//tools.wmflabs.org/xtools/bash">Random Quotes</a> &middot; <a href="//tools.wmflabs.org/xtools/editsummary">Edit summary counter</a> &middot; <a href="//tools.wmflabs.org/xtools/isAdmin">Is X an admin?</a> &middot; <a href="//tools.wmflabs.org/xtools/topedits">Top namespace edits</a> &middot; <a href="//tools.wmflabs.org/xtools/pages">Pages created</a> &middot; <a href="//tools.wmflabs.org/xtools/rfap">RfA Votes</a> &middot; <a href="//tools.wmflabs.org/xtools/autoedits">Automated edits</a> &middot; <a href="//stable.toolserver.org/nubio">Nubio<sup>2</sup></a> &middot; <a href="//wiki.toolserver.org/view/User:X!/API">Toolserver API</a> &middot; <a href="//tools.wmflabs.org/xtools/rangecontribs">CIDR range contribs</a> <b>(NEW)</b> &middot; <a href="//tools.wmflabs.org/xtools/sc">Quick edit counter</a> <b>(NEW)</b> -->
<a href="//tools.wmflabs.org/xtools/">Home</a> &middot; <a href="//tools.wmflabs.org/xtools/ec">Edit counter</a> &middot; <a href="//tools.wmflabs.org/xtools/articleinfo/">Page History Statistics</a> &middot; <a href="//tools.wmflabs.org/xtools/blame">Article blamer</a> &middot; <a href="//tools.wmflabs.org/xtools/rangecontribs">CIDR</a> &middot; <a href="//tools.wmflabs.org/xtools/ipcalc">IP calculator</a> &middot; <a href="//wiki.toolserver.org/view/User:TParis/Index">Index</a>
</div>
<?php if ($this->_tpl_vars['alert'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['alert']; ?>
</h2><?php endif; ?>
<h2><?php echo $this->_config[0]['vars']['header']; ?>
</h2>
<?php if ($this->_tpl_vars['error'] != ""): ?><?php echo $this->_tpl_vars['error']; ?>
<?php endif; ?>
<?php echo $this->_tpl_vars['content']; ?>

<?php if ($this->_tpl_vars['executedtime'] != ""): ?>
<br />
<hr />
<span style="font-size:100%;">
<?php echo $this->_tpl_vars['executedtime']; ?>

</span><br />
<?php endif; ?>
</div>
<div id="footer">
<span style="float:right;">
<a href="//validator.w3.org/check?uri=referer"><img src="//tools.wmflabs.org/xtools/images/xhtml.png" alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a>
<a href="//anybrowser.org/campaign"><img src="//tools.wmflabs.org/xtools/images/anybrowser.png" alt="AnyBrowser compliant" /></a>
<a href="//tools.wmflabs.org"><img src="//tools.wmflabs.org/xtools/images/labs.png" alt="Powered by WMF Labs" /></a>
</span>
<span style="float:right;padding-right:10px">&copy;2013 <a href="//en.wikipedia.org/wiki/User:X!">X!</a>
<?php if ($this->_tpl_vars['source'] != ""): ?> | <a href="//tools.wmflabs.org/xtools/highlight/?q=<?php echo $this->_tpl_vars['source']; ?>
"><?php echo $this->_config[0]['vars']['source']; ?>
</a><?php endif; ?>
<?php if ($this->_tpl_vars['souRce2'] != ""): ?> | <a href="<?php echo $this->_tpl_vars['source2']; ?>
"><?php echo $this->_config[0]['vars']['source']; ?>
</a><?php endif; ?> | <a href="https://webchat.freenode.net/?channels=#xlabs"><?php echo $this->_config[0]['vars']['bugs']; ?>
</a></span>
<a name="footer" />
<?php if ($this->_tpl_vars['curlang'] != ""): ?><span id="footertext"><?php echo $this->_config[0]['vars']['language']; ?>
<?php endif; ?><?php if ($this->_tpl_vars['translate'] != ""): ?> (<a href="<?php echo $this->_tpl_vars['translate']; ?>
"><?php echo $this->_config[0]['vars']['translatelink']; ?>
</a>)<?php endif; ?><?php if ($this->_tpl_vars['curlang'] != ""): ?>: <?php echo $this->_tpl_vars['curlang']; ?>
 | <span width="30%"><?php echo $this->_tpl_vars['langlinks']; ?>
</span></span><?php endif; ?>
</div>
</div>

<script type="text/javascript">if (window.runOnloadHook) runOnloadHook();</script>

</body>

</html>