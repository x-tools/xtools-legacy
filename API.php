<?php

class API {

    private $mFormats = array(
        'json',
        'jsonfm',
        'php',
        'phpfm',
        'xml',
        'xmlfm',
        'yaml',
        'yamlfm',
        'txt',
        'txtfm',
        'dbg',
        'dbgfm'
    );
    
    private $mHeaders = array(
        'xml' => 'text/xml',
        'json' => 'application/json',
        'callback' => 'text/javascript',
        'yaml' => 'application/yaml',
        'php' => 'application/x-httpd-php',
        'txt' => 'text/plain',
        'dbg' => 'text/text',
        'default' => 'text/html',
    );
    
    private $wgUrlProtocols = array(
        'http://',
        'https://',
        'ftp://',
        'irc://',
        'gopher://',
        'telnet://',
        'nntp://',
        'worldwind://',
        'mailto:',
        'news:',
        'svn://',
    );
    
    private $mDefaultFormat = 'xml';
    
    private $mFormat;
    
    private $mIsHtml;
    
    private $mCallback = false;
    
    public function getFormats() {
        return $this->mFormats;
    }
    
    public function getProp( $default = array() ) {
        if( !isset( $_GET['prop'] ) ) {
            return $default;
        }
        return explode( "|", $_GET['prop'] );
    }
    
    public function getFormat() {
        global $_GET, $_POST;
        
        $req = array_merge( $_GET, $_POST );

        if( isset( $req['callback'] ) ) {
            $this->mCallback = $req['callback'];
        }
        
        if( isset( $req['format'] ) && in_array( $req['format'], $this->mFormats ) ) {
            $this->mIsHtml = (substr($req['format'], -2, 2) === 'fm'); // ends with 'fm'
            if ($this->mIsHtml) {
                $this->mFormat = substr($req['format'], 0, -2); // remove ending 'fm'
            }
            else {
                $this->mFormat = $req['format'];
            }
            return $req['format'];
        }
        else {
            $this->mFormat = $this->mDefaultFormat;
            $this->mIsHtml = true;
            return $this->mFormat;
        }

    }
    
    public function setHeaders() {
        if( isset( $this->mHeaders[$this->mFormat] ) && $this->mIsHtml == false ) {
            if( $this->mCallback ) {
                $mime = $this->mHeaders['callback'];
            }
            else {
                $mime = $this->mHeaders[$this->mFormat];
            }
        }
        else {
            $mime = $this->mHeaders['default'];
        }
        
        header("Content-Type: $mime; charset=utf-8");
        
        if( $this->mIsHtml ) {
            echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"><html>
<head>
    <title>API Result</title>
</head>
<body>
<br/>
<small>
You are looking at the HTML representation of the ".strtoupper($this->mFormat)." format.<br/>
HTML is good for debugging, but probably is not suitable for your application.<br/>
</small>
<pre>";
        }
    }
    
    public function showArray( $array ) {
        if( $this->mFormat != 'xml' ) {
            $array = $this->removeElementKey( '_element', $array );
        }
        
        switch($this->mFormat) {
            case 'json':
                $prefix = $suffix = "";
                if(!is_null($this->mCallback)) {
                    $prefix = preg_replace("/[^][.\\'\\\"_A-Za-z0-9]/", "", $this->mCallback ) . "(";
                    $suffix = ")";
                }
                
                $this->outputText( $prefix . json_encode( $array ) . $suffix );
                break;
            case 'php':
                $this->outputText( serialize( $array ) );
                break;
            case 'xml':
                $this->outputText('<?xml version="1.0"?>');
                $this->outputText(ArrayToXML::recXmlPrint('api', 
                        $array, 
                        $this->mIsHtml ? -2 : null));
                break;
            case 'yaml':
                require_once('/data/project/xtools/yaml.php');
                $this->outputText( Spyc :: YAMLDump($array) );
                break;
            case 'txt':
                $this->outputText( print_r( $array, true ) );
                break;
            case 'dbg':
                $this->outputText( var_export( $array, true ) );
                break;
        }

        if( $this->mIsHtml ) {
            echo "\n</pre>";
        }
    }
    
    private function removeElementKey( $torem, $array ) { 
        foreach( $array as $key => $val ) {
            if( is_array( $val ) ) {
                $array[$key] = $this->removeElementKey( $torem, $val );
            }
            elseif( $torem === $key ) {
                unset( $array[$torem] );
            }
        }
        
        return $array;
    }
    
    private function outputText($text) {
        if ($this->mIsHtml) {
            echo $this->formatHTML($text);
        } else {
            echo $text;
        }
    }
    
    private function formatHTML($text) {
        // Escape everything first for full coverage
        $text = htmlspecialchars($text);

        // encode all comments or tags as safe blue strings
        $text = preg_replace('/\&lt;(!--.*?--|.*?)\&gt;/', '<span style="color:blue;">&lt;\1&gt;</span>', $text);
        // identify URLs
        $protos = implode("|", $this->wgUrlProtocols);
        # This regex hacks around bug 13218 (&quot; included in the URL)
        $text = preg_replace("#(($protos).*?)(&quot;)?([ \\'\"<>\n]|&lt;|&gt;|&quot;)#", '<a href="\\1">\\1</a>\\3\\4', $text);
        // identify requests to api.php
        $text = preg_replace("#api\\.php\\?[^ \\()<\n\t]+#", '<a href="\\0">\\0</a>', $text);

        return $text;
    }
    
    

}

class ArrayToXML
{
    public static function recXmlPrint($elemName, $elemValue, $indent, $doublequote = false) {
        $retval = '';
        if (!is_null($indent)) {
            $indent += 2;
            $indstr = "\n" . str_repeat(" ", $indent);
        } else {
            $indstr = '';
        }
        $elemName = str_replace(' ', '_', $elemName);

        switch (gettype($elemValue)) {
            case 'array' :
                if (isset ($elemValue['*'])) {
                    $subElemContent = $elemValue['*'];
                    if ($doublequote)
                        $subElemContent = self::encodeAttribute($subElemContent);
                    unset ($elemValue['*']);
                    
                    // Add xml:space="preserve" to the
                    // element so XML parsers will leave
                    // whitespace in the content alone
                    $elemValue['xml:space'] = 'preserve';
                } else {
                    $subElemContent = null;
                }

                if (isset ($elemValue['_element'])) {
                    $subElemIndName = $elemValue['_element'];
                    unset ($elemValue['_element']);
                } else {
                    $subElemIndName = null;
                }

                $indElements = array ();
                $subElements = array ();
                foreach ($elemValue as $subElemId => & $subElemValue) {
                    if (is_string($subElemValue) && $doublequote)
                        $subElemValue = self::encodeAttribute($subElemValue);
                    
                    if (gettype($subElemId) === 'integer') {
                        $indElements[] = $subElemValue;
                        unset ($elemValue[$subElemId]);
                    } elseif (is_array($subElemValue)) {
                        $subElements[$subElemId] = $subElemValue;
                        unset ($elemValue[$subElemId]);
                    }
                }

                if (is_null($subElemIndName) && count($indElements))
                    die("($elemName, ...) has integer keys without _element value. Use ApiResult::setIndexedTagName().");

                if (count($subElements) && count($indElements) && !is_null($subElemContent))
                    die("($elemName, ...) has content and subelements");

                if (!is_null($subElemContent)) {
                    $retval .= $indstr . self::element($elemName, $elemValue, $subElemContent);
                } elseif (!count($indElements) && !count($subElements)) {
                        $retval .= $indstr . self::element($elemName, $elemValue);
                } else {
                    $retval .= $indstr . self::element($elemName, $elemValue, null);

                    foreach ($subElements as $subElemId => & $subElemValue)
                        $retval .= self::recXmlPrint($subElemId, $subElemValue, $indent);

                    foreach ($indElements as $subElemId => & $subElemValue)
                        $retval .= self::recXmlPrint($subElemIndName, $subElemValue, $indent);

                    $retval .= $indstr . self::closeElement($elemName);
                }
                break;
            case 'object' :
                // ignore
                break;
            default :
                $retval .= $indstr . self::element($elemName, null, $elemValue);
                break;
        }
        return $retval;
    }
    
    
    public static function encodeAttribute( $text ) {
        $encValue = htmlspecialchars( $text, ENT_QUOTES );

        // Whitespace is normalized during attribute decoding,
        // so if we've been passed non-spaces we must encode them
        // ahead of time or they won't be preserved.
        $encValue = strtr( $encValue, array(
            "\n" => '&#10;',
            "\r" => '&#13;',
            "\t" => '&#9;',
        ) );

        return $encValue;
    }
    
    public static function element( $element, $attribs = null, $contents = '', $allowShortTag = true ) {
        $out = '<' . $element;
        if( !is_null( $attribs ) ) {
            $out .=  self::expandAttributes( $attribs );
        }
        if( is_null( $contents ) ) {
            $out .= '>';
        } else {
            if( $allowShortTag && $contents === '' ) {
                $out .= ' />';
            } else {
                $out .= '>' . htmlspecialchars( $contents ) . "</$element>";
            }
        }
        return $out;
    }
    
    public static function closeElement( $element ) {
        return "</$element>";
    }

    /**
     * Given an array of ('attributename' => 'value'), it generates the code
     * to set the XML attributes : attributename="value".
     * The values are passed to Sanitizer::encodeAttribute.
     * Return null if no attributes given.
     * @param $attribs Array of attributes for an XML element
     */
    public static function expandAttributes( $attribs ) {
        $out = '';
        if( is_null( $attribs ) ) {
            return null;
        } elseif( is_array( $attribs ) ) {
            foreach( $attribs as $name => $val )
                $out .= " {$name}=\"" . self::encodeAttribute( $val ) . '"';
            return $out;
        } else {
            die( 'Expected attribute array, got something else in ' . __METHOD__ );
        }
    }
}
