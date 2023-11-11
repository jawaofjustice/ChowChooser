<?php

class User {

	private int $id;
	private string $email;
	private Database $db;

	function __construct(int $id, string $email, Database $db) {
		$this->id = $id;
		$this->email = $email;
		$this->db = new Database();
	}

	public function getId(): int {
		return $this->id;
	}

	public function getEmail(): string {
		return $this->email;
	}

	function editUser() {
		return "this is editing a user";
	}

	function resetPassword() {
		return "this is resetting a password";
	}

	public static function getUserFromCredentials(string $email, string $password): User|null {
		$db = new Database();
		if (is_null($password)) {
			// when creating an account, we're only concerned with finding
			// accounts with the inputted email (emails should be unique)
			$statement = $db->mysqli->prepare("select * from user where email = (?) limit 1");
			$statement->bind_param('s', $email);
		} else {
			$statement = $db->mysqli->prepare("select * from user where email = (?) and password = (?) limit 1");
			$statement->bind_param('ss', $email, $password);
		}

		$statement->execute();
		$user = mysqli_fetch_assoc($statement->get_result());

		// no such user exists with these credentials
		if (is_null($user)) {
			return null;
		}

		return new User($user['id'], $user['email'], $db);
	}

	function createUserInDatabase(string $email, string $password): void {
		$id = $this->getUserFromCredentials($email, null);
		if (!is_null($id)) {
			echo "Cannot create account: user already exists with this email.";
			return;
		}
		echo "Can create account: user does not exist with this email!";
	}

}

?>
