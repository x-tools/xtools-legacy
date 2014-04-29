
{if $error != ""}<br /><h2 class="alert">{$error}</h2>{/if}
{if $notice != ""}<br /><h2 class="notice">{$notice}</h2>{/if}
{if $replag != ""}<br /><h2 class="alert">{$replag}</h2>{/if}

{if $form != ""}
{assign var=begintime value='1'}
<br />
<form action="?" method="get" accept-charset="utf-8">
<table>
<tr><td>{#user#}: </td><td><input type="text" name="user" /></td></tr>
<tr><td>{#wiki#}: </td><td><input type="text" value="{$form}" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org</td></tr>
<tr><td>{#namespace#}: </td><td>{$selectns}</td></tr>
<tr><td>{#redirects#}: </td><td>{$selectredir}</td></tr>
<!-- 
<tr><td>{#start#}: </td><td><input type="text" name="begin" /></td></tr>
<tr><td>{#end#}: </td><td><input type="text" name="end" /></td></tr>
-->
<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
</table>
</form><br /><hr />
{/if}

{if $showresult != ""}

{$totalcreated}&nbsp;(Redirect filter: {$filterredir})
{if $graphs != ""}
<table>
<tr>
<td>
	<table style="margin-top: 10px" >{$toptable}
		<tr>
			<th>NS</th>
			<th>NS name</th>
			<th>Pages</th>
			<th style="padding_left:5px">&nbsp;&nbsp;(Redirects)</th>
		</tr>
		{foreach from=$namespaces key=number item=ns}
			<tr>
				<td style="padding-right:5px; text-align:center;">{$number}</td>
				<td style="padding-right:10px"><a href="#{$number}" >{$ns.name}</a></td>
				<td style="text-align:right" >{$ns.num}</td>
				<td style="text-align:right" >{$ns.redir}</td>
			</tr>
		{/foreach}
	</table>
</td>
<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:{$nstotals}&amp;chs=550x140&amp;chl={$nsnames}&amp;chco=599ad3|f1595f|79c36a|f9a65a|727272|9e66ab|cd7058|ff0000|00ff00&amp;chf=bg,s,00000000" alt="{#minoralt#}" /></td>
</tr>
</table>

{/if}
<table class="sortable" >
{$list}
</table>


{/if} 
