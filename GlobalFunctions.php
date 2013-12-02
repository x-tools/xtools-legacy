<?php

function iin_array( $needle, $haystack ) {
    return in_array( strtoupper( $needle ), array_map( 'strtoupper', $haystack ) );
}

function in_string( $needle, $haystack ) {
    return strpos( $haystack, $needle ) !== false; 
}