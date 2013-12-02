<?php /* Smarty version 2.6.18, created on 2010-08-01 03:30:43
         compiled from articleinfo.tpl */ ?>
<script type="text/javascript">
<?php echo '
function switchShow( id ) {
	if(document.getElementById(id).style.display == "none") {
		document.getElementById(id).style.display = "block";
	}
	else{
		document.getElementById(id).style.display = "none";
	}
}
'; ?>


</script>

<?php if ($this->_tpl_vars['error'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['error']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['notice'] != ""): ?><br /><h2 class="notice"><?php echo $this->_tpl_vars['notice']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['replag'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['replag']; ?>
</h2><?php endif; ?>

<?php if ($this->_tpl_vars['form'] != ""): ?>
<?php $this->assign('begintime', '1'); ?>
<br />
<form action="//toolserver.org/~soxred93/articleinfo/index.php" method="get" accept-charset="utf-8">
<table>
<tr><td><?php echo $this->_config[0]['vars']['article']; ?>
: </td><td><input type="text" name="article" /> <input type="checkbox" name="nofollowredir" /> <?php echo $this->_config[0]['vars']['nofollowredir']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['wiki']; ?>
: </td><td><input type="text" value="<?php echo $this->_tpl_vars['form']; ?>
" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['start']; ?>
: </td><td><input type="text" name="begin" /></td></tr>
<tr><td><?php echo $this->_config[0]['vars']['end']; ?>
: </td><td><input type="text" name="end" /></td></tr>

<tr><td colspan="2"><input type="submit" value="<?php echo $this->_config[0]['vars']['submit']; ?>
" /></td></tr>
</table>
</form><br /><hr />
<?php endif; ?>

<?php if ($this->_tpl_vars['revs'] != ""): ?>
<?php echo $this->_config[0]['vars']['added']; ?>

<ul>
<?php $_from = $this->_tpl_vars['revs']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['id'] => $this->_tpl_vars['i']):
?>
<?php echo $this->_tpl_vars['i']; ?>

<?php endforeach; endif; unset($_from); ?>	
</ul>
<?php endif; ?>	

<?php if ($this->_tpl_vars['info'] != ""): ?>
<h3><?php echo $this->_config[0]['vars']['generalstats']; ?>
 &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'generalstats' )">show/hide</a>]</span></h3>
<div id = "generalstats">
<table>
<tr><td><?php echo $this->_config[0]['vars']['page']; ?>
:</td><td><a href="//<?php echo $this->_tpl_vars['url']; ?>
/wiki/<?php echo $this->_tpl_vars['urlencodedpage']; ?>
"><?php echo $this->_tpl_vars['page']; ?>
</a></td></tr>
<tr><td><?php echo $this->_config[0]['vars']['totaledits']; ?>
:</td><td><?php echo $this->_tpl_vars['totaledits']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['minoredits']; ?>
:</td><td><?php echo $this->_tpl_vars['minoredits']; ?>
 (<?php echo $this->_tpl_vars['minorpct']; ?>
%)</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['anonedits']; ?>
:</td><td><?php echo $this->_tpl_vars['anonedits']; ?>
 (<?php echo $this->_tpl_vars['anonpct']; ?>
%)</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['firstedit']; ?>
:</td><td><?php echo $this->_tpl_vars['firstedit']; ?>
 (<?php echo $this->_config[0]['vars']['by']; ?>
 <?php echo $this->_tpl_vars['firstuser']; ?>
)</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['lastedit']; ?>
:</td><td><?php echo $this->_tpl_vars['lastedit']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['timebwedits']; ?>
:</td><td><?php echo $this->_tpl_vars['timebwedits']; ?>
 <?php echo $this->_config[0]['vars']['days']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['editspermonth']; ?>
:</td><td><?php echo $this->_tpl_vars['editspermonth']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['editsperyear']; ?>
:</td><td><?php echo $this->_tpl_vars['editsperyear']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['lastday']; ?>
:</td><td><?php echo $this->_tpl_vars['lastday']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['lastweek']; ?>
:</td><td><?php echo $this->_tpl_vars['lastweek']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['lastmonth']; ?>
:</td><td><?php echo $this->_tpl_vars['lastmonth']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['lastyear']; ?>
:</td><td><?php echo $this->_tpl_vars['lastyear']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['editorcount']; ?>
:</td><td><?php echo $this->_tpl_vars['editorcount']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['editsperuser']; ?>
:</td><td><?php echo $this->_tpl_vars['editsperuser']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['toptencount']; ?>
:</td><td><?php echo $this->_tpl_vars['toptencount']; ?>
 (<?php echo $this->_tpl_vars['toptenpct']; ?>
%)</td></tr>
</table>
<?php endif; ?>

<?php if ($this->_tpl_vars['graphs'] != ""): ?>
<table>
<tr>
<td><img src="//chart.apis.google.com/chart?cht=p3&amp;chd=t:<?php echo $this->_tpl_vars['graphuserpct']; ?>
,<?php echo $this->_tpl_vars['graphanonpct']; ?>
&amp;chs=250x100&amp;chdl=<?php echo $this->_config[0]['vars']['users']; ?>
%20%28<?php echo $this->_tpl_vars['graphuserpct']; ?>
%%29|<?php echo $this->_config[0]['vars']['ips']; ?>
%20%28<?php echo $this->_tpl_vars['graphanonpct']; ?>
%%29&amp;chco=FF5555|55FF55&amp;chf=bg,s,00000000" alt="<?php echo $this->_config[0]['vars']['anonalt']; ?>
" /></td>
<td><img src="//chart.apis.google.com/chart?cht=p3&amp;chd=t:<?php echo $this->_tpl_vars['graphminorpct']; ?>
,<?php echo $this->_tpl_vars['graphmajorpct']; ?>
&amp;chs=250x100&amp;chdl=<?php echo $this->_config[0]['vars']['minor']; ?>
%20%28<?php echo $this->_tpl_vars['graphminorpct']; ?>
%%29|<?php echo $this->_config[0]['vars']['major']; ?>
%20%28<?php echo $this->_tpl_vars['graphmajorpct']; ?>
%%29&amp;chco=FFFF55|FF55FF&amp;chf=bg,s,00000000" alt="<?php echo $this->_config[0]['vars']['minoralt']; ?>
" /></td>
<td><img src="//chart.apis.google.com/chart?cht=p3&amp;chd=t:<?php echo $this->_tpl_vars['graphtoptenpct']; ?>
,<?php echo $this->_tpl_vars['graphbottomninetypct']; ?>
&amp;chs=280x100&amp;chdl=<?php echo $this->_config[0]['vars']['topten']; ?>
%20%28<?php echo $this->_tpl_vars['graphtoptenpct']; ?>
%%29|<?php echo $this->_config[0]['vars']['bottomninety']; ?>
%20%28<?php echo $this->_tpl_vars['graphbottomninetypct']; ?>
%%29&amp;chco=5555FF|55FFFF&amp;chf=bg,s,00000000" alt="<?php echo $this->_config[0]['vars']['toptenalt']; ?>
" /></td>
</tr>
</table>
</div>
<?php endif; ?>

<?php if ($this->_tpl_vars['yeargraph'] != ""): ?>
<h3><?php echo $this->_config[0]['vars']['yearcounts']; ?>
 &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'yearcounts' )">show/hide</a>]</span></h3>
<div id="yearcounts">
<table class="months wikitable">
<tr>
<th><?php echo $this->_config[0]['vars']['year']; ?>
</th>
<th><?php echo $this->_config[0]['vars']['count']; ?>
</th>
<th><?php echo $this->_config[0]['vars']['ips']; ?>
</th>
<th><?php echo $this->_config[0]['vars']['minor']; ?>
</th>
<th><?php echo $this->_config[0]['vars']['graph']; ?>
 &mdash; <span style="background-color:#<?php echo $this->_tpl_vars['pixelcolors']['anon']; ?>
;border: 1px solid #000;padding: 0 0.3em 0 0.3em;"><?php echo $this->_config[0]['vars']['ips']; ?>
</span> &mdash; <span style="background-color:#<?php echo $this->_tpl_vars['pixelcolors']['minor']; ?>
;border: 1px solid #000;padding: 0 0.3em 0 0.3em;"><?php echo $this->_config[0]['vars']['minor']; ?>
</span> &mdash; <span style="background-color:#<?php echo $this->_tpl_vars['pixelcolors']['all']; ?>
;border: 1px solid #000;padding: 0 0.3em 0 0.3em;"><?php echo $this->_config[0]['vars']['alledits']; ?>
</span></th>
</tr>
<?php $_from = $this->_tpl_vars['yearcounts']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['key'] => $this->_tpl_vars['val']):
?>
<tr>
	<td class="date"><?php echo $this->_tpl_vars['key']; ?>
</td>
	<td><?php echo $this->_tpl_vars['val']['all']; ?>
</td>
	<td><?php echo $this->_tpl_vars['val']['anon']; ?>
 (<?php echo $this->_tpl_vars['val']['pcts']['anon']; ?>
%)</td>
	<td><?php echo $this->_tpl_vars['val']['minor']; ?>
 (<?php echo $this->_tpl_vars['val']['pcts']['minor']; ?>
%)</td>
	<td>
		<?php if ($this->_tpl_vars['val']['all'] != 0): ?>
		<div class="outer_bar" style="height:150%;background-color:#<?php echo $this->_tpl_vars['pixelcolors']['all']; ?>
;width:<?php echo $this->_tpl_vars['yearpixels'][$this->_tpl_vars['key']]['all']; ?>
px;">
			<div class="bar" style="height:50%;border-left:<?php echo $this->_tpl_vars['yearpixels'][$this->_tpl_vars['key']]['anon']; ?>
px solid #<?php echo $this->_tpl_vars['pixelcolors']['anon']; ?>
"></div>
			<div class="bar" style="height:50%;border-left:<?php echo $this->_tpl_vars['yearpixels'][$this->_tpl_vars['key']]['minor']; ?>
px solid #<?php echo $this->_tpl_vars['pixelcolors']['minor']; ?>
"></div>
		</div>
		<?php endif; ?>
	</td>
</tr>
<?php endforeach; endif; unset($_from); ?>	
</table>
</div>
<?php endif; ?>

<?php if ($this->_tpl_vars['linegraph'] != ""): ?>
<h3><?php echo $this->_config[0]['vars']['linegraph']; ?>
 &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'linegraph' )">show/hide</a>]</span></h3>
<div id="linegraph">
<script type="text/javascript" src="//toolserver.org/~soxred93/articleinfo/amline/amline/swfobject.js"></script>
<div id="flashcontent">
	<strong><?php echo $this->_config[0]['vars']['upgrade']; ?>
</strong>
</div>

<script type="text/javascript">
	// <![CDATA[		
	var so = new SWFObject("//toolserver.org/~soxred93/articleinfo/amline/amline/amline.swf", "amline1", "760", "500", "8", "#D0E4EE");
	so.addVariable("path", "//toolserver.org/~soxred93/articleinfo/amline/amline/");
		so.addVariable("data_file", escape("//toolserver.org/~soxred93/articleinfo/data/<?php echo $this->_tpl_vars['linegraphdata']; ?>
.xml"));
	so.addVariable("settings_file", escape("//toolserver.org/~soxred93/articleinfo/amline/amline/amline_settings_w.xml"));

	so.write("flashcontent");
	// ]]>
</script>
</div>
<?php endif; ?>

<?php if ($this->_tpl_vars['monthgraph'] != ""): ?>
<h3><?php echo $this->_config[0]['vars']['monthcounts']; ?>
 &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'monthcounts' )">show/hide</a>]</span></h3>
<div id="monthcounts">
<table class="months wikitable">
<?php $_from = $this->_tpl_vars['yearcounts']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['key'] => $this->_tpl_vars['val']):
?>
<tr>
<th><?php echo $this->_config[0]['vars']['month']; ?>
</th>
<th><?php echo $this->_config[0]['vars']['count']; ?>
</th>
<th><?php echo $this->_config[0]['vars']['ips']; ?>
</th>
<th><?php echo $this->_config[0]['vars']['minor']; ?>
</th>
<th><?php echo $this->_config[0]['vars']['graph']; ?>
 &mdash; <span style="background-color:#<?php echo $this->_tpl_vars['pixelcolors']['anon']; ?>
;border: 1px solid #000;padding: 0 0.3em 0 0.3em;"><?php echo $this->_config[0]['vars']['ips']; ?>
</span> &mdash; <span style="background-color:#<?php echo $this->_tpl_vars['pixelcolors']['minor']; ?>
;border: 1px solid #000;padding: 0 0.3em 0 0.3em;"><?php echo $this->_config[0]['vars']['minor']; ?>
</span> &mdash; <span style="background-color:#<?php echo $this->_tpl_vars['pixelcolors']['all']; ?>
;border: 1px solid #000;padding: 0 0.3em 0 0.3em;"><?php echo $this->_config[0]['vars']['alledits']; ?>
</span></th>
</tr>
	<?php $_from = $this->_tpl_vars['val']['months']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['month'] => $this->_tpl_vars['info']):
?>
		<tr>
			<td <?php if ($this->_tpl_vars['evenyears'][$this->_tpl_vars['key']] != ""): ?>style="background-color:#DEF;"<?php endif; ?> class="date"><?php echo $this->_tpl_vars['month']; ?>
/<?php echo $this->_tpl_vars['key']; ?>
</td>
			<td <?php if ($this->_tpl_vars['evenyears'][$this->_tpl_vars['key']] != ""): ?>style="background-color:#DEF;"<?php endif; ?>><?php echo $this->_tpl_vars['info']['all']; ?>
</td>
			<td <?php if ($this->_tpl_vars['evenyears'][$this->_tpl_vars['key']] != ""): ?>style="background-color:#DEF;"<?php endif; ?>><?php echo $this->_tpl_vars['info']['anon']; ?>
 (<?php echo $this->_tpl_vars['info']['pcts']['anon']; ?>
%)</td>
			<td <?php if ($this->_tpl_vars['evenyears'][$this->_tpl_vars['key']] != ""): ?>style="background-color:#DEF;"<?php endif; ?>><?php echo $this->_tpl_vars['info']['minor']; ?>
 (<?php echo $this->_tpl_vars['info']['pcts']['minor']; ?>
%)</td>
			<td <?php if ($this->_tpl_vars['evenyears'][$this->_tpl_vars['key']] != ""): ?>style="background-color:#DEF;"<?php endif; ?>>
				<?php if ($this->_tpl_vars['info']['all'] != 0): ?>
				<div class="outer_bar" style="height:150%;background-color:#<?php echo $this->_tpl_vars['pixelcolors']['all']; ?>
;width:<?php echo $this->_tpl_vars['monthpixels'][$this->_tpl_vars['key']][$this->_tpl_vars['month']]['all']; ?>
px;">
					<div class="bar" style="height:50%;border-left:<?php echo $this->_tpl_vars['monthpixels'][$this->_tpl_vars['key']][$this->_tpl_vars['month']]['anon']; ?>
px solid #<?php echo $this->_tpl_vars['pixelcolors']['anon']; ?>
"></div>
					<div class="bar" style="height:50%;border-left:<?php echo $this->_tpl_vars['monthpixels'][$this->_tpl_vars['key']][$this->_tpl_vars['month']]['minor']; ?>
px solid #<?php echo $this->_tpl_vars['pixelcolors']['minor']; ?>
"></div>
				</div>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; endif; unset($_from); ?>
<?php endforeach; endif; unset($_from); ?>	
</table>
</div>
<?php endif; ?>

<?php if ($this->_tpl_vars['sizegraph'] != ""): ?>
<h3><?php echo $this->_config[0]['vars']['sizegraph']; ?>
 &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'sizegraph' )">show/hide</a>]</span></h3>
<div id="sizegraph">
<script type="text/javascript" src="//toolserver.org/~soxred93/articleinfo/amline/amline/swfobject.js"></script>
<div id="flashcontent2">
	<strong><?php echo $this->_config[0]['vars']['upgrade']; ?>
</strong>
</div>

<script type="text/javascript">
	// <![CDATA[		
	var so = new SWFObject("//toolserver.org/~soxred93/articleinfo/amline/amline/amline.swf", "amline2", "760", "500", "8", "#D0E4EE");
	so.addVariable("path", "//toolserver.org/~soxred93/articleinfo/amline/amline/");
	so.addVariable("data_file", escape("//toolserver.org/~soxred93/articleinfo/data/<?php echo $this->_tpl_vars['sizegraphdata']; ?>
.xml"));
	so.addVariable("settings_file", escape("//toolserver.org/~soxred93/articleinfo/amline/amline/samline_settings_w.xml"));

	so.write("flashcontent2");
	// ]]>
</script>
</div>
<?php endif; ?>

<?php if ($this->_tpl_vars['usertable'] != ""): ?>
<h3><?php echo $this->_config[0]['vars']['usertable']; ?>
 &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( 'usertable' )">show/hide</a>]</span></h3>
<div id="usertable">
<table class="months wikitable">
	<tr>
		<th><?php echo $this->_config[0]['vars']['user']; ?>
</th>
		<th><?php echo $this->_config[0]['vars']['count']; ?>
</th>
		<th><?php echo $this->_config[0]['vars']['minor']; ?>
</th>
		<th><?php echo $this->_config[0]['vars']['firstedit']; ?>
</th>
		<th><?php echo $this->_config[0]['vars']['lastedit']; ?>
</th>
		<th><?php echo $this->_config[0]['vars']['atbe']; ?>
</th>
		<th><?php echo $this->_config[0]['vars']['avgsize']; ?>
</th>
	</tr>
	<?php $_from = $this->_tpl_vars['userdata']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['user'] => $this->_tpl_vars['info']):
?>
		<?php if (in_array ( $this->_tpl_vars['user'] , $this->_tpl_vars['topteneditors'] )): ?>
			<tr>
				<td class="date"><a href="//<?php echo $this->_tpl_vars['url']; ?>
/wiki/User:<?php echo $this->_tpl_vars['info']['urlencoded']; ?>
"><?php echo $this->_tpl_vars['user']; ?>
</a> (<a href="//toolserver.org/~soxred93/pc/<?php echo $this->_tpl_vars['info']['urlencoded']; ?>
/<?php echo $this->_tpl_vars['lang']; ?>
/<?php echo $this->_tpl_vars['wiki']; ?>
"><?php echo $this->_config[0]['vars']['editcount']; ?>
</a>)</td>
				<td><?php echo $this->_tpl_vars['info']['all']; ?>
</td>
				<td><?php echo $this->_tpl_vars['info']['minor']; ?>
 (<?php echo $this->_tpl_vars['info']['minorpct']; ?>
%)</td>
				<td><?php echo $this->_tpl_vars['info']['first']; ?>
</td>
				<td><?php echo $this->_tpl_vars['info']['last']; ?>
</td>
				<td><?php echo $this->_tpl_vars['info']['atbe']; ?>
</td>
				<td><?php echo $this->_tpl_vars['info']['size']; ?>
 KB</td>
			</tr>
		<?php endif; ?>
	<?php endforeach; endif; unset($_from); ?>
</table>
</div>
<?php endif; ?>
