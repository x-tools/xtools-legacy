<script type="text/javascript">
{literal}
function switchShow( id ) {
	if(document.getElementById(id).style.display == "none") {
		document.getElementById(id).style.display = "block";
	}
	else{
		document.getElementById(id).style.display = "none";
	}
}
{/literal}

</script>

{if $error != ""}<br /><h2 class="alert">{$error}</h2>{/if}
{if $notice != ""}<br /><h2 class="notice">{$notice}</h2>{/if}
{if $replag != ""}<br /><h2 class="alert">{$replag}</h2>{/if}

{if $form != ""}
{assign var=begintime value='1'}
<br />
<form action="//tools.wmflabs.org/xtools/articleinfo/index.php" method="get" accept-charset="utf-8">
<table>
<tr><td>{#article#}: </td><td><input type="text" name="article" /> <input type="checkbox" name="nofollowredir" /> {#nofollowredir#}</td></tr>
<tr><td>{#wiki#}: </td><td><input type="text" value="{$form}" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org</td></tr>
<tr><td>{#start#}: </td><td><input type="text" name="begin" /></td></tr>
<tr><td>{#end#}: </td><td><input type="text" name="end" /></td></tr>

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

{if $info != ""}
<h3>{#generalstats#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'generalstats' )">show/hide</a>]</span></h3>
<div id = "generalstats">
<table>
<tr><td>{#page#}:</td><td><a href="//{$url}/wiki/{$urlencodedpage}">{$page}</a></td></tr>
<tr><td>{#totaledits#}:</td><td>{$totaledits}</td></tr>
<tr><td>{#minoredits#}:</td><td>{$minoredits} ({$minorpct}%)</td></tr>
<tr><td>{#anonedits#}:</td><td>{$anonedits} ({$anonpct}%)</td></tr>
<tr><td>{#firstedit#}:</td><td>{$firstedit} ({#by#} {$firstuser})</td></tr>
<tr><td>{#lastedit#}:</td><td>{$lastedit}</td></tr>
<tr><td>{#timebwedits#}:</td><td>{$timebwedits} {#days#}</td></tr>
<tr><td>{#editspermonth#}:</td><td>{$editspermonth}</td></tr>
<tr><td>{#editsperyear#}:</td><td>{$editsperyear}</td></tr>
<tr><td>{#lastday#}:</td><td>{$lastday}</td></tr>
<tr><td>{#lastweek#}:</td><td>{$lastweek}</td></tr>
<tr><td>{#lastmonth#}:</td><td>{$lastmonth}</td></tr>
<tr><td>{#lastyear#}:</td><td>{$lastyear}</td></tr>
<tr><td>{#editorcount#}:</td><td>{$editorcount}</td></tr>
<tr><td>{#editsperuser#}:</td><td>{$editsperuser}</td></tr>
<tr><td>{#toptencount#}:</td><td>{$toptencount} ({$toptenpct}%)</td></tr>
</table>
{/if}

{if $graphs != ""}
<table>
<tr>
<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:{$graphuserpct},{$graphanonpct}&amp;chs=250x100&amp;chdl={#users#}%20%28{$graphuserpct}%%29|{#ips#}%20%28{$graphanonpct}%%29&amp;chco=FF5555|55FF55&amp;chf=bg,s,00000000" alt="{#anonalt#}" /></td>
<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:{$graphminorpct},{$graphmajorpct}&amp;chs=250x100&amp;chdl={#minor#}%20%28{$graphminorpct}%%29|{#major#}%20%28{$graphmajorpct}%%29&amp;chco=FFFF55|FF55FF&amp;chf=bg,s,00000000" alt="{#minoralt#}" /></td>
<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:{$graphtoptenpct},{$graphbottomninetypct}&amp;chs=280x100&amp;chdl={#topten#}%20%28{$graphtoptenpct}%%29|{#bottomninety#}%20%28{$graphbottomninetypct}%%29&amp;chco=5555FF|55FFFF&amp;chf=bg,s,00000000" alt="{#toptenalt#}" /></td>
</tr>
</table>
</div>
{/if}

{if $yeargraph != ""}
<h3>{#yearcounts#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'yearcounts' )">show/hide</a>]</span></h3>
<div id="yearcounts">
<table class="months wikitable">
<tr>
<th>{#year#}</th>
<th>{#count#}</th>
<th>{#ips#}</th>
<th>{#minor#}</th>
<th>{#graph#} &mdash; <span style="background-color:#{$pixelcolors.anon};border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#ips#}</span> &mdash; <span style="background-color:#{$pixelcolors.minor};border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#minor#}</span> &mdash; <span style="background-color:#{$pixelcolors.all};border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#alledits#}</span></th>
</tr>
{foreach from=$yearcounts key=key item=val}
<tr>
	<td class="date">{$key}</td>
	<td>{$val.all}</td>
	<td>{$val.anon} ({$val.pcts.anon}%)</td>
	<td>{$val.minor} ({$val.pcts.minor}%)</td>
	<td>
		{if $val.all != 0}
		<div class="outer_bar" style="height:150%;background-color:#{$pixelcolors.all};width:{$yearpixels[$key].all}px;">
			<div class="bar" style="height:50%;border-left:{$yearpixels[$key].anon}px solid #{$pixelcolors.anon}"></div>
			<div class="bar" style="height:50%;border-left:{$yearpixels[$key].minor}px solid #{$pixelcolors.minor}"></div>
		</div>
		{/if}
	</td>
</tr>
{/foreach}	
</table>
</div>
{/if}

{if $linegraph != ""}
<h3>{#linegraph#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'linegraph' )">show/hide</a>]</span></h3>
<div id="linegraph">
<script type="text/javascript" src="//tools.wmflabs.org/xtools/articleinfo/amline/amline/swfobject.js"></script>
<div id="flashcontent">
	<strong>{#upgrade#}</strong>
</div>

<script type="text/javascript">
	// <![CDATA[		
	var so = new SWFObject("//tools.wmflabs.org/xtools/articleinfo/amline/amline/amline.swf", "amline1", "760", "500", "8", "#D0E4EE");
	so.addVariable("path", "//tools.wmflabs.org/xtools/articleinfo/amline/amline/");
	{*so.addVariable("chart_data", encodeURIComponent("{$linegraphdata}"));*}
	so.addVariable("data_file", escape("//tools.wmflabs.org/xtools/articleinfo/data/{$linegraphdata}.xml"));
	so.addVariable("settings_file", escape("//tools.wmflabs.org/xtools/articleinfo/amline/amline/amline_settings_w.xml"));

	so.write("flashcontent");
	// ]]>
</script>
</div>
{/if}

{if $monthgraph != ""}
<h3>{#monthcounts#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'monthcounts' )">show/hide</a>]</span></h3>
<div id="monthcounts">
<table class="months wikitable">
{foreach from=$yearcounts key=key item=val}
<tr>
<th>{#month#}</th>
<th>{#count#}</th>
<th>{#ips#}</th>
<th>{#minor#}</th>
<th>{#graph#} &mdash; <span style="background-color:#{$pixelcolors.anon};border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#ips#}</span> &mdash; <span style="background-color:#{$pixelcolors.minor};border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#minor#}</span> &mdash; <span style="background-color:#{$pixelcolors.all};border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#alledits#}</span></th>
</tr>
	{foreach from=$val.months key=month item=info}
		<tr>
			<td {if $evenyears[$key] != ""}style="background-color:#DEF;"{/if} class="date">{$month}/{$key}</td>
			<td {if $evenyears[$key] != ""}style="background-color:#DEF;"{/if}>{$info.all}</td>
			<td {if $evenyears[$key] != ""}style="background-color:#DEF;"{/if}>{$info.anon} ({$info.pcts.anon}%)</td>
			<td {if $evenyears[$key] != ""}style="background-color:#DEF;"{/if}>{$info.minor} ({$info.pcts.minor}%)</td>
			<td {if $evenyears[$key] != ""}style="background-color:#DEF;"{/if}>
				{if $info.all != 0}
				<div class="outer_bar" style="height:150%;background-color:#{$pixelcolors.all};width:{$monthpixels[$key][$month].all}px;">
					<div class="bar" style="height:50%;border-left:{$monthpixels[$key][$month].anon}px solid #{$pixelcolors.anon}"></div>
					<div class="bar" style="height:50%;border-left:{$monthpixels[$key][$month].minor}px solid #{$pixelcolors.minor}"></div>
				</div>
				{/if}
			</td>
		</tr>
	{/foreach}
{/foreach}	
</table>
</div>
{/if}

{if $sizegraph != ""}
<h3>{#sizegraph#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'sizegraph' )">show/hide</a>]</span></h3>
<div id="sizegraph">
<script type="text/javascript" src="//tools.wmflabs.org/xtools/articleinfo/amline/amline/swfobject.js"></script>
<div id="flashcontent2">
	<strong>{#upgrade#}</strong>
</div>

<script type="text/javascript">
	// <![CDATA[		
	var so = new SWFObject("//tools.wmflabs.org/xtools/articleinfo/amline/amline/amline.swf", "amline2", "760", "500", "8", "#D0E4EE");
	so.addVariable("path", "//tools.wmflabs.org/xtools/articleinfo/amline/amline/");
	so.addVariable("data_file", escape("//tools.wmflabs.org/xtools/articleinfo/data/{$sizegraphdata}.xml"));
	so.addVariable("settings_file", escape("//tools.wmflabs.org/xtools/articleinfo/amline/amline/samline_settings_w.xml"));

	so.write("flashcontent2");
	// ]]>
</script>
</div>
{/if}

{if $usertable != ""}
<h3>{#usertable#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'usertable' )">show/hide</a>]</span></h3>
<div id="usertable">
<table class="months wikitable">
	<tr>
		<th>{#user#}</th>
		<th>{#count#}</th>
		<th>{#minor#}</th>
		<th>{#firstedit#}</th>
		<th>{#lastedit#}</th>
		<th>{#atbe#}</th>
		<th>{#avgsize#}</th>
	</tr>
	{foreach from=$userdata key=user item=info}
		{if in_array( $user, $topteneditors) }
			<tr>
				<td class="date"><a href="//{$url}/wiki/User:{$info.urlencoded}">{$user}</a> (<a href="//tools.wmflabs.org/xtools/pcount/{$info.urlencoded}/{$lang}/{$wiki}">{#editcount#}</a>)</td>
				<td>{$info.all}</td>
				<td>{$info.minor} ({$info.minorpct}%)</td>
				<td>{$info.first}</td>
				<td>{$info.last}</td>
				<td>{$info.atbe}</td>
				<td>{$info.size} KB</td>
			</tr>
		{/if}
	{/foreach}
</table>
</div>
{/if}

