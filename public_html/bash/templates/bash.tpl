{if $error != ""}<br /><h2 class="alert">{$error}</h2>{/if}
{if $notice != ""}<br /><h2 class="notice">{$notice}</h2>{/if}

{if $form != ""}
<br />
<form action="//tools.wmflabs.org/xtools/bash/index.php" method="get" accept-charset="utf-8">
<table class="wikitable">
<tr>
	<td colspan="2"><input type="radio" name="action" value="random" checked="checked" /> {#random#}</td>
</tr>
<tr>
	<td colspan="2"><input type="radio" name="action" value="showall" /> {#showall#}</td>
</tr>
<tr>
	<td><input type="radio" name="action" value="showone" /> {#showone#} <input type="text" name="id" size="4" /></td>
</tr>
<tr>
	<td><input type="radio" name="action" value="search" /> {#search#}  <input type="text" name="search" /> <input type="checkbox" name="regex" /> {#regex#}</td>
</tr>
<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
</table>
</form><br /><hr />
{/if}

{if $random != ""}
<h3>{#quotenumber#} {$id}</h3>

<pre>
{$quote}
</pre>

<a href="{$thisurl}">{#showanother#}</a>
{/if}

{if $showone != ""}
<h3>{#quotenumber#} {$id}</h3>

<pre>
{$quote}
</pre>

{/if}


{if $showall != ""}
<h3>{#allquotes#}</h3>

{foreach from=$quotes key="id" item="quote"}

<h3>{#quotenumber#} {$id}</h3>
<pre>
{$quote}
</pre>
{/foreach}

{/if}

{if $search != ""}
<h3>{#searchresults#}</h3>

{foreach from=$quotes key="id" item="quote"}

<h3>{#quotenumber#} {$id}</h3>
<pre>
{$quote}
</pre>
{/foreach}

{/if}



