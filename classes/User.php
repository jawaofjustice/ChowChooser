<?php

class User {

	private int $id;
	private string $email;
	private string $username;
	private Database $db;

	function __construct(int $id, string $email, string $username, Database $db) {
		$this->id = $id;
		$this->email = $email;
		$this->username = $username;
		$this->db = $db;
	}
	
	public function getId(): int {
		return $this->id;
	}

	public function getEmail(): string {
		return $this->email;
	}

	public function getUsername(): string {
		return $this->username;
	}

	function editUser() {
		return "this is editing a user";
	}

	function resetPassword() {
		return "this is resetting a password";
	}

	public static function getUserFromCredentials(string $email, string $password = null): User|null {
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

		return new User($user['id'], $user['email'], $user['username'], $db);

	}

	public static function getUserFromId(int $id): User {
		$db = new Database();

      $statement = $db->mysqli->prepare("select * from user where id = (?) limit 1");
      $statement->bind_param('i', $id);
		$statement->execute();

		$user = mysqli_fetch_assoc($statement->get_result());

		return new User($user['id'], $user['email'], $user['username'], $db);
	}

	public static function createUserInDatabase(string $email, string $password, string $username): User|null {
      $db = new Database();
		$emailAlreadyExists = !is_null(User::getUserFromCredentials($email));
		if ($emailAlreadyExists) {
			$errorMsg = "User already exists with this email. Please try a different one!";
         echo ChowChooserEngine::load_template("createAccount", ["errorMsg" => $errorMsg]);
			exit();
		}

      $statement = $db->mysqli->prepare("
         insert into user
         (email, password, username) values
         ( (?), (?), (?) );");
      $statement->bind_param('sss', $email, $password, $username);
      $statement->execute();

      return User::getUserFromCredentials($email);
	}

   private function isInLobby($lobbyId): bool {
      $statement = $this->db->mysqli->prepare("
         SELECT *
         FROM lobby_user
         WHERE lobby_id = (?) and user_id = (?)");
      $statement->bind_param('ii', $lobbyId, $this->id);
      $statement->execute();

      $result = mysqli_fetch_assoc($statement->get_result());

      if (is_null($result)) {
         return false;
      }
      return true;
   }

   public function joinLobby(string $inviteCode): void {
      $lobby = Lobby::getLobbyByInviteCode($inviteCode);

      // if there is no matching lobby, do nothing
      if (is_null($lobby)) {
         return;
      }

      $lobbyId = $lobby->getId();

      if ($this->isInLobby($lobbyId)) {
         return;
      }

      $db = new Database();
      $statement = $db->mysqli->prepare("
         insert into lobby_user
         (lobby_id, user_id) values
         ( (?), (?) );");
      $statement->bind_param('ii', $lobbyId, $this->id);
      $statement->execute();
   }

}

?>
