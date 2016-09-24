<?php

  include("translations.inc.php");

  function getLang()
  {
    $lang_available = array("en", "de");
    $lang = "en";
  
    if (isset($_REQUEST['lang']))
      $lang = $_REQUEST['lang'];
    else if (isset($_COOKIE['wikihistory_lang']))
      $lang = $_COOKIE['wikihistory_lang'];
    else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
      // standard: from accept language header
      $lang = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);

    // remove country (like de_DE, en_US)
    if (strpos($lang, "_") > 0) $lang = substr($lang, 0, strpos($lang, "_"));  

    // only accept languages we have translations for
    if (!in_array($lang, $lang_available)) $lang = "en";
    
    // set cookie for 100 days
    setcookie("wikihistory_lang", $lang, time()+100*24*60*60, "/wikihistory/");
    
    return $lang;
  }

  function t($nr)
  {
    global $trans;
    static $lang = "";
    if ($lang == "") $lang = getLang(); 
    return $trans[$nr][$lang];
  }

  function show_flags()
  {
    $lang = getLang();
    $uri = $_SERVER['REQUEST_URI'];
    $uri = str_replace("&lang=$lang", "", $uri);
    $uri = str_replace("?lang=$lang", "", $uri);
    if (strpos($uri, "?") !== false) $uri .= "&"; else $uri .= "?";
    if ($lang == 'de')
    {  
      print ' 
      <a href="' . $uri . 'lang=en">en</a>
      <b>de</b>
      ';
    }
    else
    {
      print ' 
      <b>en</b>
      <a href="' . $uri . 'lang=de">de</a>
      ';
    } 
  }

?>
