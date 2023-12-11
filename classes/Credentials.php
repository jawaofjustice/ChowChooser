<?php
class Credentials {
	public string $host;
	public string $username;
	public string $password;
	public string $database;

	public function __construct() {
		$this->host = "localhost";
		$this->username = "chowChooserAdmin";
		$this->password = "devTeam2023!";
		$this->database = "chow_chooser";
	}

}
?>
