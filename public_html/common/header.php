<?php

// error_reporting(E_NONE);
// ini_set("display_errors", 0);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="//www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
	<head>
		<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
		<title><?php
		if( isset($main_title) ) {
			echo $main_title . " - X!'s Tools";
		}
		else {
			echo "X!'s Tools";
		}
		?></title>
		<?php echo "<link rel=\"stylesheet\" href=\"/xtools/common/style.css\" />"; ?>
	</head>
	<body>
		<div id="wrap">
			<div id="header">
				X!'s tools &middot; <a href="https://webchat.freenode.net/?channels=#xlabs">Bug reports</a>
			</div>
