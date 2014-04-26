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
<a href="//tools.wmflabs.org/xtools/">Home</a> &middot; <a href="//tools.wmflabs.org/xtools/ec">Edit counter</a> &middot; <a href="//tools.wmflabs.org/xtools/articleinfo/">Page History Statistics</a> &middot; <a href="//tools.wmflabs.org/xtools/blame">Article blamer</a> &middot; <a href="//tools.wmflabs.org/xtools/rangecontribs">CIDR</a> &middot; <a href="//tools.wmflabs.org/xtools/ipcalc">IP calculator</a> &middot; <a href="//tools.wmflabs.org/xtools/bash">IRC random quote</a> &middot; <a href="//wiki.toolserver.org/view/User:TParis/Index">Index</a>
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
<span style="float:right;">
<a href="//validator.w3.org/check?uri=referer"><img src="//tools.wmflabs.org/xtools/images/xhtml.png" alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a>
<a href="//anybrowser.org/campaign"><img src="//tools.wmflabs.org/xtools/images/anybrowser.png" alt="AnyBrowser compliant" /></a>
<a href="//tools.wmflabs.org"><img src="//tools.wmflabs.org/xtools/images/labs.png" alt="Powered by WMF Labs" /></a>
</span>
<span style="float:right;padding-right:10px">&copy;2013 <a href="//en.wikipedia.org/wiki/User:X!">X!</a>
{if $source != ""} | <a href="//tools.wmflabs.org/xtools/highlight/?q={$source}">{#source#}</a>{/if}
{if $souRce2 != ""} | <a href="{$source2}">{#source#}</a>{/if} | <a href="https://webchat.freenode.net/?channels=#xlabs">{#bugs#}</a></span>
<a name="footer" />
{if $curlang != ""}<span id="footertext">{#language#}{/if}{if $translate != ""} (<a href="{$translate}">{#translatelink#}</a>){/if}{if $curlang != ""}: {$curlang} | <span width="30%">{$langlinks}</span></span>{/if}
</div>
</div>

<script type="text/javascript">if (window.runOnloadHook) runOnloadHook();</script>

</body>

</html>
