<?php

$messages = array();

$messages['en'] = array(
	'title'       => 'Edit Counter (version $1)',
	'navigation'  => 'Navigation',
	'viewsource'  => 'View source',
	'nowiki'      => '$1 is not a valid wiki',
	'welcome'     => 'Welcome to X!\'s edit counter!',
	'username'    => 'Username',
	'wiki'        => 'Wiki',
	'submit'      => 'Submit',
	'mysqlerror'  => 'MySQL error, please report to X! using <a href="//en.wikipedia.org/wiki/User:X!/Bugs">the bug reporter.</a> Be sure to report the following SQL error when reporting:<br /><pre>$1</pre>',
	'nosuchuser'  => '$1 does not exist.',
	'highreplag'  => 'Caution: Replag is high, changes newer than $1 may not be shown.',
	'mainspace'   => 'Article',
	'generalinfo' => 'General user info',
	'usergroups'  => 'User groups',
	'firstedit'   => 'First edit',
	'unique'      => 'Unique articles edited',
	'avgedits'    => 'Average edits per page',
	'total'       => 'Total edits (including deleted)',
	'deleted'     => 'Deleted edits',
	'live'        => 'Live edits',
	'format'      => 'number_format($1)',
	'nstotal'     => 'Namespace totals',
	'monthcounts' => 'Month counts',
	'logs'        => 'Logs',
	'ub'          => 'Users blocked',
	'urm'         => 'User rights modified',
	'ac'          => 'Accounts created',
	'pd'          => 'Pages deleted',
	'ppa'         => 'Pages patrolled',
	'ppr'         => 'Pages protected',
	'pr'          => 'Pages restored',
	'uu'          => 'Users unblocked',
	'pu'          => 'Pages unprotected',
	'fu'          => 'Files uploaded',
	'ur'          => 'Users renamed',
	'rg'          => 'Rights granted',
	'rr'          => 'Rights revoked',
	'pm'          => 'Pages moved',
	'topedited'   => 'Top edited articles',
	'executed'    => 'Executed in $1 seconds',
	'memory'      => 'Taken $1 megabytes of memory to execute.',
	'otherlang'   => 'View in other languages:',
	'incomplete'  => '(incomplete by $1 messages)',
	'helptrans'   => 'Want to help with translations? See <a href="//toolserver.org/~soxred93/count/translate.php">the translation page</a> for information on how to help.',
	'adminactions' => 'Actions',
	'nosql'       => 'X!\'s edit counter is down due to a MySQL problem. yarrow (the database server) is broken, and as such, there is no way to get results for the tool. This problem is common across all tools which access the database server. If you want just a plain number, you can get it from your preferences window. I apologize for any inconvenience.',
	'status'      => 'MySQL status',
	'w'           => 'w ',
	'd'           => 'd ',
	'h'           => 'h ',
	'm'           => 'm ',
	's'           => 's '
);

$canonical = array(
	'en' => 'English',
);

function wfMsg( $key ) {
    $args = func_get_args();
    array_shift( $args );
    return wfMsgReal( $key, $args );
}

function wfMsgReal( $key, $args ) {
    $message = wfMsgGetKey( $key );
    $message = wfMsgReplaceArgs( $message, $args );
    return $message;
}

function wfMsgGetKey( $key ) {
	global $messages;
	$lang = $_GET['lang'];
	if( empty($lang)) {
		$lang = $_GET['uselang'];
	}
    if ( isset($messages[$lang])) {
    	if ( isset($messages[$lang][$key])) {
    		return $messages[$lang][$key];
    	}
    	else {
    		return $messages['en'][$key];
    	}
    }
    else {
    	if ( isset($messages['en'][$key])) {
    		return $messages['en'][$key];
    	}
    	else {
    		toDie('Error with localization, please fill out a bug report using the link at right.');
    	}
    } 
}

function wfMsgReplaceArgs( $message, $args ) {
    # Fix windows line-endings
    # Some messages are split with explode("\n", $msg)
    $message = str_replace( "\r", '', $message );

    // Replace arguments
    if ( count( $args ) ) {
        if ( is_array( $args[0] ) ) {
            $args = array_values( $args[0] );
        }
        $replacementKeys = array();
        foreach( $args as $n => $param ) {
            $replacementKeys['$' . ($n + 1)] = $param;
        }
        $message = strtr( $message, $replacementKeys );
    }

    return $message;
}

?>
