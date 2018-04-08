<?php
session_start();

$GLOBALS['config'] = array(
	'mysql' => array(
		'host' => '192.168.0.62',
		'user' => 'root',
		'password' => 'root',
		'db' => 'localdb',
		'odbc' => null,
		'dbtype' => 'mysql'
	),
	'mdlogp' => array(
		'host' => '192.168.1.37', 
		'user' => 'integra',
		'password' => 'integra',
		'db' => 'POTIGUAR', 
		'odbc' => 'PRODPTG',
		'dbtype' => 'oracle'
	),
	'remember' => array(
		'cookie_name' => 'hash',
		'cookie_expire' => 604800
	),
	'session' => array(
		'session_name' => 'remote',
		'token_name' => 'api'
	),
	'appId' => 'e03ad982449af87ade1899ffbc259eee'
);

spl_autoload_register(function($class){
	require_once 'classes/'. str_replace('_', '/', $class) .'.php';
});