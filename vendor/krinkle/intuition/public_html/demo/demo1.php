<?php
/* Config */
require_once 'demoBase.php';


/* Demonstration */

// 1) Init $I18N
$I18N = new Intuition( 'demo' /* name of domain */ );
$I18N->registerDomain( 'demo', __DIR__ . '/messages/demo' );

// 2) Get message
echo $I18N->msg( 'example' );


/* View source */
closeDemo( __FILE__ );
