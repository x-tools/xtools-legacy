<?php
  /*
  function db_user_data()
  {
    $ts_pw = "";
    $ts_mycnf = parse_ini_file("replica.my.cnf");
    $db = mysqli_connect('127.0.0.1', $ts_mycnf['user'], $ts_mycnf['password'], "s51187_xtools", 8889) or die("database connection failed: " . mysql_error());
    unset($ts_mycnf, $ts_pw);
    mysqli_query($db, "SET NAMES 'utf8';");
    return $db;  
  }
  
  function db_enwiki()
  {
    $ts_pw = "";
    $ts_mycnf = parse_ini_file("replica.my.cnf");
    $db = mysqli_connect('127.0.0.1', $ts_mycnf['user'], $ts_mycnf['password'], "enwiki_p", 8888) or die("database connection failed: " . mysql_error());
    unset($ts_mycnf, $ts_pw);
    return $db; 
  }   */
  function db_enwiki()
  {
    $ts_pw = posix_getpwuid(posix_getuid());
    $ts_mycnf = parse_ini_file("/data/project/xtools/replica.my.cnf");
    $db = mysqli_connect('enwiki.labsdb', $ts_mycnf['user'], $ts_mycnf['password'], "enwiki_p") or die("database connection failed: " . mysql_error());
    unset($ts_mycnf, $ts_pw);
    return $db;  
  }  
  
  function db_wiki($wiki)
  {
    $ts_pw = posix_getpwuid(posix_getuid());
    $ts_mycnf = parse_ini_file("/data/project/xtools/replica.my.cnf");
    $db = mysqli_connect($wiki . '.labsdb', $ts_mycnf['user'], $ts_mycnf['password'], $wiki."_p" ) or die("database connection failed: " . mysql_error());
    unset($ts_mycnf, $ts_pw);
    return $db;    
  }
  
  function db_user_data()
  {
    $ts_pw = posix_getpwuid(posix_getuid());
    $ts_mycnf = parse_ini_file("/data/project/xtools/replica.my.cnf");
    $db = mysqli_connect('tools-db', $ts_mycnf['user'], $ts_mycnf['password'], "s51187_xtools") or die("database connection failed: " . mysql_error());
    unset($ts_mycnf, $ts_pw);
    mysqli_query($db, "SET NAMES 'utf8';");
    return $db;  
  } 

?>
