<?php
class Credentials {
	private string $host;
	private string $username;
	private string $password;
	private string $database;

	public function __construct() {
		$this->host = "localhost";
		$this->username = "chowChooserAdmin";
//		$this->password = getenv('CHOWCHOOSER_P');
		$this->password = "devTeam2023!";
		$this->database = "chow_chooser";
	}

	public function __get($property) {
		if (property_exists($this, $property)) {
            return $this->$property;
        }
	}
}
?>
