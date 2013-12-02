{&isset: error &}<br /><h2 class="alert">{$error$}</h2>{&endisset&}
{&isset: replag &}<br /><h2 class="alert">{$replag$}</h2>{&endisset&}
{&isset: nouser &}<br /><h2 class="alert">{#nouser#}</h2>{&endisset&}

{&isset: form &}
<br />
{#welcome#}
<br /><br />
<form action="http://tools.wmflabs.org/xtools/pcount/translate.php" method="get" accept-charset="utf-8">
<table>
<tr><td>{#language#}: </td><td>
<select name="language">
{$form$}
</select>
</td></tr>
<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
</table>
</form><br /><hr />
{&endisset&}

{&isset: translationform &}<br />
{$translationform$}
{&endisset&}

{&isset: submitted &}
{#submitted#}
{&endisset&}