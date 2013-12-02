{&isset: error &}<br /><h2 class="alert">{$error$}</h2>{&endisset&}
{&isset: replag &}<br /><h2 class="alert">{$replag$}</h2>{&endisset&}
{&isset: nouser &}<br /><h2 class="alert">{#nouser#}</h2>{&endisset&}

{&isset: form &}
<br />
{#selectfile#}
<br /><br />
<form action="http://tools.wmflabs.org/xtools/pcount/source.php" method="get">
<table>
<tr><td>{#File#}: </td><td>
<select name="path">
<option value="http">HTTP.php</option>
<option value="database">Database.php</option>
<option value="functions">Functions.php</option>
<option value="counter">counter.php</option>
<option value="graph">graph.php</option>
<option value="index">index.php</option>
<option value="PHPtemp">PHPtemp.php</option>
<option value="Language">Language.php</option>
<option value="main.tpl">main.tpl</option>
<option value="pcount.tpl">pcount.tpl</option>
</select>
</td></tr>
<tr><td><input type="submit" value="{#submit#}" /></td></tr>
</table>
</form><br /><hr />
{&endisset&}

{&isset: source &}<br />
<pre>
{$source$}
</pre>
{&endisset&}
