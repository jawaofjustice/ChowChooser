<?php
require_once "classes/Credentials.php";

class Database {
	
	function __construct() {
		$this->mysqli = new mysqli("localhost","chowChooserAdmin",Credentials::pass(),"chow_chooser");
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

	public function getUsersLobbies(int $id) {
		/* $statement = $this->mysqli->prepare("select * from lobby as l inner join lobby_user as lu on l.id = lu.lobby_id where lu.user_id = (?)"); */
        $statement = $this->mysqli->prepare("select * from lobby where lobby.admin_id = (?)");
		$statement->bind_param('s', $id);
		$statement->execute();
		/* $lobbies = mysqli_fetch_assoc($statement->get_result()); */
		foreach ($statement->get_result() as $lobby) {
			print_r($lobby);
			echo "<br>";
		}
	}
}

?>
