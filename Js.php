<?php
namespace Tpl;
class Js extends Template {
	const template = 'js';
	static protected $_instance;
	static protected $_type     = array( 'plain', 'include', 'ready' );
	static public    $libraries = array(
		'jquery'        => 'https://ajax.googleapis.com/ajax/libs/jquery/{1}/jquery.min.js',
		'jquery-ui'     => 'https://ajax.googleapis.com/ajax/libs/jqueryui/{1}/jquery-ui.min.js',
		//'jquery-ui-fx'  => 'jquery-ui.fx.js',
		'mootools'      => 'https://ajax.googleapis.com/ajax/libs/mootools/{1}/mootools-yui-compressed.js',
		'prototype'     => 'https://ajax.googleapis.com/ajax/libs/prototype/{1}/prototype.js',
		'extcore'       => 'https://ajax.googleapis.com/ajax/libs/ext-core/{3}/ext-core.js',
		'dojo'          => 'https://ajax.googleapis.com/ajax/libs/dojo/{1}/dojo/dojo.js',
		'scriptaculous' => 'https://ajax.googleapis.com/ajax/libs/scriptaculous/{1}/scriptaculous.js',
		'swfobject'     => 'https://ajax.googleapis.com/ajax/libs/swfobject/{2}/swfobject.js',
		'webfont'       => 'https://ajax.googleapis.com/ajax/libs/webfont/{1}/webfont.js',
		'chrome'        => 'https://ajax.googleapis.com/ajax/libs/chrome-frame/{1}/CFInstall.min.js'
	);
	public function __construct ( $template = self::template ) {
		parent::__construct( $template  );
	}
	static protected function _type ( &$code, $type = null ) {
		if ( $library = self::_library( $code, $type ) ) {
			$code = $library;
			return 'include';
		}
		if ( is_null( $type ) )
			$type = self::$_type[ (int) preg_match( '/\.js$/', $code ) ];
		elseif ( $type == 'ready' && ! self::$_instance->include->grep( '/(^|\/)jquery\W/' ) )
			self::$_instance->prepend( self::_library( 'jquery' ), 'include' );
		return $type;
	}
	static protected function _library ( $name, $version = null ) {
	//static protected function library ( &$name, $version = null ) {
		if ( isset( self::$libraries[ $name ] ) )
			return preg_replace( '/\{(\d)\}/', preg_match( '/^([\d]+\.?)+$/', $version ) ? $version : '$1', self::$libraries[ $name ] );
//		if ( ! isset( self::$libraries[ $name ] ) )
//			return;
//		$library = substr( $name , 0, strrpos( $name, '-' ) );
//		if ( $library && ! self::$_instance->include->grep( '/(^|\/)' . $library . '\W/' ) && self::_library( $library ) )
//			self::$_instance->append( $library, 'include' );
		//return $name = preg_replace( '/\{(\d)\}/', preg_match( '/^([\d]+\.?)+$/', $version ) ? $version : '$1', self::$libraries[ $name ] );
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
			$this->offsetSet( $name, in_array( $name, self::$_type ) ? Item::init() : new self() );
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
