<?php

require('gradient_class.php');

$height = $_GET['size'];

if( isset( $_GET['random'] ) ) {
	$image = new gd_gradient_fill(500,$height,"random",$_GET['color1'],$_GET['color2']);
}
else {
	$image = new gd_gradient_fill(1,$height,"vertical",$_GET['color1'],$_GET['color2']);
}
