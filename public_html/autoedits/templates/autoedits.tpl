{if $error != ""}<br /><h2 class="alert">{$error}</h2>{/if}
{if $notice != ""}<br /><h2 class="notice">{$notice}</h2>{/if}
{if $replag != ""}<br /><h2 class="alert">{$replag}</h2>{/if}

{if $form != ""}
{assign var=begintime value='1'}
<br />
<form action="//tools.wmflabs.org/xtools/autoedits/index.php" method="get" accept-charset="utf-8">
<table>
<tr><td>{#user#}: </td><td><input type="text" name="user" /></td></tr>
<tr><td>{#wiki#}: </td><td><input type="text" value="{$form}" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org</td></tr>
<tr><td>{#start#}: </td><td><input type="text" name="begin" /></td></tr>
<tr><td>{#end#}: </td><td><input type="text" name="end" /></td></tr>

<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
</table>
</form><br /><hr />
{/if}

{if $showedits != ""}

{#approximate#}

<ul>
{foreach from=$data key="tool" item="count"}
   <li><a href="//{$url}/wiki/{$urls[$tool]}">{$tool}</a> &ndash; {$count}</li>
{/foreach}
</ul>

<table class="wikitable">
   <tr>
      <td>{#totalauto#}</td><td>{$totalauto}</td>
   </tr>
   <tr>
      <td>{#totalall#}</td><td>{$totalall}</td>
   </tr>
   <tr>
      <td>{#autopct#}</td><td>{$pct}%</td>
   </tr>
</table>
{/if} 
