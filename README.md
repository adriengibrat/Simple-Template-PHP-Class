## How to use it
Just include Tpl.php file, everything else is loaded dynamicly via PHP autoload magic ;)

```php
<?php
require 'Tpl.php';
echo new Tpl( 'layout' ) // Get new template from file ([path/]filename[.extention])
  ->set( 'title', 'My Test Page') // Set simple data like page title
  ->set( 'content', // Set complex data like another template
		Tpl( 'content' ) // Use new Tpl syntax or Tpl function alias
		->set( 'users', array( array( // content.phtml template is a "user" loop, give it an array of data
			'id'     => 1,
			'name'   => 'user name',
			'email'  => 'test@test.com',
			'banned' => true
		) ) )
		->indent( 2 ) // Indent the result for nice source ;)
	);
```
* Default template file extention is .phtml
* Default template file path is configurable by modyfying Tpl::$path
* Values set with by example "->set( 'name', ... )" are available in template file as $this->name and $name
* Values passed as Object are casted to string in there variable form, i.e. typeof $this->name == 'object' and typeof $name == 'string'

### Caching
```php
<?php
require 'Tpl.php';
$Tpl = new Tpl( 'layout' );
// Classic cache handling
if ( $cache = $Tpl->cached( 'myCache', 3600, '/my/path' ) ) // Get /my/path/myCache.html cache (expires after 3600s)
	echo $cache;
else
	echo $Tpl
		->set( 'content', ... ) // Build content html
		->cache( 'myCache', '/my/path' ); // Save cache in /my/path/myCache.html
// Or generate automatic cache id
echo $Tpl = new Tpl( 'layout' )
	->set( 'content', ... )
	->cache(); // Cache method can be called without any argument / without id: ->cache( null, '/my/path' )
$cacheId = $Tpl->id(); // Get the (almost unique md5) hash id of the cached file (/{sys_tempdir}/{$cacheId}.html)
```
* Expire duration is given in second, default is 3600 seconds
* If path is ommited, it use system temp directory

### Helpers
For now Tpl offers 2 helpers to handle CSS & JS inclusion in your template files, see example usage below.
In main template "layout.phtml" file:
```php
<?php
echo $this->css( 'style.css' ) // Append style.css (& "echo" will print all added css!)
		  ->append( 'print.css', 'print' ); // Append file with print media attribute
		  ->append( 'handheld.css', 'phone' ); // Css offers usefull preset media queries alias
echo $this->js()->prepend( 'jquery' ); // Print all js prepended by latest jQuery version from Goggle CDN
```
In sub template "content.phtml" file:
```php
<?php
$this->css( 'my/plugin/file.css' ); // Add plugin style
$this->js( 'my/plugin/jquery.file.js' ) // Add jQuery plugin
	 ->append( '! $.support.feature && alert("Your browser sucks!");' ); // Add plain JS
	 ->append( '$.plugin( "selector" );', 'ready' ); // Call plugin on ready jQuery event
```
You can easily create you own helpers, just create a class with "init" static method.
```php
<?php
class Menu { // Basic menu helper
	function init ( $links, $class = 'primary' ) { // Helpers need at least an init method
		$menu = '<ul class="menu ' . $class '">' . PHP_EOL;
		foreach ( (array) $links as $url => $text )
			$menu .= '<li><a href="' . $url . '">' . $text . '</a></li>' . PHP_EOL;
		return $menu . '</ul>';
	}
}
```
And simply use it in your template files.
```php
<?php
echo $this->menu( array( 'my/link' => 'My link' ) ); // Calls the helper init method
```

### Data filtering
You can use Data objects to store / handle your data.
By setting Data::$filter callback, your filter is applied when accessing data.
By example, you could use data filtering to resolve basic encoding problem (PHP code in UTF-8, output HTML in ISO-*)
```php
<?php
Data::$filter = function ( $data ) { // Applied on access ($data->property / $data['property'])
	return is_string( $data ) ? utf8_decode( $data ) : $data; // Decode every string
};
echo Tpl( 'layout' )
	->set( new Data( array( 'title' => 'àéïôù', 'content' => ... ) ) ); // Pass array / Traversable to set method
// In the layout.phtml template file, $this->title is UTF8-decoded ;)
```
## Objects & API
### Tpl objects
<pre>
- Data  -> Extended ArrayObject, used to store, filter and get any data.
	extended by:
	- Item  -> Data with "toString" (implode with new lines) + "indent" methods, used in helpers.
	- Cache -> Item with simple in memory caching, used to introduce cache interface.
- Tpl   -> Template engine with file caching & extensible with helpers, extends Cache.
</pre>
Data features: fluid interface + add "prepend" + "grep" methods + automatic filtering / modification when getting data.
### Helpers
<pre>
- Js    -> Javascript helper, extending Tpl.
- Css   -> CSS helper, extending Tpl.
	use: Style -> Item to store CSS style.
</pre>
### Hierarchy
<pre>
- Data
	- Item
		- Cache
			- Tpl (use Js and Css in __call)
				- Js
				- Css (use Style as storage)
		- Style
</pre>