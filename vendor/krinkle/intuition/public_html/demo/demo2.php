<?php
/* Config */
require_once 'demoBase.php';


/* Demonstration */

// Because 'suppressnotices' is true (default), this won't trigger a notice.
$I18N = new Intuition( array(
	'domain' => 'demo',
) );
echo $I18N->msg( 'foo' ) . '<br/>';

// Because 'suppressnotices' is false, this will trigger a "Notice: 'bar' undefined"
$I18N = new Intuition( array(
	'domain' => 'demo',

	// Show notices
	'suppressnotice' => false,
) );
echo $I18N->msg( 'bar' ) . '<br/>';

// Because 'suppressbrackets' is true, gthis will display "Quux" instead of "[quux]"
$I18N = new Intuition( array(
	'domain' => 'demo',

	// Hide any sign of an undefined message to the end-user
	'suppressbrackets' => true,
) );
echo $I18N->msg( 'quux' ) . '<br/>';

/* View source */
closeDemo( __FILE__ );
