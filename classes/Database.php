<?php
require_once "classes/Credentials.php";

class Database {
  
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

	public function getUsersLobbies(int $id) {
		/* $statement = $this->mysqli->prepare("select * from lobby as l inner join lobby_user as lu on l.id = lu.lobby_id where lu.user_id = (?)"); */
        $statement = $this->mysqli->prepare("select * from lobby where lobby.admin_id = (?)");
		$statement->bind_param('s', $id);
		$statement->execute();

		$all_user_lobbies="";

		/* $lobbies = mysqli_fetch_assoc($statement->get_result()); */
		foreach ($statement->get_result() as $lobby) {
			// Append each lobby to a list of lobbies for the user
			$all_user_lobbies.='<a href="index.php?action=showlobby&lobby="'.$lobby['id'].">".$lobby['name']."</a><br>";
		}

		return $all_user_lobbies;
	}
}

?>
