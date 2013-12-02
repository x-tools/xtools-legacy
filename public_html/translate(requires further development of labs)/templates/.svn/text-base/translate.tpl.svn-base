{if $error != ""}<br /><h2 class="alert">{$error}</h2>{/if}
{if $notice != ""}<br /><h2 class="notice">{$notice}</h2>{/if}
{if $replag != ""}<br /><h2 class="alert">{$replag}</h2>{/if}

{if $form != ""}
<form action="//toolserver.org/~soxred93/translate/index.php" method="get" accept-charset="utf-8">
<input type="hidden" value="step1" name="action" />
<input type="hidden" value="{$uselang}" name="uselang" />
<table>
<tr><td>{#toolname#}: </td><td>
<select name="toolname">
	{foreach from=$tools key="tool" item="uri"}
		<option {if $usetool == $tool}selected="selected"{/if}>{$tool}</option>
	{/foreach}
</select>
</td></tr>
<tr><td>{#language#}: </td><td>
<select name="lang">
	{foreach from=$langs item="lang"}
		{if $lang == "en"}
			<option value="en" selected="selected">en</option>
		{else}
			<option>{$lang}</option>
		{/if}
	{/foreach}
</select>
</td></tr>
<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
</table>
</form><br /><hr />
{/if}

{if $showvars != ""}
{#showvarshelp#}

{if $tool != "Global"}<br /><br />{#globalnotice#}{/if}
<form action="//toolserver.org/~soxred93/translate/index.php" method="post" accept-charset="utf-8">
<input type="hidden" value="{$uselang}" name="uselang" />
<input type="hidden" value="step2" name="action" />
<input type="hidden" value="{$lang}" name="lang" />
<input type="hidden" value="{$tool}" name="toolname" />
<table class="wikitable">
<tr>
	<th>{#id#}</th>
	<th>{#text#}</th>
	<th>{#explanation#}</th>
</tr>
{foreach from=$config_vars key="id" item="data"}
	<tr>
		<td>{$id}</td>
		<td><input size="60" type="text" name="{$id}" value="{$data.value}"></td>
		<td>{$data.qqq}</td>
	</tr>
{/foreach}
</table>
<input type="submit" value="{#submit#}" />
</form>
{/if}

{if $success != ""}
{#success#}
{/if}

{if $adminlist != ""}
<form action="//toolserver.org/~soxred93/translate/index.php" method="post" accept-charset="utf-8">

<table width="100%" class="wikitable">

{foreach from=$submissionlist item="row"}
	<tr>
		<th colspan="2">{$row.tr_tool} - {$row.tr_lang}</th>
	</tr>
	<tr>
		<th>Diff</th>
		<th>Info</th>
	</tr>
	<tr>
		<td>{$row.tr_diff}</td>
		<td>
			<ul>
				<li>Date: {$row.tr_date}</li>
				<li>IP: {$row.tr_ip}</li>
				<li><input type="radio" name="result-{$row.tr_id}" value="approve" /> Approve</li>
				<li><input type="radio" name="result-{$row.tr_id}" value="reject" /> Reject</li>
			</ul>
			
		</td>
	</tr>
{/foreach}


</table>

<input type="hidden" name="approve" value="1" />
<input type="hidden" name="action" value="admin" />
<input type="hidden" name="password" value="{$password}" />
<input type="submit" value="{#submit#}" />

</form>
{/if}

{if $donemsg != ""}
{$donemsg}
{/if}

{if $passmenu != ""}
<form action="//toolserver.org/~soxred93/translate/index.php" method="post" accept-charset="utf-8">
<input type="hidden" name="action" value="admin" />
Password: <input type="password" name="password" />
<input type="submit" value="{#submit#}" />

</form>
{/if}



