<?php
$newQS = "";
if ($_SERVER["QUERY_STRING"] != ""){
  if ( isset($_REQUEST["wiki"]) && isset($_REQUEST["lang"]) && isset($_REQUEST["name"]) ){
    $newQS = "?user=".$_REQUEST["name"]."&project=".$_REQUEST["lang"].".".$_REQUEST["wiki"];
  }
  if ( isset($_REQUEST["project"]) && isset($_REQUEST["user"]) ){
    $newQS = "?user=".$_REQUEST["user"]."&project=".$_REQUEST["project"];
  }
}
header("Status: 301 Moved Permanently");
header("Location: //tools.wmflabs.org/supercount/index.php".$newQS);
exit;
