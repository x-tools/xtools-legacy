<?php /* Smarty version 2.6.18, created on 2012-02-29 03:27:35
         compiled from ../../templates/mainSmarty.tpl */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "//www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="//www.w3.org/1999/xhtml" xml:lang="en" lang="en" >
<head>
	<title><?php if ($this->_tpl_vars['page'] != ""): ?><?php echo $this->_tpl_vars['page']; ?>
 -<?php endif; ?> <?php echo $this->_config[0]['vars']['tool']; ?>
 - <?php echo $this->_config[0]['vars']['title']; ?>
</title>
	<link rel="stylesheet" type="text/css" href="//toolserver.org/~tparis/style.css" />
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
<!-- <a href="//toolserver.org/~soxred93/">Home</a> &middot; <a href="//toolserver.org/~soxred93/ec">Edit counter</a> &middot; <a href="//toolserver.org/~soxred93/bash">Random Quotes</a> &middot; <a href="//toolserver.org/~soxred93/editsummary">Edit summary counter</a> &middot; <a href="//toolserver.org/~soxred93/isAdmin">Is X an admin?</a> &middot; <a href="//toolserver.org/~soxred93/topedits">Top namespace edits</a> &middot; <a href="//toolserver.org/~soxred93/pages">Pages created</a> &middot; <a href="//toolserver.org/~soxred93/rfap">RfA Votes</a> &middot; <a href="//toolserver.org/~soxred93/autoedits">Automated edits</a> &middot; <a href="//stable.toolserver.org/nubio">Nubio<sup>2</sup></a> &middot; <a href="//wiki.toolserver.org/view/User:X!/API">Toolserver API</a> &middot; <a href="//toolserver.org/~soxred93/rangecontribs">CIDR range contribs</a> <b>(NEW)</b> &middot; <a href="//toolserver.org/~soxred93/sc">Quick edit counter</a> <b>(NEW)</b> -->
<a href="//toolserver.org/~tparis/">Home</a> &middot; <a href="//toolserver.org/~tparis/ec">Edit counter</a> &middot; <a href="//toolserver.org/~tparis/articleinfo/">Page History Statistics</a> <b>(NEW)</b> &middot; <a href="//toolserver.org/~tparis/blame">Article blamer</a> &middot; <a href="//toolserver.org/~tparis/rangecontribs">CIDR</a> &middot; <a href="//toolserver.org/~tparis/ipcalc">IP calculator</a> &middot; <a href="//wiki.toolserver.org/view/User:TParis/Index">Index</a>
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
<a href="//validator.w3.org/check?uri=referer"><img src="//toolserver.org/~soxred93/images/xhtml.png" alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a>
<a href="//anybrowser.org/campaign"><img src="//toolserver.org/~soxred93/images/anybrowser.png" alt="AnyBrowser compliant" /></a>
<a href="//toolserver.org"><img src="//toolserver.org/~tparis/images/toolserver.png" alt="Powered by WMF Toolserver" /></a>
</span>
<span style="float:right;padding-right:10px">&copy;2010 <a href="//en.wikipedia.org/wiki/User:X!">Soxred93</a>
<?php if ($this->_tpl_vars['source'] != ""): ?> | <a href="//toolserver.org/~tparis/highlight/?q=<?php echo $this->_tpl_vars['source']; ?>
"><?php echo $this->_config[0]['vars']['source']; ?>
</a><?php endif; ?>
<?php if ($this->_tpl_vars['souRce2'] != ""): ?> | <a href="<?php echo $this->_tpl_vars['source2']; ?>
"><?php echo $this->_config[0]['vars']['source']; ?>
</a><?php endif; ?> | <a href="//en.wikipedia.org/wiki/User_talk:TParis"><?php echo $this->_config[0]['vars']['bugs']; ?>
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