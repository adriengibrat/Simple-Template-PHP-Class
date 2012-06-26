<?php
namespace Tpl;
class Style extends Item {
	public $media;
	public function __construct ( $media = null ) {
		$this->media = $media ?: 'all';
	}
	static public function init ( $media = null ) {
		return new self( $media );
	}
}
class Css extends Template {
	const template = 'css';
	public $media;
	static protected $_instance;
	static protected $_type  = array( 'plain', 'include', 'font' );
	static protected $_media = array(
		'tiny'    => 'screen and (max-device-width: 480px)',
		'phone'   => 'handheld, screen and (max-device-width: 640px), screen and (-webkit-min-device-pixel-ratio: 2)',
		'tablet'  => 'screen and (min-device-width: 641px) and (max-device-width: 960px)',
		'desktop' => 'screen and (min-device-width: 961px) and (max-device-width: 1280px)',
		'large'   => 'screen and (min-device-width: 1281px)',
		'print'   => 'print'
	);

	static public    $styles = array(
		'redbox'   => 'border: 1px solid red;',
		'greenbox' => 'border: 1px solid green;'
	);

	public function __construct ( $media = null, $template = self::template ) {
		$this->media = isset( self::$_media[ $media ] ) ? 
			self::$_media[ $media ] :
			'all';
		parent::__construct( $template  );
	}
	static protected function _type ( &$code, $type = null ) {
		if ( is_null( $type ) )
			$type = self::$_type[ (int) preg_match( '/\.css$/', $code ) ];
		elseif ( isset( self::$styles[ $code ] ) ) {
			$code = $type . '{' . self::$styles[ $code ] . '}'; 
			return 'plain';
		} elseif ( $type == 'font' ) {
			$code = 'http://fonts.googleapis.com/css?family=' .  $code;
			return 'include';
		} 
		return $type;
	}
	static public function init ( $code = null, $type = null ) {
		if ( ! self::$_instance )
			self::$_instance = new self();
		if ( is_null( $code ) )
			return self::$_instance;
		return ! $type || in_array( $type, self::$_type ) ?
			self::$_instance->append( $code, $type ) :
			self::$_instance->offsetGet( $type )->append( $code );
	}
	public function offsetGet ( $name ) {
		if ( ! $this->offsetExists( $name ) )
			$this->offsetSet( $name, in_array( $name, self::$_type ) ? Style::init( $this->media ) : new self( $name ) );
		return parent::offsetGet ( $name );
	}
	public function append ( $code, $type = null ) {
		$this->offsetGet( self::_type( $code, $type ) )->append( $code );
		return $this;
	}
	public function prepend ( $code, $type = null ) {
		$this->offsetGet( self::_type( $code, $type ) )->prepend( $code );
		return $this;
	}
}