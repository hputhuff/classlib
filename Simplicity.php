<?php
/**
 * Simplicity - A simple interface to helper classes
 * v1.1, January 2023 by Harley H. Puthuff
 * Copyright 2023, Your Showcase on the Internet
 */

/**
 * Global default variables
 */
$globalDefault = [
	'dbName'				=> null,						// database name
	'dbHost'				=> "localhost",			// SQL server host
	'dbUsername'		=> "databoss",			// access username
	'dbPassword'		=> "dbpasswd",			// access password
	];

/**
 * Databoss SQL database services
 */
class Databoss {
}

/**
 * Our base class
 */
class Simplicity {
	private $myDbName;
	public function __construct() {
		$myDbName = main::$globalDefault['dbName'];
		}
}
