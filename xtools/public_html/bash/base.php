<?php

class BashBase {

	var $quotes;

	public function __construct() {
		 
		$pgHTTP = new HTTP();

		$text = $pgHTTP->get('http://meta.wikimedia.org/w/index.php?title=IRC/Quotes&action=raw&ctype=text/css', false);
		
		$text = explode('<pre><nowiki>', $text);
		$text = explode('</nowiki></pre>', $text[1]);
		$text = explode('%%', $text[0]);
		$text = substr($text[0], 2);
		$text = htmlspecialchars($text);
		$text = trim($text);
		$text = preg_replace('/\n/', '<br />', $text);
		
		$this->quotes = explode("%<br />", $text);

	}
	
	public function getRandomQuote() {
		return $this->getQuoteFromId( rand( 0, count( $this->quotes ) ) );
	}
	
	public function getQuoteFromId( $id ) {
		if( isset( $this->quotes[$id - 1 ] ) ) {
			return array( 'quote' => $this->quotes[$id - 1], 'id' => $id );
		}
		else {
			if( $api ) return array( 'error' => 'noquote', 'info' => 'No quote found' );
		}
	}
	
	public function getAllQuotes() {
		$retArr = array();
		foreach( $this->quotes as $id => $quote ) {
			$retArr[ $id + 1 ] = $quote;
		}
		
		return $retArr;
	}
	
	public function getQuotesFromSearch( $search, $regex = false ) {
		$retArr = array();

		foreach( $this->quotes as $id => $quote ) {
			if( $regex ) {
				if( preg_match( $search, html_entity_decode( $quote ) ) ) {
					$retArr[ $id + 1 ] = $quote;
				}
			}
			else {
				if( in_string( $search, $quote, false ) === true ) {
					$retArr[ $id + 1 ] = $quote;
				} 
			}
		}
		
		return $retArr;
	}
	
}
