{if $error != ""}<br /><h2 class="alert">{$error}</h2>{/if}
{if $replag != ""}<br /><h2 class="alert">{$replag}</h2>{/if}

{if $form != ""}
<br />
{#welcome#}
<br /><br />
<form action="//tools.wmflabs.org/xtools/blame/index.php" method="get" accept-charset="utf-8">
<table>
<tr><td>{#article#}: </td><td><input type="text" name="article" /> <input type="checkbox" name="nofollowredir" /> {#nofollowredir#}</td></tr>
<tr><td>{#wiki#}: </td><td><input type="text" value="{$form}" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org</td></tr>
<tr><td>{#tosearch#}: </td><td><textarea name="text" rows="10" cols="40"></textarea></td></tr>
<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
</table>
</form><br /><hr />
{/if}

{if $revs != ""}
{#added#}
<ul>
{foreach from=$revs key=id item=i}
{$i}
{/foreach}  
</ul>
{/if} 
