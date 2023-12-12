<?php
class Credentials {
	private string $host;
	private string $username;
	private string $password;
	private string $database;

	public function __construct() {
		$this->host = "localhost";
		$this->username = "chowChooserAdmin";
		$this->password = "devTeam2023!";
		$this->database = "chow_chooser";
	}

	public function getHost(): string {
        return $this->host;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getDatabase(): string {
        return $this->database;
    }

}
?>
