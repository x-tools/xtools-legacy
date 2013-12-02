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
 * PHPtemp object
 */
 
 /**
 * @package PHPtemp
 */
 
 /**
 * The templating class
 * All the functions in this class assume the object is either explicitly
 * loaded or filled. It is not load-on-demand. There are no accessors.
 */

class PHPtemp {
	/**
     * Parsed template
     * @var string
     */
	private $mOutput;
	
	/**
     * Configuration from the *.conf variable
     * @var array
     */
	private $mConfig;
	
	/**
     * Construct function, initiates the template.
     * @param string $templatefile The main template, the data that will be parsed
     * @return void
     */
	function __construct( $templatefile ) {
		if( !is_file( $templatefile ) ) {
			throw new Exception( "Could not load template file" );
		}
		$this->mOutput = file_get_contents( $templatefile );
	}
	
	/**
     * Loads a configuration file, holds language specific messages. Replaces {#these#} with the messages.
     * @param string $configfile Path to the configuration file
     * @param string $name The [name] at the top of the ini file
     * @todo Make $name do something
     * @return void
     */
	function load_config( $configfile, $name ) {
		if( !is_file( $configfile ) ) {
			throw new Exception( "Could not load configuration file " . $configfile );
		}
		if( !is_array($this->mConfig) ) {
			$this->mConfig = parse_ini_string( file_get_contents( $configfile ) );
		}
		else {
			$this->mConfig = array_merge( $this->mConfig, parse_ini_string( file_get_contents( $configfile ) ) );
		}
		
		
		foreach( $this->mConfig as $field => $value ) {
			$this->mOutput = str_ireplace( '{#'.$field.'#}', $value, $this->mOutput );
		}
	}
	
	/**
     * Replaces {$something$} with some string. Also parses the isset function
     * @param string $name Variable to change
     * @param string $value What to change it to. 
     * @return void
     */
	function assign( $name, $value ) {
		$this->mOutput = str_replace( '{$'.$name.'$}'."\n", $value, $this->mOutput );
		$this->mOutput = str_replace( '{$'.$name.'$}', $value, $this->mOutput );

		/*$this->mOutput = preg_replace( 
			'/\{&isset: ' . $name . ' &\}(.*?)\{&endisset&\}/msi',
			'$1',
			$this->mOutput
		);*/
		$this->mOutput = str_ireplace( '{&isset: '.$name.' &}', '', $this->mOutput );

		/*$this->mOutput = preg_replace( 
			'/\{&isnotset: (.*?) &\}(.*?)\{&endisnotset&\}/msi',
			'',
			$this->mOutput
		);*/
	}
	
	/**
     * Parse the remaining data, and return the final code
     * @param bool $noecho Whether it should return or echo the result
     * @return void|string Void if $noecho is false, string if it is true.
     */
	function display( $noecho = false ) {
		$this->parse( $output );
		if( $noecho == true ) return $output;
		echo $output;
	}
	
	/**
     * Parses the remaining functions, and removes any leftover issets.
     * @access private
     * @param string &$data Parsed data, call-by-reference
     * @return void
     */
	private function parse( &$data ) {
		$this->mOutput = preg_replace( 
			'/\{&isset: (.*?) &\}(.*?)\{&endisset&\}/msi',
			'',
			$this->mOutput
		);
		$this->mOutput = str_replace( '{&endisset&}', '', $this->mOutput );
		/*$this->mOutput = preg_replace( 
			'/\{&isnotset: ' . $name . ' &\}(.*?)\{&endisnotset&\}/msi',
			'$1',
			$this->mOutput
		);*/

		preg_match_all( '/\{&foreach: arr=\{\{(.*?)\}\} key=\{\^(.*?)\^\} val=\{\^(.*?)\^\}&}/i', $this->mOutput, $foreach_tag, PREG_SET_ORDER );

		foreach( $foreach_tag as $instance ) {
			$begintag = $instance[0];
			$arr = unserialize( $instance[1] );
			$key = $instance[2];
			$val = $instance[3];
			
			$tmp = explode( $begintag, $this->mOutput );
			$tmp = explode( "{&endforeach&}", $tmp[1] );
			$content = $tmp[0];
			unset($tmp);
			
			$parsed_array = null;
			foreach( $arr as $arr_key => $arr_val ) {
				$parsed_array .= str_replace( 
					array( 
						'{^'.$key.'^}', 
						'{^'.$val.'^}' 
					), 
					array( $arr_key, $arr_val ), 
					$content 
				) . "<br />\n";
			}
			
			$this->mOutput = str_replace( $content, $parsed_array, $this->mOutput );

			$this->mOutput = preg_replace( '/' . preg_quote( $begintag ) . '(.*)' . '\{&endforeach&\}/msi', '$1', $this->mOutput );
		}
		
		$this->mOutput = preg_replace( 
			'/\{&math: (.*?) &\}/ei',
			'$1',
			$this->mOutput
		);
		
		$this->mOutput = preg_replace( 
			'/\{&include: (.*?) &\}/ei',
			'include($1)',
			$this->mOutput
		);
		
		$this->mOutput = preg_replace( 
			'/\{&require: (.*?) &\}/ei',
			'require($1)',
			$this->mOutput
		);
		
		$this->mOutput = preg_replace( 
			'/\{&include_once: (.*?) &\}/ei',
			'include($1)',
			$this->mOutput
		);
		
		$this->mOutput = preg_replace( 
			'/\{&require_once: (.*?) &\}/ei',
			'require($1)',
			$this->mOutput
		);
		
		$data = $this->mOutput;
	}
	
	/**
     * Gets a certain config variable.
     * @param string $name Configuration variable to retrieve
     * @return string Config variable
     */
	function getConf( $name ) {
		$config = @$this->mConfig[$name];
		foreach( func_get_args() as $key => $arg ) {
			if( $arg == $name ) continue;
			$config = str_replace( '$'.($key), $arg, $config );
		}
		return $config;
	}
}
