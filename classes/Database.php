<?php
require_once "classes/Credentials.php";

/**
* Creates and stores a MySQLi instance that interfaces with the database.
*/
class Database {
	private Database $db;
	public mysqli $mysqli;

	public function __construct() {
		$creds = new Credentials();
		$this->mysqli = new mysqli($creds->getHost(),$creds->getUsername(),$creds->getPassword(),$creds->getDatabase());
		if ($this->mysqli->connect_errno) {
			echo "Failed to connect to MySQL: " . $this->mysqli->connect_error;
			exit();
		}
	}
	
}

?>
