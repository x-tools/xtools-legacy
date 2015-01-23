<?php
header('HTTP/1.1 301 Moved Permanently');
?><html>
<head>
<script type="text/javascript">
<!--
function delayer(){
    window.location = "//tools.wmflabs.org/xtools-articleinfo/index.php?<?php echo $_SERVER['QUERY_STRING']; ?>"
}
//-->
</script>
</head>
<body onLoad="setTimeout('delayer()', 5000)">
<h1>301 Moved Permanently</h1>
<p>This tool has moved to a new location.  You will be redirected to tools.wmflabs.org/xtools-articleinfo/index.php?<?php echo $_SERVER['QUERY_STRING']; ?> shortly.</p>

</body>
</html>