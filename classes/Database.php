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

			// Display end of phase information based on the current phase
			if ($lobby['description']=="Voting")
				$phase_end_message="Voting ends at ".$lobby['voting_end_time'];
			else if ($lobby['description']=="Ordering")
				$phase_end_message="Ordering ends at ".$lobby['ordering_end_time'];
			else if ($lobby['description']=="Completed")
				$phase_end_message="Everyone has finished ordering. Enjoy your meal!";
			else
				$phase_end_message="ERROR: Invalid lobby status";

			// Append each lobby to a list of lobbies for the user
			$all_user_lobbies.='<a href="index.php?action=showlobby&lobby='.$lobby['id'].'">'.$lobby['name']."</a>"." User: ".$lobby['username'].$adminIcon." ".$phase_end_message."<br>";
		}

		return $all_user_lobbies;
	}
}

?>
