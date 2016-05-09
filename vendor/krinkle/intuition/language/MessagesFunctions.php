<?php
/**
 * Functions for messages (PLURAL, etc.).
 * @see Intuition::msg()
 *
 * @copyright 2011-2014 See AUTHORS.txt
 * @license CC-BY 3.0 <https://creativecommons.org/licenses/by/3.0/>
 * @package intuition
 */

class MessagesFunctions {

	private static $instance = null;

	private $langIsLoaded = array();

	private $baseDir = null;

	private $I18N = null;

	private $langCode = null;

	private $msgFunctionRegex = "@\\{\\{(PLURAL|GENDER)\\:(.*?)\\|(.*?)\\}\\}@i";

	private $error = array();

	/**
	 *
	 * Get a instance of MessagesFunctions.
	 *
	 * @static
	 * @param String $baseDir The path of the root dir of TS-I18N
	 * @param Intuition $intuition
	 * @see Intuition::getMessagesFunctions()
	 */
	public static function getInstance( $baseDir, Intuition $intuition ) {
		if( self::$instance == null ) {
			self::$instance = new MessagesFunctions( $baseDir, $intuition );
			return self::$instance;
		} else {
			return self::$instance;
		}
	}

	/**
	 *
	 * Construct a new object of MessageFunctions.
	 *
	 * @param String $baseDir The path of the root dir of TS-I18N
	 * @param Intuition $intuition
	 */
	function __construct( $baseDir, $intuition ) {
		$this->baseDir = $baseDir;
		$this->I18N = $intuition;

		require_once $this->baseDir . '/language/mw-classes/Language.php';
	}

	/**
	 *
	 * Load a language class file from its code.
	 *
	 * @param String $language Language-Code
	 */
	private function loadLanguage( $language ) {
		$language = ucfirst( strtolower( str_replace( '-', '_', $language ) ) );

		if ( in_array( $language, $this->langIsLoaded ) ) {
			return;
		}

		$className = "Language".$language;

		if ( file_exists( $this->baseDir . '/language/mw-classes/' . $className . '.php' ) ) {
			include_once $this->baseDir . '/language/mw-classes/' . $className . '.php';
		}

		$this->langIsLoaded[] = $language;
	}

	/**
	 *
	 * Executed as the callback from parse()
	 * Runs the functions (PLURAL, etc.) for the message.
	 *
	 * @param array $matches the matches for the function
	 * @return string replaced message
	 */
	private function msgFunctionMatches( $matches ) {
		$functionName = strtolower( $matches[1] );
		$firstParameter = $matches[2];
		$parameters = explode( '|', $matches[3] );

		return $this->$functionName( $firstParameter, $parameters, $matches[0] );
	}

	/**
	 *
	 * Parsing a message.
	 *
	 * @param String $msg Message
	 * @param String $lang Language of the message
	 * @return String Parsed message
	 */
	public function parse( $msg, $lang ) {
		$this->langCode = $lang;
		$this->loadLanguage( $lang );

		$msg = preg_replace_callback(
			$this->msgFunctionRegex,
			array( $this, 'msgFunctionMatches' ), $msg
		);
		$this->sendParseErrors( __METHOD__ );

		return $msg;
	}

	private function plural( $number, $parameters, $msg ) {
		$language = ucfirst( strtolower( str_replace( '-', '_', $this->langCode ) ) );

		if ( $number == null || !is_numeric( $number ) ) {
			$this->addParseError( "Invalid number argument to {{PLURAL: ...}}",
				__METHOD__, E_ERROR, __FILE__, __LINE__ );
			return $msg;
		}

		$className = 'Language' . $language;

		if ( class_exists( $className ) ) {
			$langObj = new $className();
		} else {
			$langObj = new Language();
		}

		return $langObj->convertPlural( $number, $parameters );
	}

	private function gender( $user, $parameters, $msg ) {
		switch ( count( $parameters ) ) {
			case 0:
				$this->addParseError( "{{GENDER:}} with no variants" );
				return '';
			case 1:
				return $parameters[0];
			case 2:
				return IntuitionUtil::tag( $parameters[0], 'span', array(
						'class' => 'gender-male gender-neutral'
					) ) .
					IntuitionUtil::tag( $parameters[1], 'span', array(
						'class' => 'gender-female'
					) );
			default:
				$this->addParseError( "{{GENDER:}} given too many variants" );
			case 3:
				return IntuitionUtil::tag( $parameters[2], 'span', array(
						'class' => 'gender-neutral'
					) ) .
					IntuitionUtil::tag( $parameters[0], 'span', array(
						'class' => 'gender-male'
					) ) .
					IntuitionUtil::tag( $parameters[1], 'span', array(
						'class' => 'gender-female'
					) );
		}
	}

	private function addParseError( $errMsg, $context, $errType = E_WARNING, $file = '', $line = '' ) {
		$this->error[] = array( 'msg' => $errMsg, 'context' => $context,
			'type' => $errType, 'file' => $file, 'line' => $line
		);
	}

	private function sendParseErrors( $parseContext ) {
		if ( count( $this->error ) < 1 ) return;

		$this->I18N->errTrigger( 'Problems when parsing the message. For Information see below!',
			$parseContext, E_WARNING );

		foreach( $this->error as $error ) {
			$this->I18N->errTrigger( $error['msg'], $error['context'],
				$error['type'], $error['file'], $error['line']
			);
		}
	}
}
