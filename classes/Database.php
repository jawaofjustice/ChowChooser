<?php
require_once "classes/Credentials.php";

/**
* Creates and stores a MySQLi instance that interfaces with the database.
*/
class Database {
	public $db;
	public $mysqli;

	function __construct() {
		$creds = new Credentials();
		$this->mysqli = new mysqli($creds->host,$creds->username,$creds->password,$creds->database);
		if ($this->mysqli->connect_errno) {
			echo "Failed to connect to MySQL: " . $this->mysqli->connect_error;
			exit();
		}
	}
	
	public function __get($property) {
		if (property_exists($this, $property)) {
            return $this->$property;
        }
	}
}

?>
