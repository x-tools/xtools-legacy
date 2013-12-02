<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once( '/data/project/xtools/public_html/phptemp/PHPtemp.php' );
require_once( '/data/project/xtools/public_html/phptemp/Language.php' );

$phptemp = new PHPtemp( '/data/project/xtools/public_html/templates/main.tpl' );
$content = new PHPtemp( '/data/project/xtools/public_html/pcount/templates/source.tpl' );

$language = new Language( array('en') );
$lang = $language->getLang();

$langlinks = $language->generateLangLinks();

$phptemp->load_config( '/data/project/xtools/public_html/configs/'.$lang.'.conf', 'main' );
$phptemp->load_config( '/data/project/xtools/public_html/pcount/configs/'.$lang.'.conf', 'pcount' );
$content->load_config( '/data/project/xtools/public_html/configs/'.$lang.'.conf', 'main' );
$content->load_config( '/data/project/xtools/public_html/pcount/configs/'.$lang.'.conf', 'pcount' );

$dirs = array(
   'http' => '/data/project/xtools/public_html/counter_commons/HTTP.php',
   'database' => '/data/project/xtools/public_html/counter_commons/Database.php',
   'functions' => '/data/project/xtools/public_html/counter_commons/Functions.php',
   'counter' => '/data/project/xtools/public_html/pcount/counter.php',
   'index' => '/data/project/xtools/public_html/pcount/index.php',
   'Language' => '/data/project/xtools/public_html/phptemp/Language.php',
   'main.tpl' => '/data/project/xtools/public_html/templates/main.tpl',
   'pcount.tpl' => '/data/project/xtools/public_html/pcount/templates/pcount.tpl',
);

if( !isset( $_GET['path'] ) ) {
   $phptemp->assign( "header", $phptemp->getConf('sourceviewer') );
   $content->assign( "form", "true" );
}
elseif( !isset( $dirs[$_GET['path']] ) ) {
   $phptemp->assign( "header", $phptemp->getConf('sourceviewer') );
   $content->assign( "error", $phptemp->getConf('sourceviewer') );
   $content->assign( "form", "true" );
}
else {
   $pathname = $dirs[$_GET['path']];
   
   $phptemp->assign( "header", $phptemp->getConf('viewingsource', $pathname) );
   $content->assign( "source", str_replace( '<?php', '', highlight_file( $pathname, true ) ) );
   
}

$phptemp->assign( "content", $content->display( true ) );
$phptemp->assign( "curlang", $lang );
$phptemp->assign( "langlinks", $langlinks );
//$phptemp->assign( "source", "count" );
$phptemp->display();

