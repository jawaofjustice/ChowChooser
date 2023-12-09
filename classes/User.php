<?php

class User {

	private int $id;
	private string $email;
	private string $username;
	private Database $db;

   function __construct(
      int $id,
      string $email,
      string $username,
      Database $db
   ) {
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


	function editUser() {
		return "this is editing a user";
	}

	function resetPassword() {
		return "this is resetting a password";
	}

   /**
   * Reads a user based on email and password.
   *
   * @return User|null The matching user. Returns `null` if no user was found.
   */
   public static function readUserByCredentials(
      string $email,
      string $password = null
   ): User|null {
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

   /**
   * Reads a user from the database by ID.
   */
	public static function readUserById(int $id): User {
		$db = new Database();

      $statement = $db->mysqli->prepare("select * from user where id = (?) limit 1");
      $statement->bind_param('i', $id);
		$statement->execute();

		$user = mysqli_fetch_assoc($statement->get_result());

		return new User($user['id'], $user['email'], $user['username'], $db);
	}

   /**
   * Creates a new record in the database's `user` table.
   *
   * @return User|null Instance of the newly created user. Returns `null` if a user already exists with the provided email.
   */
	public static function createUserInDatabase(string $email, string $password, string $username): User|null {
      $db = new Database();
		$emailAlreadyExists = !is_null(User::readUserByCredentials($email));
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

      return User::readUserByCredentials($email);
	}

   /**
   * Returns whether or not a user is a member of a lobby.
   */
   private function isInLobby(int $lobbyId): bool {
      $db = new Database();
      $statement = $db->mysqli->prepare("
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

   /**
   * Adds this user as a member of a lobby via invite code.
   */
   public function joinLobby(string $inviteCode): void {
      $db = new Database();
      $lobby = Lobby::readLobbyByInviteCode($inviteCode);

      // if there is no matching lobby, do nothing
      if (is_null($lobby)) {
         return;
      }

      $lobbyId = $lobby->getId();

      if ($this->isInLobby($lobbyId)) {
         return;
      }

      $statement = $db->mysqli->prepare("
         insert into lobby_user
         (lobby_id, user_id) values
         ( (?), (?) );");
      $statement->bind_param('ii', $lobbyId, $this->id);
      $statement->execute();
   }

   /**
   * Reads all lobbies that this user is a member of.
   *
   * @return array<Lobby> A collection of `Lobby` instances.
   */
	public function readLobbies(): array {
      $db = new Database();
		$statement = $db->mysqli->prepare("select distinct l.*, lu.user_id, u.username, s.description 
			from lobby as l inner join lobby_user as lu on l.id = lu.lobby_id inner join status as s 
			on l.status_id=s.id inner join user as u on lu.user_id=u.id where lu.user_id = (?)");
		$statement->bind_param('s', $this->id);
		$statement->execute();

		$all_user_lobbies = array();

      foreach ($statement->get_result() as $l) {
         $lobby = new Lobby($db, $l['id'], $l['admin_id'], $l['name'], $l['voting_end_time'], $l['ordering_end_time'], $l['status_id'], $l['invite_code']);
         array_push($all_user_lobbies, $lobby);
      }

      return $all_user_lobbies;
	}

}

?>
