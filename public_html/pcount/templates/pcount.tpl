{&isset: error &}<br /><h2 class="alert">{$error$}</h2>{&endisset&}
{&isset: replag &}<br /><h2 class="alert">{$replag$}</h2>{&endisset&}
{&isset: nouser &}<br /><h2 class="alert">{#nouser#}</h2>{&endisset&}
{&isset: million &}<br /><h3 class="notice">{#million#}</h3>{&endisset&}

<script type="text/javascript">
var collapseCaption = '{#hide#}';
var expandCaption = '{#show#}';
</script>

{&isset: form &}
<br />
{#welcome#}
<br /><br />
<form action="http://tools.wmflabs.org/xtools/pcount/index.php" method="get">
<table>
<tr><td>{#username#}: </td><td><input type="text" name="name" /></td></tr>
<tr><td>{#wiki#}: </td><td><input type="text" value="{$form$}" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org</td></tr>
<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
</table>
</form><br /><hr />
{&endisset&}

{&isset: username &}<br />
<table>
<tr>
<td>{#username#}:</td><td><a href="http://{$url$}/wiki/User:{$usernameurl$}">{$username$}</a></td>
</tr>
{&endisset&}

{&isset: groups &}
<tr>
<td>{#groups#}:</td><td>{$groups$}</td>
</tr>
{&endisset&}

{&isset: firstedit &}
<tr>
<td>{#firstedit#}:</td><td>{$firstedit$}</td>
</tr>
{&endisset&}

{&isset: unique &}
<tr>
<td>{#unique#}:</td><td>{$unique$}</td>
</tr>
{&endisset&}

{&isset: average &}
<tr>
<td>{#average#}:</td><td>{$average$}</td>
</tr>
{&endisset&}

{&isset: live &}
<tr>
<td>{#live#}:</td><td>{$live$}</td>
</tr>
{&endisset&}

{&isset: deleted &}
<tr>
<td>{#deleted#}:</td><td>{$deleted$}</td>
</tr>
{&endisset&}

{&isset: total &}
<tr>
<td><b>{#total#}:</b></td><td><b>{$total$}</b></td>
</tr>
</table>
<br />
{&endisset&}

{&isset: namespacetotals &}
<h3>{#namespacetotals#}</h3>
<br />
<table>
<tr><td>
{$namespacetotals$}</td>
{&endisset&}

{&isset: graph &}

<td><div class="center">{$graph$}</div></td></tr></table>

{&endisset&}

{&isset: monthcounts &}

<h3>{#monthcounts#}</h3>
{$monthcounts$}
<br />

{&endisset&}

{&isset: nograph &}
<h3>{#monthcounts#}</h3>
{$nograph$}
<br />
{&endisset&}

{&isset: popup &}
<div id="popup"></div>
<script type="text/javascript"><!--

var pop = document.getElementById('popup');

var xoffset = 15;
var yoffset = 10;

document.onmousemove = function(e) {
  var x, y, right, bottom;
  
  try { x = e.pageX; y = e.pageY; } // FF
  catch(e) { x = event.x; y = event.y; } // IE

  right = (document.documentElement.clientWidth || document.body.clientWidth || document.body.scrollWidth);
  bottom = (window.scrollY || document.documentElement.scrollTop || document.body.scrollTop) + (window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || document.body.scrollHeight);

  x += xoffset;
  y += yoffset;

  if(x > right-pop.offsetWidth)
    x = right-pop.offsetWidth;
 
  if(y > bottom-pop.offsetHeight)
    y = bottom-pop.offsetHeight;
  
  pop.style.top = y+'px';
  pop.style.left = x+'px';

}

function popup(text) {
  pop.innerHTML = text;
  pop.style.display = 'block';
}

function popout() {
  pop.style.display = 'none';
}

//--></script>

{&endisset&}

{&isset: topedited &}

<h3>{#topedited#}</h3>
{$topedited$}
<br />

{&endisset&}

{&isset: notopedit &}

<h3>{#topedited#}</h3>
{#notopedit#}
<br />

{&endisset&}

{&isset: nograph &}
<h3>{#topedited#}</h3>
{$nograph$}
<br />
{&endisset&}
