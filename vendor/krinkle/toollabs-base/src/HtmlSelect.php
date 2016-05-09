<?php
/**
 * HtmlSelect class
 *
 * This file is inspired by MediaWiki's XmlSelect class.
 * https://svn.wikimedia.org/viewvc/mediawiki/trunk/phase3/includes/Xml.php?view=markup&pathrev=82840
 *
 * @author Timo Tijhof, 2012
 * @license Public domain
 * @package toollabs-base
 * @since v0.1.0
 */

define( 'HTMLSELECT_OPTION_NORMAL', 1 );
define( 'HTMLSELECT_OPTION_RAW', 2 );

class HtmlSelect {
	protected $options = array();
	protected $default = false;
	protected $attributes = array();

	public function __construct( $name = false, $id = false, $default = false ) {
		if ( $name ) {
			$this->setAttribute( 'name', $name );
		}
		if ( $id ) {
			$this->setAttribute( 'id', $id );
		}
		if ( $default !== false ) {
			$this->default = $default;
		}
	}

	public function setDefault( $default ) {
		$this->default = $default;
	}

	public function setAttribute( $name, $value ) {
		$this->attributes[$name] = $value;
	}

	public function getAttribute( $name ) {
		if ( isset($this->attributes[$name]) ) {
			return $this->attributes[$name];
		} else {
			return null;
		}
	}

	public function addOption( $value, $text = false, $return = HTMLSELECT_OPTION_NORMAL ) {
		$attribs = array( 'value' => $value );

		// Shortcut
		$text = ($text !== false) ? $text : $value;

		// Selected ?
		if ( $value === $this->default ) {
			$attribs['selected'] = 'selected';
		}

		$html = Html::element( 'option', $attribs, $text );
		if ( $return === HTMLSELECT_OPTION_RAW ) {
			return $html;
		}
		$this->options[] = $html;
	}

	// This accepts an array of form
	// value => text
	// optgrouplabel => ( value => text, value => text )
	public function addOptions( $options ) {
		$this->options[] = trim(self::formatOptions( $options ));
	}
	public function formatOptions( $options ) {
		$data = '';
		foreach( $options as $value => $text ) {
			if ( is_array( $text ) ) {
				$contents = self::addOptions( $text );
				$data .= Html::rawElement( 'optgroup', array( 'label' => $label ), $contents ) . "\n";
			} else {
				$data .= self::addOption( $value, $text, HTMLSELECT_OPTION_RAW ) . "\n";
			}
		}

		return $data;
	}

	public function getHTML() {
		return Html::rawElement( 'select', $this->attributes, implode( "\n", $this->options ) );
	}

}
