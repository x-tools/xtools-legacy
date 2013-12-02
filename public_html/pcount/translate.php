<?php

error_reporting(E_ERROR);
ini_set("display_errors", 1);

require_once( '/data/project/xtools/public_html/phptemp/PHPtemp.php' );
require_once( '/data/project/xtools/public_html/phptemp/Language.php' );

$phptemp = new PHPtemp( '/data/project/xtools/public_html/templates/main.tpl' );
$content = new PHPtemp( '/data/project/xtools/public_html/pcount/templates/translate.tpl' );

$langs = glob("/data/project/xtools/public_html/pcount/configs/*.conf");

foreach( $langs as $k => $newlang ) {
	$langs[$k] = str_replace( array( '/data/project/xtools/public_html/pcount/configs/', '.conf' ), '', $newlang );
	if( $langs[$k] == "qqq" ) unset( $langs[$k] );
}

$language = new Language( $langs );
$lang = $language->getLang();

$langlinks = $language->generateLangLinks();

$phptemp->load_config( '/data/project/xtools/public_html/configs/en.conf', 'main' );
$phptemp->load_config( '/data/project/xtools/public_html/pcount/configs/'.$lang.'.conf', 'pcount' );
$content->load_config( '/data/project/xtools/public_html/pcount/configs/'.$lang.'.conf', 'pcount' );

require_once( '/data/project/xtools/public_html/counter_commons/Database.php' );
require_once( '/data/project/xtools/public_html/counter_commons/Email.php' );
require_once( '/data/project/xtools/database.inc' );
require_once( '/data/project/xtools/textdiff/textdiff.php' );

$wgDBPort = 3306;
$wgDBUser = $toolserver_username;
$wgDBPass = $toolserver_password;

/*$tdbr = new Database( 
	'sql-toolserver', 
	$wgDBPort, 
	$wgDBUser, 
	$wgDBPass, 
	'toolserver', 
	true
);*/


$res = Database::mysql2array($dbr->select(
	'wiki',
	'DISTINCT lang',
	array(
		array(
			'is_meta',
			'!=',
			'1'
		),
		array(
			'is_multilang',
			'!=',
			'1'
		),
		array(
			'is_closed',
			'!=',
			'1'
		)
	),
	array(
		'ORDER BY' => 'lang ASC'
	)
));

$langs = array();
foreach($res as $r) {
	$langs[] = $r['lang'];
}


$s = array_search( 'en-simple', $langs );

unset( $langs[$s] );
$langs[] = 'simple';

sort($langs);

$langs[] = 'qqq';

$dirs = null;

$phptemp->assign( "header", $phptemp->getConf('translation') );

if( isset( $_POST['title'] ) ) {
	$content->assign( "submitted", true );

	$diff = "[pcount]\n";
	foreach( $_POST as $key => $get ) {
		if( $key == "usedlanguage" ) continue;
		$diff .= $key . " = \"$get\"\n";
	}
	
	//$diff = utf8_encode( html_entity_decode( $diff ) );

	//echo  getTextDiff( 'unified', implode( "\n", file_get_contents( '/data/project/xtools/public_html/pcount/configs/'.$_GET['usedlanguage'].'.conf' ) ), $diff );
	if( is_file( '/data/project/xtools/public_html/pcount/configs/'.$_POST['usedlanguage'].'.conf' ) ) $diff .= "\n\n--------------\n\n" . getTextDiff( 'unified', file_get_contents( '/data/project/xtools/public_html/pcount/configs/'.$_POST['usedlanguage'].'.conf' ), trim( $diff ) );

	$email = new Email( "submitter@labs.org", "Translation Machine", "New translation in the {$_POST['usedlanguage']} language", $diff . "\n\n-----------\n\n//tools.wmflabs.org/xtools/pcount/translate2.php?passu=arghyry3738&uselang=" . $_POST['usedlanguage'] );
	$email->addTarget( "cybernet678@yahoo.com", "Cyberpwoer678" );
	$email->send();
}
elseif( ( !isset( $_GET['language'] ) || !in_array( $_GET['language'], $langs ) ) && !isset( $_GET['title'] ) ) {
	$select = null;
	
	foreach( $langs as $lang ) {
		if( $lang == "en" ) $select .= '<option value="en" selected="selected">en</option>';
		else $select .= "<option>$lang</option>";
		$select .= "\n";
	}
	$content->assign( "form", $select );
}
else {
	if( is_file('/data/project/xtools/public_html/pcount/configs/'.$_GET['language'].'.conf') ) {
		$ini_file = parse_ini_string( file_get_contents( '/data/project/xtools/public_html/pcount/configs/'.$_GET['language'].'.conf') );
	}
	else {
		$ini_file = parse_ini_file( '/data/project/xtools/public_html/pcount/configs/en.conf' );
	}
	$en_file = parse_ini_file( '/data/project/xtools/public_html/pcount/configs/en.conf' );
	$qqq_file = parse_ini_file( '/data/project/xtools/public_html/pcount/configs/qqq.conf' );
	
	$translationform = "<form action=\"//tools.wmflabs.org/xtools/pcount/translate.php\" method=\"post\"><table>";
	
	if( $_GET['language'] == "qqq" ) {
		foreach( $en_file as $ini_key => $ini_value ) {
			$translationform .= "<tr><td>" . $ini_key . "</td><td><input size=\"60\" type=\"text\" name=\"". $ini_key ."\" value=\"" . $qqq_file[$ini_key] . "\"></td><td></td></tr>";
		}
	}
	else {
		foreach( $ini_file as $ini_key => $ini_value ) {
			$explanation = "(Explanation: ".@$qqq_file[$ini_key].")";
			if( !$qqq_file[$ini_key] ) $explanation = '';
			$translationform .= "<tr><td>" . $ini_key . "</td><td><input size=\"60\" type=\"text\" name=\"". $ini_key ."\" value=\"" . $ini_value . "\"></td><td></td><td><span style=\"font-size: smaller;\"><span style=\"font-size: smaller;\">".$explanation."</span></span></td></tr>";
		}
	}
	
	$translationform .= "<tr><td colspan=\"3\"><input type=\"submit\" value=\"".$phptemp->getConf('submit')."\"></td></tr>";
	$translationform .= "</table>".'<input type="hidden" name="usedlanguage" value="'.$_GET['language'].'">'."</form>";
	$content->assign( "translationform", $translationform );
}

$phptemp->assign( "content", $content->display( true ) );
$phptemp->assign( "curlang", $lang );
$phptemp->assign( "langlinks", $langlinks );
//$phptemp->assign( "source", "count" );
$phptemp->display();

