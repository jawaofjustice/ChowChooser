<?php
require_once "classes/Credentials.php";

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

	public function getUsersLobbies(int $id) {
		$statement = $this->mysqli->prepare("select distinct l.*, lu.user_id, u.username, s.description 
			from lobby as l inner join lobby_user as lu on l.id = lu.lobby_id inner join status as s 
			on l.status_id=s.id inner join user as u on lu.user_id=u.id where lu.user_id = (?)");
        /* $statement = $this->mysqli->prepare("select * from lobby where lobby.admin_id = (?)"); */
		$statement->bind_param('s', $id);
		$statement->execute();

		$all_user_lobbies="";

		/* $lobbies = mysqli_fetch_assoc($statement->get_result()); */
		foreach ($statement->get_result() as $lobby) {
			// If the user is the lobby admin, put a star after their user ID
			if ($lobby['admin_id']==$lobby['user_id'])
				$adminIcon = "*";
			else
				$adminIcon = "";

			// Append each lobby to a list of lobbies for the user
			$all_user_lobbies.='<a href="index.php?action=showlobby&lobby='.$lobby['id'].'">'.$lobby['name']."</a>"." User: ".$lobby['username'].$adminIcon." Status: ".$lobby['description']."<br>";
		}

		return $all_user_lobbies;
	}
}

?>
