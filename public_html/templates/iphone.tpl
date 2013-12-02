<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
         "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="//www.w3.org/1999/xhtml">
<head>
<title>{&isset: page &}{$page$} -{&endisset&} {#tool#} - {#title#}</title>
<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
<style type="text/css" media="screen">@import "//tools.wmflabs.org/xtools/iphone.css";</style>
<script type="application/x-javascript" src="//tools.wmflabs.org/xtools/iphone.js"></script>
{&isset: moreiheader &}{$moreiheader$}{&endisset&}
</head>

<body>
    <h1 id="pageTitle">{#tool#}</h1>

{$content$}
</body>

</html>
