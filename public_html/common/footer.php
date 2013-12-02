<?php

if( !function_exists('getUrl')) {
function getUrl($url) {
	$ch = curl_init();
    curl_setopt($ch,CURLOPT_MAXCONNECTS,100);
    curl_setopt($ch,CURLOPT_CLOSEPOLICY,CURLCLOSEPOLICY_LEAST_RECENTLY_USED);
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_MAXREDIRS,10);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,30);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,10);
    curl_setopt($ch,CURLOPT_HTTPGET,1);
    $data = curl_exec($ch);
    curl_close($ch);
    
    return $data;
}
}

?></td>
<td class="cont_td" style="width:25%;">
	<h2 class="table"><?php
	if (function_exists('wfMsg')) {
		echo wfMsg( 'navigation' );
	}
	else {
		echo 'Navigation';
	}
	?></h2>
	<h3>Stable tools</h3>
	<ul>
	<li>
	<a href="//tools.wmflabs.org/xtools/">Home</a>
	</li>
	<li>
	<a href="//tools.wmflabs.org/xtools/ec">Edit counter</a>
	</li>
	<li>
	<a href="//tools.wmflabs.org/xtools/bash">Random Quotes</a>
	</li>
	<li>
	<a href="//tools.wmflabs.org/xtools/editsummary">Edit summary counter</a>
	</li>
	<li>
	<a href="//tools.wmflabs.org/xtools/isAdmin">Is X an admin?</a>
	</li>
	<li>
	<a href="//tools.wmflabs.org/xtools/topedits">Top namespace edits</a>
	</li>
	<li>
	<a href="//tools.wmflabs.org/xtools/pages">Pages created</a>
	</li>
	<li>
	<a href="//tools.wmflabs.org/xtools/rfap">RfA Votes</a>
	</li>
	<li>
	<a href="//tools.wmflabs.org/xtools/autoedits">Automated edits</a>
	</li>
	<li>
	<a href="//tools.wmflabs.org/xtools/rangecontribs">CIDR range contribs</a> <b>(NEW TOOL)</b>
	</li>
	<li>
	<a href="//tools.wmflabs.org/xtools/sc">Quick edit counter</a> <b>(NEW TOOL)</b>
	</li>
	</ul>

	<ul>
	<li>
	<a href="https://webchat.freenode.net/?channels=#xlabs">Bug reports</a>
	</li>
	<?php 
	if (function_exists('outputSource')) {
		outputSource();
	}
	?>
	</ul>
	<hr />
	<br />
	
	<?php 
	/*if( function_exists( 'getUrl' ) ) {
		$data = getUrl('//toolserver.org/~vvv/status.php');
	}
	elseif( is_resource( $http ) ) {
		$data = $http->get('//toolserver.org/~vvv/status.php');
	}
	else {
		$data = "Unavailable";
	}
	
	if( $data != "Unavailable" ) {
		if (function_exists('wfMsg')) {
			echo wfMsg( 'status' );
		}
		else {
			echo 'MySQL status';
		}
		echo "<br />";
		$result = preg_match_all('/(s(\d)|sql):(.*);/', $data, $m);
		$s1 = $m[3][0];
		$s2 = $m[3][1];
		$s3 = $m[3][2];
		$sql = $m[3][3];
		
		echo "s1: $s1<br />";
		echo "s2: $s2<br />";
		echo "s3: $s3<br />";
		echo "sql: $sql<br />";
	}*/
	?>
	</li>
	</ul>
</td>
</tr>
</table>
</div>
			<div id="footer">
				 <img
        src="//www.w3.org/Icons/valid-xhtml10-blue"
        alt="Valid XHTML 1.0 Transitional" height="31" width="88" /> 
    <img style="border:0;width:88px;height:31px"
        src="//jigsaw.w3.org/css-validator/images/vcss-blue"
        alt="Valid CSS!" /> <img src="//tools.wmflabs.org/xtools/images/labs.png" width="88" height="31" alt="Wikimedia Toolserver banner" />
			</div>
		</div>
	</body>
</html>
