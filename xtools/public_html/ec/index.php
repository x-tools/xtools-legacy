<?php
$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
header($protocol . ' 301 Moved Permanently');
$GLOBALS['http_response_code'] = 301;
header( 'Location: //tools.wmflabs.org/xtools-ec/index.php?'.$_SERVER['QUERY_STRING'] ) ;
?>