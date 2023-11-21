<?php
class Credentials {
	public $host;
	public $username;
	public $password;
	public $database;

	function __construct() {
		$this->host = "localhost";
		$this->username = "chowChooserAdmin";
//		$this->password = getenv('CHOWCHOOSER_P');
		$this->password = "devTeam2023!";
		$this->database = "chow_chooser";
	}
}
?>
