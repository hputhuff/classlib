<?php
/**
 * Simplicity - A simple PHP framework
 * v1.1, January 2023 by Harley H. Puthuff
 * Copyright 2023, Your Showcase on the Internet
 */

/**
 * Global default variables
 */
class Simplicity {
	// for database connections
	public static $dbName					= null;
	public static $dbHost					= "localhost";
	public static $dbUsername			= "databoss";
	public static $dbPassword			= "dbpasswd";
}

/**
 * Databoss SQL database services
 */
class Databoss {

// object variables
	public $database;
	public $hostname;
	public $username;
	public $password;
	public $db;

// constructor
function __construct($name,$host=null,$user=null,$pass=null) {
	$database = $name;
	$hostname = $host ? $host : Simplicity::$dbHost;
	$username = $user ? $user : Simplicity::$dbUsername;
	$password = $pass ? $pass : Simplicity::$dbPassword;
	if (!$database) {
		echo "Databoss: cannot obtain database connection for: $database !";
		return null;
		}
	}

}
