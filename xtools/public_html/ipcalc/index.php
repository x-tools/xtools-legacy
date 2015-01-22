<?php 
//ipcalc -> rangecontribs
//$path = preg_replace('/^\/(.*)\/.*/', '\1', $_SERVER["REQUEST_URI"]); 
header("Status: 301 Moved Permanently");
header("Location: //tools.wmflabs.org/xtools/rangecontribs/index.php");
exit;