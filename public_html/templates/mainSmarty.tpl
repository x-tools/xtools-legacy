<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "//www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="//www.w3.org/1999/xhtml" xml:lang="en" lang="en" >
<head>
	<title>{if $page != ""}{$page} -{/if} {#tool#} - {#title#}</title>
	<link rel="stylesheet" type="text/css" href="//tools.wmflabs.org/xtools/style.css" />
	{if $moreheader != ""}{$moreheader}{/if}
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
<div id="wrap">
<div id="header">
{if $page != ""}{$page} -{/if} {#tool#} - {#title#}
</div>
<div id="content">
<div id="navigation" class="center container">
<!-- <a href="//tools.wmflabs.org/xtools/">Home</a> &middot; <a href="//tools.wmflabs.org/xtools/ec">Edit counter</a> &middot; <a href="//tools.wmflabs.org/xtools/bash">Random Quotes</a> &middot; <a href="//tools.wmflabs.org/xtools/editsummary">Edit summary counter</a> &middot; <a href="//tools.wmflabs.org/xtools/isAdmin">Is X an admin?</a> &middot; <a href="//tools.wmflabs.org/xtools/topedits">Top namespace edits</a> &middot; <a href="//tools.wmflabs.org/xtools/pages">Pages created</a> &middot; <a href="//tools.wmflabs.org/xtools/rfap">RfA Votes</a> &middot; <a href="//tools.wmflabs.org/xtools/autoedits">Automated edits</a> &middot; <a href="//stable.toolserver.org/nubio">Nubio<sup>2</sup></a> &middot; <a href="//wiki.toolserver.org/view/User:X!/API">Toolserver API</a> &middot; <a href="//tools.wmflabs.org/xtools/rangecontribs">CIDR range contribs</a> <b>(NEW)</b> &middot; <a href="//tools.wmflabs.org/xtools/sc">Quick edit counter</a> <b>(NEW)</b> -->
	<a href="//tools.wmflabs.org/xtools/">Home</a> &middot; 
	<a href="//tools.wmflabs.org/supercount/">Edit counter</a> &middot; 
	<a href="//tools.wmflabs.org/xtools/articleinfo/">Page History Statistics</a> &middot; 
	<a href="//tools.wmflabs.org/xtools/blame">Article blamer</a> &middot;
	<a href="//tools.wmflabs.org/xtools/pages">Pages created</a> &middot;
	<a href="//tools.wmflabs.org/xtools/autoedits/">Automated edits</a> &middot; 
	<a href="//tools.wmflabs.org/xtools/rangecontribs">CIDR</a> &middot; 
	<a href="//tools.wmflabs.org/xtools/ipcalc">IP calculator</a> &middot; 
	<a href="//tools.wmflabs.org/xtools/bash">Random quote</a> &middot; 
</div>
{if $alert != ""}<br /><h2 class="alert">{$alert}</h2>{/if}
<h2>{#header#}</h2>
{if $error != ""}{$error}{/if}
{$content}
{if $executedtime != ""}
<br />
<hr />
<span style="font-size:100%;">
{$executedtime}
</span><br />
{/if}
</div>

<div id="footer">
<div style="float:right; display:inline-block">
	<span >
		<!-- <a href="//validator.w3.org/check?uri=referer"><img src="//tools.wmflabs.org/xtools/images/xhtml.png" alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a> -->
		<a href="//anybrowser.org/campaign"><img src="//tools.wmflabs.org/xtools/images/anybrowser.png" alt="AnyBrowser compliant" /></a>
		<a href="//tools.wmflabs.org"><img src="//tools.wmflabs.org/xtools/images/labs.png" alt="Powered by WMF Labs" /></a>
	</span>
</div>
<div style="float:left; display:inline-block; line-height:1.5em;">
	<span>&copy;2014 
		<a href="//en.wikipedia.org/wiki/User:Cyberpower678">Cyberpower678</a>&middot; 
		<a href="//de.wikipedia.org/wiki/User:Hedonil">Hedonil</a>&middot; 
		<a href="//en.wikipedia.org/wiki/User:TParis">TParis</a>&middot;
		<a href="//en.wikipedia.org/wiki/User:X!">X!</a> &nbsp;|&nbsp; 
		{if $source != ""}<a href="//github.com/x-Tools/xtools/tree/master/public_html/{$source}" >{#source#}</a>
		{else}<a href="//github.com/x-Tools/xtools/" >{#source#}</a>{/if} &nbsp;|&nbsp;
		<a href="//github.com/x-Tools/xtools/issues" >{#bugs#}</a> &nbsp;|&nbsp;
		<a href="irc://irc.freenode.net/#wikimedia-labs" >#wikimedia-labs</a>
		<sup><a  style="color:green" href="https://webchat.freenode.net/?channels=#wikimedia-labs">WebChat</a></sup>
	</span><br />
	<span>
		{if $curlang != ""}{#language#}{/if}{if $curlang != ""}: {$curlang} | <span >{$langlinks}</span>{/if}
	</span>
</div>
</div>
</div>

<!-- currently ice'd {if $translate != ""} (<a href="{$translate}">{#translatelink#}</a>){/if} -->


<script type="text/javascript">if (window.runOnloadHook) runOnloadHook();</script>

</body>

</html>
