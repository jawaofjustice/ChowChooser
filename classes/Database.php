<?php

class Database {
	public static function connect() {
		$pass = getenv('CHOWCHOOSER_P');
		$mysqli = new mysqli("localhost","chowChooserAdmin",$pass,"chow_chooser");

		if ($mysqli -> connect_errno) {
		  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
		  exit();
		}
		return $mysqli;
	}
}

?>
