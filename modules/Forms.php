<?php 

class xForms{

private static $namespaceOptions = '
		<option value="0">Main</option>
		<option value="1">Talk</option>
		<option value="2">User</option>
		<option value="3">User talk</option>
		<option value="4">Wikipedia</option>
		<option value="5">Wikipedia talk</option>
		<option value="6">File</option>
		<option value="7">File talk</option>
		<option value="8">MediaWiki</option>
		<option value="9">MediaWiki talk</option>
		<option value="10">Template</option>
		<option value="11">Template talk</option>
		<option value="12">Help</option>
		<option value="13">Help talk</option>
		<option value="14">Category</option>
		<option value="15">Category talk</option>
		<option value="100">Portal</option>
		<option value="101">Portal talk</option>
		<option value="108">Book</option>
		<option value="109">Book talk</option>
	';	
private static $usageText = array( 
	'ec' => '',
	'articleinfo' => '',
	'pages' => '',
	'topedits' => '',
	'autoedits' => '',
	'blame' => '',
	'rangecontribs' =>'
			<span>{#rc_usage_0#}</span>
			<ol>
			<li>{#rc_usage_1#} 0.0.0.0/0</li>
			<li>{#rc_usage_2#}</li>
			<li>{#rc_usage_3#}</li>
			</ol><br />
		',
	'autoblock' => '',
	'rfa' =>'
			<p style="line-height:1.5em;" >{#rfx_intro#} <br />
			&nbsp; &bull; <a href="//en.wikipedia.org/wiki/Wikipedia:Requests_for_adminship">Request for adminship</a> {$onEnWiki}. <br />
			&nbsp; &bull; <a href="//de.wikipedia.org/wiki/Wikipedia:Adminkandidaturen">Adminkandidatur</a> {$onDeWiki}. <br/>
			<p style="margin-top:0px;" >{#rfx_bureaucrat#}.</p>
			</p>
		',
	'rfap' => '',
	'sc' => '',
	'adminstats' => '',
	);

private static $templateForm = array(
		
'ec' => '
	<br />
	<form action="?" method="get">
		<table>
		<tr><td>{#username#}: </td><td><input type="text" name="user" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
		</table>
	</form><br />
	',
	
'articleinfo' => '
	<br />
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#article#}: </td><td><input type="text" name="article" /> <input type="checkbox" name="nofollowredir" /> {#nofollowredir#}</td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td>{#start#}: </td><td><input type="text" name="begin" /></td></tr>
		<tr><td>{#end#}: </td><td><input type="text" name="end" /></td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form><br />
	',

'pages' => '
	<br />
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#username#}: </td><td><input type="text" name="user" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td>{#namespace#}: </td><td><select name="namespace"><option value="all">-{#all#}-</option>{$nsoptions}</select><br /></td></tr>
		<tr><td>{#redirects#}:
			</td><td>
				<select name="redirects">
					<option value="none">{#redirfilter_none#}</option>
					<option value="onlyredirects">{#redirfilter_onlyredirects#}</option>
					<option value="noredirects">{#redirfilter_noredirects#}</option>
				</select><br />
			</td></tr>
		<tr>
			<td>inlude move/deletes</td>
			<td><input type="checkbox" name="movedeletes" value="1" /></td>
		</tr>
		<!--
		<tr><td>{#start#}: </td><td><input type="text" name="begin" /></td></tr>
		<tr><td>{#end#}: </td><td><input type="text" name="end" /></td></tr>
		-->
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form><br />
	',

'topedits' => '
	<br />
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#username#}: </td><td><input type="text" name="user" size="21" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td>{#namespace#}: </td><td><select name="namespace">{$nsoptions}</select> -{#or#}</td></tr>
		<tr><td>{#page#}<td><input type="text" name="article" size="40" /></td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form><br />
	',

'autoedits' => '
	<br />
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#username#}: </td><td><input type="text" name="user" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td>{#start#}: </td><td><input type="text" name="begin" /></td></tr>
		<tr><td>{#end#}: </td><td><input type="text" name="end" /></td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form>
	',

'blame' => '
	<br />
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#page#}: </td><td><input type="text" name="article" /> <input type="checkbox" name="nofollowredir" />{#nofollowredir#}</td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td>{#tosearch#}: </td><td><textarea name="text" rows="10" cols="40"></textarea></td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form>
	',
		
'rangecontribs' => '
	<br />
	<form action="?" method="get">
	<table>
		<tr>
			<td style="padding-left:5px" >Wiki:</td>
			<td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td>
		</tr>
		<tr></tr>
		<tr><td colspan=2 ><textarea name="ips" rows="10" cols="40"></textarea></td></tr>
		<tr>
			<td style="padding-left:5px" >{#limit#}:</td>
			<td>
			<select name="limit">
			<option value="5">5</option>
			<option selected value="20" >20</option>
			<option value="50">50</option>
			</select>
			</td>
		</tr>
		<tr><td style="padding-left:5px">{#start#}: </td><td><input type="text" name="begin" value="{$defaultBegin}" /></td></tr>
		<tr><td style="padding-left:5px">{#end#}: </td><td><input type="text" name="end" /></td></tr>
		<tr><td><input type="submit" value="{#submit#}"/></td></td></tr>
	</table>
	</form>
	',
		
'autoblock' => '
 	<br />
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#username#}: </td><td><input type="text" name="user" value="%"/></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form><br />
	',
		
'rfa' => '
	<br />
	<form method="get" action="?" >
		<table>
			<tr><td>{#wiki#}:</td>
			<td>
				<select onchange="submit()" name="project">
				{$optionsProject}
				</select></td></tr>
			</tr>
			<tr><td>{#rfx_page#}:&nbsp;</td><td><input type="text" name="page" size="50" value="{$defaultPage}" /></td></tr>
			<tr><td>{#or#}: </td><td>
				<select name="page2">
				{$optionsPage}
				</select></td></tr>
			<tr><td colspan=2><input type="submit" value="{#submit#}" /></td></tr>
		</table>
	</form><br />
	',

'rfap' => '
	<br />
	<form action="index.php" method="get">
		<table>
		<tr><td>{#wiki#}:</td>
		<td><select name="project">{$optionsProject}</select></td></tr></tr>
		<tr><td>{#username#}: </td><td><input type="text" name="user" /></td></tr>
		<tr><td colspan=2 ><input type="submit" value="{#submit#}"/></td></tr>
		</table>
	</form><br />
	',
		
'sc' => '
	<br />
	<form action="?" method="get">
		<table>
		<tr><td>{#username#}: </td><td><input type="text" name="user" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
		</table>
	</form><br />
	',
);


private static $tplNew = array(

'pre' => '
		<h3 style="margin-bottom: 0.4em">{$toolTitle}<small style="color:inherit"> &nbsp;&bull;&nbsp; {$toolDesc}</small></h3>
		<form class="form-horizontal" style="width:80%" action="?" method="get" accept-charset="utf-8" >
		<fieldset>
		<legend></legend>
	',
'username' => '
		<div class="input-group">
			<span class="input-group-addon form-label">{#username#}</span>
			<input type="text" class="form-control" value="{$defaultUser}" name="user">
			<span class="input-group-addon glyphicon glyphicon-info-sign tooltipcss"  >
				<span>
        			<img class="callout" src="../static/images/callout.png" />
        			Username or IPv4 or IPv6
    			</span>
			</span>
		</div>
	',
'usernameMult' => '
		<div class="input-group">
			<span class="input-group-addon form-label">{#username#}</span>
			<input type="text" class="form-control" value="{$defaultUser}" name="user">
			<span class="input-group-addon glyphicon glyphicon-info-sign tooltipcss"  >
				<span>
        			<img class="callout" src="../static/images/callout.png" />
        			<strong>Multiple Users allowed <br/>(combined with page):</strong><br />
        			eg: UserX|UserY|UserZ ...
    			</span>
			</span>
		</div>
	',
'page' =>'	
		<div class="input-group">
			<span class="input-group-addon form-label">{#page#}:</span>
			<input type="text" class="form-control" value="{$defaultPage}" name="article">
			<span class="input-group-addon">
        		<input type="checkbox" name="nofollowredir" /> {#nofollowredir#}
      		</span>
		</div>
	',
'pageselect' => '
		<div class="input-group">
			<span class="input-group-addon form-label">{#or#}</span>
			<select class="form-control" name="page2" >{$optionsPage}<select>
		</div>
	',
'project' => '
		<div class="input-group">
			<span class="input-group-addon form-label">{#wiki#}</span>
			<input type="text" class="form-control" value="{$project}" name="project">
			<span class="input-group-addon glyphicon glyphicon-info-sign tooltipcss"  >
				<span>
        			<img class="callout" src="../static/images/callout.png" />
        			<strong>Accepted formats :</strong><br />
        			enwiki or en.wikipedia or <br />http://de.wikipedia.org ...
    			</span>
			</span>
		</div>
	',
'projectselect' => '
		<div class="input-group">
			<span class="input-group-addon form-label">{#wiki#}</span>
			<select class="form-control" onchange="submit(\'bla\')" name="project" >{$optionsProject}<select>
		</div>
	',
'namespace' => '
		<div class="input-group">
			<span class="input-group-addon form-label">{#namespace#}</span>
			<select class="form-control" name="namespace">{$nsoptions}</select>
		</div>
	',
'redirect' => '
		<div class="input-group">
			<span class="input-group-addon form-label">{#redirects#}</span>
			<select class="form-control" name="redirects">
					<option value="none" >{#redirfilter_none#}</option>
					<option value="onlyredirects" >{#redirfilter_onlyredirects#}</option>
					<option value="noredirects" >{#redirfilter_noredirects#}</option>
			</select>
		</div>
	',
'begin' => '
		<div class="input-group">
			<span class="input-group-addon form-label">{#start#}</span>
			<input type="text" class="form-control" placeholder="<{#optional#}>" value="{$defaultBegin}" name="begin" >
			<span class="input-group-addon glyphicon glyphicon-info-sign" title="2014-12-31" ></span>
		</div>
	',
'end' => '
		<div class="input-group">
			<span class="input-group-addon form-label">{#end#}</span>
			<input type="text" class="form-control" placeholder="<{#optional#}>" name="end" >
			<span class="input-group-addon glyphicon glyphicon-info-sign" title="2014-12-31" ></span>
		</div>
	',
'text' => '
		<div class="input-group">
			<span class="input-group-addon form-label">{#tosearch#}</span>
			<textarea class="form-control" name="text" ></textarea>
		</div>
	',
'limit' => '
		<div class="input-group">
			<span class="input-group-addon form-label">{#limit#}</span>
			<select class="form-control" name="limit">
				<option value="5">5</option>
				<option selected value="20" >20</option>
				<option value="50">50</option>
			</select>
		</div>
	',
'post' => '		
		<br />
		<input class="btn btn-large btn-primary" type="submit" value="{#submit#}" />
		</fieldset>
		</form>
	',
);


	static function getTemplate ( $style, $type, $tool ){
		
		if ( $style == "old" || $style == "" ){
			if ( $type == "form" ){

				return self::$usageText[ $tool ] . str_replace('{$nsoptions}', self::$namespaceOptions, self::$templateForm[ $tool ] );
			}
		}
		else {
			$nsoptionAll = "";
			$ret = self::$tplNew["pre"] . self::$usageText[$tool];
			
			switch ( $tool ){
				case 'ec':
					$ret .= self::$tplNew["username"] . self::$tplNew["project"];
					break;
				case 'articleinfo':
					$ret .= self::$tplNew["page"] . self::$tplNew["project"];
					break;
				case 'pages':
					$nsoptionAll = '<option value="all">-{#all#}-</option>';
					$ret .= self::$tplNew["username"] . self::$tplNew["project"] . self::$tplNew["namespace"] . self::$tplNew["redirect"];
					break;
				case 'topedits':
					$ret .= self::$tplNew["usernameMult"] . self::$tplNew["project"] . self::$tplNew["namespace"] . self::$tplNew["page"];
					break;
				case 'autoedits':
					$ret .= self::$tplNew["username"] . self::$tplNew["project"] . self::$tplNew["begin"] . self::$tplNew["end"];
					break;
				case 'blame':
					$ret .= self::$tplNew["project"] . self::$tplNew["page"] . self::$tplNew["text"];
					break;
				case 'rangecontribs':
					$nsoptionAll = '<option value="all">-{#all#}-</option>';
					$ret .= self::$tplNew["project"] . self::$tplNew["text"] . self::$tplNew["begin"] . self::$tplNew["end"] . self::$tplNew["namespace"] . self::$tplNew["limit"];
					break;
				case 'autoblock':
					$ret .= self::$tplNew["username"] . self::$tplNew["project"];
					break;
				case 'rfa':
					$ret .= self::$tplNew["projectselect"] . self::$tplNew["page"] . self::$tplNew["pageselect"];
					break;
				case 'rfap':
					$ret .= self::$tplNew["projectselect"] . self::$tplNew["username"];
					break;
				case 'sc':
					$ret .= self::$tplNew["username"] . self::$tplNew["project"];
					break;
				case 'adminstats':
					$ret .= self::$tplNew["project"] . self::$tplNew["begin"] . self::$tplNew["end"];
					break;
			}
			$ret .= self::$tplNew["post"];
			
			$ret = str_replace('{$nsoptions}', $nsoptionAll.self::$namespaceOptions, $ret ) ;
			
			return '<div class="container" >' . $ret . '</div><br />';
		}
	}
}