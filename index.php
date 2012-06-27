<?php
namespace Tpl;
require 'Tpl.php';
header( 'Content-Type: text/html; charset=utf-8' );
echo Tpl( 'index' )
	->set( 'title', 'My Test Page')
	->set( 'content', Tpl( 'content' )->set( 'users', array( array( 
		'id'     => 1,
		'name'   => 'user name',
		'email'  => 'test@test.com',
		'banned' => true
	) ) )->indent( 2 ) );