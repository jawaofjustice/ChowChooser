<?php

class Database {

	private \mysqli $mysqli;

	function __construct() {
		
		$pass = getenv('CHOWCHOOSER_P');
		$pass = "devTeam2023!";
		
		$this->mysqli = new mysqli("localhost","chowChooserAdmin",$pass,"chow_chooser");

		if ($this->mysqli->connect_errno) {
			echo "Failed to connect to MySQL: " . $this->mysqli->connect_error;
			exit();
		}
		//return $this;
	}

	public function __get($property) {
		if (property_exists($this, $property)) {
            return $this->$property;
        }
	}

	public function getUserFromCredentials(string $email, string $password): User|null {
		// when creating an account, we're only concerned with finding
		// accounts with the inputted email (emails should be unique)
		if (is_null($password)) {
			$statement = $this->mysqli->prepare("select * from user where email = (?) limit 1");
			$statement->bind_param('s', $email);
		} else {
			$statement = $this->mysqli->prepare("select * from user where email = (?) and password = (?) limit 1");
			$statement->bind_param('ss', $email, $password);
		}

		$statement->execute();
		$user = mysqli_fetch_assoc($statement->get_result());

		// no such user exists with these credentials
		if (is_null($user)) {
			return null;
		}

		return new User($user['id'], $user['email']);
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

	function createAccount(string $email, string $password): void {
		$id = $this->getUserFromCredentials($email, null);
		if (!is_null($id)) {
			echo "Cannot create account: user already exists with this email.";
			return;
		}
		echo "Can create account: user does not exist with this email!";
	}

}

?>
