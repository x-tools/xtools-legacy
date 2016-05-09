<?php
/* Config */
require_once 'demoBase.php';


/* Demonstration */

// 1) Init
$I18N = new Intuition( 'general' );

// 2) Do it

// Simple parentheses
echo $I18N->parentheses( 'hello' );

// Variables
echo '<br/>' . $I18N->msg( 'toolversionstamp', array(
	'variables' => array( '1.0', $I18N->dateFormatted( '2001-01-15' ) ),
) );

// msgExists
echo '<br/>msgExists: ';
var_dump(

	$I18N->msgExists( 'welcome' )

);
var_dump(

	$I18N->msgExists( 'foobar' )

);

// nonEmptyStr
echo '<br/>nonEmptyStr: ';
var_dump(

	IntuitionUtil::nonEmptyStr( 'one' )

);

// nonEmptyStrs
echo '<br/>nonEmptyStrs: ';
var_dump(

	IntuitionUtil::nonEmptyStrs( 'one', '', 'three' )

);
var_dump(

	IntuitionUtil::nonEmptyStrs( 'one', 'three' )

);

// GetAcceptableLanguages
$acceptLang = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
echo "<br/>getAcceptableLanguages: (default: \$_SERVER['HTTP_ACCEPT_LANGUAGE']: " .
	htmlspecialchars( $acceptLang ) .
	"):<br/>";
var_dump(

	IntuitionUtil::getAcceptableLanguages( $acceptLang )

);

$acceptLang = 'nl-be,nl;q=0.7,en-us,en;q=0.3';
echo "<br/>getAcceptableLanguages: ( '{$acceptLang}' ):<br/>";
var_dump(

	IntuitionUtil::getAcceptableLanguages( $acceptLang )

);


/* View source */
closeDemo( __FILE__ );
