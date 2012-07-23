<?php
function __autoload ( $class ) {
	if ( class_exists( $class, true ) )
		return true;
	$class = ltrim( $class, '\\' );
	$file  = '';
	if ( $separator = strripos( $class, '\\' ) ) {
		$namespace = substr( $class, 0, $separator );
		if ( $namespace != __NAMESPACE__ )
			$file  .= str_replace( '\\', DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
		$class = substr( $class, $separator + 1 );
	}
	$file .= str_replace( '_', DIRECTORY_SEPARATOR, $class ) . '.php';
	if ( ! @include_once( $file ) )
		return false;
	return true;
}
spl_autoload_register( __NAMESPACE__ . '\__autoload' );
class Data extends \ArrayObject {
	static public $filter;
	static public function filter ( $data ) {
		return is_callable( self::$filter ) ? call_user_func( self::$filter, $data ) : $data;
	}
	public function getArrayCopy () {
		return array_map( array( $this, 'filter' ), parent::getArrayCopy() );
	}
	public function exchangeArray ( $array ) {
		parent::exchangeArray( $array );
		return $this;
	}
	public function append ( $value ) {
		parent::append( $value );
		return $this;
	}
	public function prepend ( $value ) {
		return $this->exchangeArray( array_merge( array( $value ), (array) $this ) );
	}
	public function offsetGet ( $name ) {
		if ( $this->offsetExists( $name ) )
			return self::filter( parent::offsetGet( $name ) );
	}
	public function __set ( $name, $value ) {
		return $this->offsetSet( $name, $value );
	}
	public function __get ( $name ) {
		return $this->offsetGet( $name );
	}
	public function __unset ( $name ) {
		$this->offsetUnset( $name );
	}
	public function __isset ( $name ) {
		return $this->offsetExists( $name );
	}
	public function grep ( $pattern ) {
		return preg_grep( $pattern , (array) $this );
	}
}
class Item extends Data {
	protected $_indent;
	public function __construct () {}
	static public function init () {
		return new self();
	}
	public function getArrayCopy () {
		static $filter;
		if ( ! $filter )
			$filter = function ( $value ) {
				return $value instanceof Item ? (string) $value : $value;
			};
		return array_map( $filter, parent::getArrayCopy() );
	}
	public function __toString () {
		return $this->render();
	}
	static protected function _safe ( $value, $mode ) {
		if ( ! is_array( $value ) && ! $value instanceof Traversable )
			return htmlentities( $value, ENT_COMPAT, 'UTF-8' );
		foreach ( $value as $index => $item )
			$value[ $index ] = self::_safe( $item, $mode );
		return $value;
	}
	public function set ( $name, $value = false, $safe = false ) {
		if ( ! is_array( $name ) && ! $name instanceof Traversable )
			$this->offsetSet( (string) $name, $safe ? self::_safe( $value, $safe ) : $value );
		else
			foreach ( $name as $_name => $_value )
				$this->set( $_name, $_value, $value );
		return $this;
	}
	public function indent ( $indent = 1, $indentation = "\t" ) {
		$this->_indent = implode( '', array_fill( 0, $indent, $indentation ) );
		return $this;
	}
	protected function _indent ( $buffer ) {
		return preg_replace( '/(^|\r?\n|\r)(?!\s*$)/', '$1' . $this->_indent, $buffer );
	}
	protected function render () {
		return $this->_indent( implode( PHP_EOL , $this->getArrayCopy() ) );
	}
}
class Cache extends Item {
	protected $_id;
	static public $_cache = array();
	public function offsetSet ( $name, $value ) {
		$this->id( false );
		parent::offsetSet( $name, $value );
	}
	public function append ( $value ) {
		$this->id( false );
		return parent::append( $value );
	}
	public function exchangeArray ( $array ) {
		$this->id( false );
		return parent::exchangeArray( $array );
	}
	public function __toString () {
		return $this->cached( $this->id() ) ?: $this->cache();
	}
	protected function id ( $id = null ) {
		if ( $id === false )
			return $this->_id = null;
		if ( $id )
			return $id;
		return $this->_id ?: ( $this->_id = md5( serialize( $this ) ) );
	}
	public function indent ( $indent = 1, $indentation = "\t" ) {
		$this->id( false );
		return parent::indent( $indent, $indentation );
	}
	public function cache ( $id = null ) {
		return self::$_cache[ $this->id( $id ) ] = $this->render();
	}
	public function cached ( $id = null ) {
		if ( is_null( $id ) )
			return isset( self::$_cache[ $id = $this->id() ] ) ? $id : false;
		return isset( self::$_cache[ $id ] ) ? self::$_cache[ $id ] : false;
	}
}
class Tpl extends Cache {
	protected $_template;
	static public $path = './tpl';
	public function __construct ( $template = null ) {
		$this->_template = $this->_path( $template ?: 'helpers/debug' , self::$path );
	}
	static public function init ( $template = null ) {
		return new self( $template );
	}
	public function __toString () {
		return parent::cached( $this->id() ) ?: parent::cache();
	}
	public function __call ( $method, $arguments ) {
		$method =  __NAMESPACE__ . '\\' . preg_replace('/(^|_)([a-z])/uie', '"$1".strtoupper("$2")', $method );
		if ( class_exists( $method ) && is_callable( $method . '::init' ) )
			return call_user_func_array( $method . '::init', $arguments );
	}
	protected function _path ( $file, $path = null, $extention = '.phtml' ) {
		return implode( DIRECTORY_SEPARATOR, array(
			realpath( $path ?: sys_get_temp_dir() ),
			pathinfo( $file, PATHINFO_EXTENSION ) ? $file : $file . $extention
		) );
	}
	protected function render () {
		if ( ! is_readable( $this->_template ) )
			return ! trigger_error( 'Template ' . $this->_template . ' not found.' );
		extract( $this->getArrayCopy() );
		ob_start();
		include $this->_template;
		$buffer = ob_get_contents();
		ob_end_clean();
		return $this->_indent( $buffer );
	}
	public function cache ( $id = null, $path = null ) {
		self::$_cache[ $cache = $this->id( $id ) ] = $this->render();
		if ( $path !== false )
			if ( file_put_contents( $this->_path ( $cache, $path, '.html' ), self::$_cache[ $cache ] ) === false )
				trigger_error( 'Cache ' . $cache . ' not writable.' );
		return self::$_cache[ $cache ];
	}
	public function cached ( $id = null, $expire = 3600, $path = null ) {
		if ( is_null( $id ) )
			return isset( self::$_cache[ $id = $this->id() ] ) ? $id : false;
		if ( $expire === false )
			return isset( self::$_cache[ $id ] ) ? self::$_cache[ $id ] : false;
		$cache = $this->_path ( $id, $path, '.html' );
		if ( ! $mtime = @filemtime( $cache ) )
			return false;
		if( $mtime + $expire < time() )
			return @unlink( $cache ) && false;
		return file_get_contents( $cache );
	}
}
function Tpl ( $template = null ) {
	return new Tpl( $template );
}