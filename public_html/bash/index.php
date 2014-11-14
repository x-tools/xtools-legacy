<?php

//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );
	require_once( 'base.php' );

//Load WebTool class
	$wt = new WebTool( 'Bash', 'bash', array() );
	$base = new BashBase();
	$wt->content = getPageTemplate( 'form' );
	
	$search = $wgRequest->getVal( 'search' );
	$isRegex = $wgRequest->getBool( 'regex' );

//Show form if &article parameter is not set (or empty)
	if( !$wgRequest->getVal( 'action' ) ) {
		$wt->showPage();
	}
	

	switch( $wgRequest->getVal( 'action' ) ) {
		case 'random':
			$quote = $base->getRandomQuote();
			
			$otherurl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
			$pageResult = '
				<h3>{#quote_number#} '.$quote['id'].'</h3>
				<pre>'.$quote['quote'].'</pre>
				<a href="'.$otherurl.'"> - {#more#} - </a>
			';
			
			break;
			
		case 'showall':
			$quotes = $base->getAllQuotes();
			
			$pageResult = '<h3>{#quote_showall#}</h3>';
			foreach ( $quotes as $id => $quote ){
				$pageResult .= '
						<h3>{#quote_number#} '.$id.'</h3>
						<pre>'.$quote.'</pre>';
			}
			
			break;
			
		case 'search':
			$quotes = $base->getQuotesFromSearch( $search, $isRegex );
			
			$pageResult = '<h3>{#searchresults#}</h3>';
			foreach ( $quotes as $id => $quote ){
				$pageResult .= '
						<h3>{#quote_number#} '.$id.'</h3>
						<pre>'.$quote.'</pre>';
			}
			if( !count( $quotes ) ) {
				$wt->toDie('noresult', $search ) ;
			}
			break;
			
		default:
			$wt->showPage();
	}

unset($base, $quotes);
$wt->content = $pageResult;
$wt->showPage();
	

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){ 

	$templateForm = '
	<br />		
	<form action="?" method="get" accept-charset="utf-8">
	<table class="">
	<tr>
	<td colspan="2"><input type="radio" name="action" value="random" checked="checked" />{#quote_random#}</td>
	</tr>
	<tr>
	<td colspan="2"><input type="radio" name="action" value="showall" />{#quote_showall#}</td>
	</tr>
	<tr>
	<td><input type="radio" name="action" value="search" />{#tosearch#}: <input type="text" name="search" /> <input type="checkbox" name="regex" />Regex</td>
	</tr>
	<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
}