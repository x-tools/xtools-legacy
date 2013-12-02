<?php
mb_internal_encoding("utf-8"); 
?>
<html>
<head>
<title>Translation tool enterance</title>
</head>
<body><?php

error_reporting(E_ALL);
ini_set("display_errors", 1);




if( isset( $_POST['password'] ) && $_POST['password'] == "arghyry3738" )  {
	parse_ini_file('/data/project/xtools/public_html/pcount/configs/' . $_POST['lang'] . '.conf');
    file_put_contents( '/data/project/xtools/public_html/pcount/configs/' . $_POST['lang'] . '.conf', html_entity_decode( $_POST['stuff'], ENT_QUOTES, 'UTF-8' ) );
    echo "Done. See <a href='//tools.wmflabs.org/xtools/pcount/configs/" . $_POST['lang'] . ".conf'>xtools/pcount/configs/" . $_POST['lang'] . ".conf</a>";
}
else {
    ?>
    
    <form action="translate2.php" method="post" accept-charset="utf-8">
    <textarea name="stuff" rows="30" cols="60" ></textarea><br />
    <input type="password" name="password" value="<?php echo $_GET['passu']; ?>" /> (pass)<br />
    <input type="text" name="lang" value="<?php echo $_GET['uselang']; ?>" /> (lang)<br />
    <input type="submit" />
    </form>
    
    <?php
}

?>
</body>
</html>