<?php

/*
 * This file is part of PHPTemp templating system <//urs.sf.net/>.
 *
 * PHPTemp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PHPTemp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PHPTemp.  If not, see <//www.gnu.org/licenses/>.
 */
 
 /**
 * @file
 * Language object
 */
 
 /**
 * @package PHPtemp
 */
 
 /**
 * The language class
 * All the functions in this class assume the object is either explicitly
 * loaded or filled. It is not load-on-demand. There are no accessors.
 */

class Language {
	/**
     * Language to display
     * @var array
     */
	private $mLang;
	private $mLanguages;
	private $mLanglinks;
	
	/**
     * Construct function, initiates the $lang variable.
     * @param array $list Array of possible languages
     * @return void
     */
	function __construct( $list, $useonlylang = true ) {
		$this->mLang = 'en';
		$this->mLanguages = $list;
		if( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) $this->setLang( $_SERVER['HTTP_ACCEPT_LANGUAGE'], $list );
		if( isset( $_COOKIE['soxred_lang'] ) && in_array( $_COOKIE['soxred_lang'], $list ) ) $this->setLang( $_COOKIE['soxred_lang'] );
		if( isset( $_REQUEST['lang'] ) && $useonlylang ) $this->setLang( $_REQUEST['lang'] );
		if( isset( $_REQUEST['uselang'] ) ) $this->setLang( $_REQUEST['uselang'] );
	}
	
	/**
     * Sets the $lang variable, just verifies that the given language is a valid choice.
     * @access private
     * @param string $lang Language to set
     * @return void
     */
	private function setLang( $lang ) {
		if( in_array( $lang, $this->mLanguages ) ) {
			$this->mLang = $lang;
			
			setcookie("soxred_lang", $this->mLang, time()+60*60*24*365);
		}
	}
	
	/**
     * Returns the set language.
     * @return string $lang variable
     */
	function getLang() {
		return $this->mLang;
	}
	
	/**
     * Generates a list of languages that aren't currently selected
     * @return string $langlinks variable
     */
	function generateLangLinks() {
		global $_SERVER;
		foreach( $this->mLanguages as $cur_lang ) {
			if( $cur_lang != $this->mLang ) {
			
				$url = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
				
				if( in_string( 'uselang', $url ) ) $url = preg_replace( '/uselang=(.*?)&?/', '', $url );
				if( in_string( '?', $url ) ) {
					$url = $url . "&uselang=".$cur_lang;
				}
				else {
					$url = $url . "?uselang=".$cur_lang;
				}

				
				
				$this->mLanglinks.="<a href=\"". $url."\">".$cur_lang."</a> ";
			}
		}
		
		return $this->mLanglinks;
	}
}
