<?php

$time = microtime( 1 );//Calculate time in microseconds to calculate time taken to execut

//error_reporting(E_ALL);
ini_set("display_errors", 1);

include( '/data/project/xtools/stats.php' );

$tool = 'IPCalc';
$surl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
if (isset($_GET['name'])) {
	addStat( $tool, $surl, $_SERVER['HTTP_REFERER'], $_SERVER['HTTP_USER_AGENT'] );//Stat checking
}
unset($tool, $surl);

//require_once( '/data/project/xtools/public_html/phptemp/PHPtemp.php' );
//require_once( '/data/project/xtools/public_html/phptemp/Language.php' );
require_once( "/data/project/xtools/public_html/Smarty/languages.class.php" );
require_once( "/data/project/xtools/public_html/Smarty/Smarty.class.php" );

require_once( '/data/project/xtools/public_html/sitenotice.php' );
require_once( '/data/project/xtools/public_html/ipcalc/base.php' );

//$phptemp = new PHPtemp( '/data/project/xtools/public_html/templates/main.tpl' );
//$content = new PHPtemp( '/data/project/xtools/public_html/ipcalc/templates/ipcalc.tpl' );
$phptemp = new Smarty();
$content = new Smarty();

$language = new Language( array( "en" ) );
$lang = $language->getLang();

$langlinks = $language->generateLangLinks();

$phptemp->config_load( '../../configs/' . $lang . '.conf', 'main' );
$content->config_load( '../../configs/' . $lang . '.conf', 'main' );
$phptemp->config_load( $lang . '.conf', 'ipcalc' );
$content->config_load( $lang . '.conf', 'ipcalc' );

//$phptemp->load_config( '/data/project/xtools/public_html/configs/'.$lang.'.conf', 'main' );
//$phptemp->load_config( '/data/project/xtools/public_html/ipcalc/configs/'.$lang.'.conf', 'ipcalc' );
//$content->load_config( '/data/project/xtools/public_html/ipcalc/configs/'.$lang.'.conf', 'ipcalc' );

$content->assign( "form", 'Welcome to X!\'s IP CIDR calculator!<br /><br />
	There are two ways to use this tool.
	<ol>
	<li>IP range: Enter a CIDR range into the box, in the format 0.0.0.0/0</li>
	<li>IP list: Enter a list of IPs into the box, separated by newlines.</li>
	</ol><br />
		<form action="//tools.wmflabs.org/xtools/ipcalc/index.php" method="get">
		<table>
		<tr>
		<td align="center">IP range: <input type="radio" name="type" value="range" '.(($_GET['type'] == "range")?'checked="checked"':'').' /></td>
		<td align="center">IP list: <input type="radio" name="type" value="list" '.(($_GET['type'] == "list")?'checked="checked"':'').' /></td>
		</tr>
		<tr>
		<td colspan="2" align="center"><textarea name="ips" rows="10" cols="40">'.@$_GET['ips'].'</textarea></td>
		</tr>
		<tr>
		<td align="center">Enable "fun stuff": <input type="checkbox" name="fun" '.((isset($_GET['fun']))?'checked="1"':'').' /></td>
		<td align="center"><input type="submit" /></td>
		</tr>
		</table>
		</form><br />' );

flush();

$cidr = $_GET['ips'];
$cidr = str_replace('\r\n','\n',$cidr);

if( !preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/', $cidr ) && $_GET['type'] == 'range' ) {
	toDie( "Not a valid CIDR range." );
}

//Start the calculation

if( $_GET['type'] == 'range' ) {
	$cidr_info = IPCalc::calcCIDR( $cidr );
}
elseif( $_GET['type'] == 'list' ) {
	preg_match_all( '/((((25[0-5]|2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.){3}((25[0-5]|2[0-4][0-9])|([0-1]?[0-9]?[0-9])){1})/', $cidr, $m );
	$cidr_info = IPCalc::calcRange( $m );

	$ips = array();

	foreach( $cidr_info['ips'] as $ip ) {
		$tmp = "<h3>{$ip['ip']}</h3>";

		$tmp .= "<ul>";/*'bin' => implode( '.', self::ip2bin( $ip ) ),
				'rdns' => gethostbyaddr( $ip ),
				'long' => ip2long( $ip ),
				'hex' => implode( '.', self::ip2hex( $ip ) ),
				'octal' => implode( '.', self::ip2oct( $ip ) ),
				'radians' => implode( '.', self::ip2rad( $ip ) ),
				'base64'*/

		$tmp .= "<li>Reverse DNS: {$ip['rdns']}</li>";
		$tmp .= "<li>Network address: {$ip['long']}</li>";
		$tmp .= "<li>Binary: {$ip['bin']}</li>";

		if( isset( $_GET['fun'] ) ) {
			$tmp .= "<li>Hexadecimal: {$ip['hex']}</li>";
			$tmp .= "<li>Octal: {$ip['octal']}</li>";
			$tmp .= "<li>Radians: {$ip['radians']}</li>";
			$tmp .= "<li>Base 64: {$ip['base64']}</li>";
			$tmp .= "<li>Letters: {$ip['alpha']}</li>";
		}
		$tmp .= "<li>More info: " .
			"<a href=\"//ws.arin.net/whois/?queryinput={$ip['ip']}\">WHOIS</a> · " .
			"<a href=\"//toolserver.org/~luxo/contributions/contributions.php?user={$ip['ip']}\">Global Contribs</a> · " .
			"<a href=\"//www.robtex.com/rbls/{$ip['ip']}.html\">RBLs</a> · " .
			"<a href=\"//www.dnsstuff.com/tools/tracert.ch?ip={$ip['ip']}\">Traceroute</a> · " .
			"<a href=\"//www.infosniper.net/index.php?ip_address={$ip['ip']}\">Geolocate</a> · " .
			"<a href=\"//toolserver.org/~overlordq/scripts/checktor.fcgi?ip={$ip['ip']}\">TOR</a> · " .
			"<a href=\"//www.google.com/search?hl=en&q={$ip['ip']}\">Google</a> · " .
			"<a href=\"//ws.arin.net/whois/?queryinput={$ip['ip']}\">WHOIS</a>"
			."</li>";

		$tmp .= "</ul>";

		$ips[] = $tmp;
	}

	$content->assign( "list", implode( '', $ips ) );
}
else {
	toDie( 'Invalid type selected.' );
}

$content->assign( "showstats", "1" );
$content->assign( "cidr", "{$cidr_info['begin']}/{$cidr_info['suffix']}" );
$phptemp->assign( "page", "{$cidr_info['begin']}/{$cidr_info['suffix']}" );
$content->assign( "ip_start", $cidr_info['begin'] );
$content->assign( "ip_end", $cidr_info['end'] );
$content->assign( "ip_number", $cidr_info['count'] );




//Calculate time taken to execute
$exectime = number_format(microtime( 1 ) - $time, 2, '.', '');
$phptemp->assign( "excecutedtime", "Executed in $exectime seconds" );
$phptemp->assign( "memory", "Taken ". number_format((memory_get_usage() / (1024 * 1024)), 2, '.', '')." megabytes of memory to execute." );

$phptemp->assign( "curlang", $lang );
$phptemp->assign( "langlinks", $langlinks );
$phptemp->assign( "source", "ipcalc" );
assignContent();

//Access to the wiki
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

//If there is a failure, do it pretty.
function toDie( $msg ) {
	global $content;
	//echo $msg;
	//include( '/data/project/xtools/public_html/common/footer.php' );
	//die();
	$content->assign( "error", $msg );
	assignContent();
}

//Debugging stuff
function pre( $array ) {
	echo "<pre>";
	print_r( $array );
	echo "</pre>";
}

function assignContent() {
	global $phptemp, $content;
	$phptemp->assign( "content", $content->fetch( 'ipcalc.tpl' ) );
	$phptemp->display( '../../templates/mainSmarty.tpl' );
	die();
}
