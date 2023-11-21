<?php
class Credentials {
	function __construct() {
		$this->host = "localhost";
		$this->username = "chowChooserAdmin";
		$this->password = getenv('CHOWCHOOSER_P');
		$this->database = "chow_chooser";
	}
}
?>
