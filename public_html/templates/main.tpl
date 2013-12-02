<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "//www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="//www.w3.org/1999/xhtml" xml:lang="en" lang="en" >
<head>
	<title>{&isset: page &}{$page$} -{&endisset&} {#tool#} - {#title#}</title>
	<link rel="stylesheet" type="text/css" href="//tools.wmflabs.org/xtools/style.css" />
	{&isset: moreheader &}{$moreheader$}{&endisset&}
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
<div id="wrap">
<div id="header">
{&isset: page &}{$page$} -{&endisset&} {#tool#} - {#title#}
</div>
<div id="content">
<div id="navigation" class="center container">
<!-- <a href="//tools.wmflabs.org/xtools/">Home</a> &middot; <a href="//tools.wmflabs.org/xtools/ec">Edit counter</a> &middot; <a href="//tools.wmflabs.org/xtools/bash">Random Quotes</a> &middot; <a href="//tools.wmflabs.org/xtools/editsummary">Edit summary counter</a> &middot; <a href="//tools.wmflabs.org/xtools/isAdmin">Is X an admin?</a> &middot; <a href="//tools.wmflabs.org/xtools/topedits">Top namespace edits</a> &middot; <a href="//tools.wmflabs.org/xtools/pages">Pages created</a> &middot; <a href="//tools.wmflabs.org/xtools/rfap">RfA Votes</a> &middot; <a href="//tools.wmflabs.org/xtools/autoedits">Automated edits</a> &middot; <a href="//stable.toolserver.org/nubio">Nubio<sup>2</sup></a> &middot; <a href="//wiki.toolserver.org/view/User:X!/API">Toolserver API</a> &middot; <a href="//tools.wmflabs.org/xtools/rangecontribs">CIDR range contribs</a> <b>(NEW)</b> &middot; <a href="//tools.wmflabs.org/xtools/sc">Quick edit counter</a> <b>(NEW)</b> -->
<a href="//tools.wmflabs.org/xtools/">Home</a> &middot; <a href="//tools.wmflabs.org/xtools/ec">Edit counter</a> &middot; <a href="//tools.wmflabs.org/xtools/articleinfo/">Page History Statistics</a> &middot; <a href="//tools.wmflabs.org/xtools/blame">Article blamer</a> &middot; <a href="//tools.wmflabs.org/xtools/rangecontribs">CIDR</a> &middot; <a href="//tools.wmflabs.org/xtools/ipcalc">IP calculator</a> &middot; <a href="//wiki.toolserver.org/view/User:tparis/Index">Index</a>
</div>
{&isset: alert &}<br /><h2 class="alert">{$alert$}</h2>{&endisset&}
<h2>{$header$}</h2>
{&isset: error &}{$error$}{&endisset&}
{$content$}
{&isset: executedtime &}
<br />
<hr />
<span style="font-size:100%;">
{$executedtime$}
</span><br />
{&endisset&}
</div>
<div id="footer">
<span style="float:right;">
<a href="//validator.w3.org/check?uri=referer"><img src="//tools.wmflabs.org/xtools/images/xhtml.png" alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a>
<a href="//anybrowser.org/campaign"><img src="//tools.wmflabs.org/xtools/images/anybrowser.png" alt="AnyBrowser compliant" /></a>
<a href="//tools.wmflabs.org"><img src="//tools.wmflabs.org/xtools/images/labs.png" alt="Powered by WMF Labs" /></a>
</span>
<span style="float:right;padding-right:10px">&copy;2010 <a href="https://wikitech.wikimedia.org/wiki/Nova_Resource_Talk:Xtools">Support</a>
{&isset: source &} | <a href="//tools.wmflabs.org/xtools/highlight/?q={$source$}">{#source#}</a>{&endisset&}
{&isset: source2 &} | <a href="{$source2$}">{#source#}</a>{&endisset&} | <a href="https://webchat.freenode.net/?channels=#xlabs">Bugs</a></span></span>
<a name="footer" />
{&isset: curlang &}<span id="footertext">{#language#}{&endisset&}{&isset: translate &} (<a href="{$translate$}">{#translatelink#}</a>){&endisset&}{&isset: curlang &}: {$curlang$} | <span width="30%">{$langlinks$}</span></span>{&endisset&}
</div>
</div>
Script maintained by the xlabs team. 
<script type="text/javascript">if (window.runOnloadHook) runOnloadHook();</script>

</body>

</html>
