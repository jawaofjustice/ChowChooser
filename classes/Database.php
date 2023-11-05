<?php

class Database {

   private mysqli $mysqli;

	function __construct() {
		$pass = getenv('CHOWCHOOSER_P');
		$this->mysqli = new mysqli("localhost","chowChooserAdmin",$pass,"chow_chooser");

		if ($this->mysqli->connect_errno) {
		  echo "Failed to connect to MySQL: " . $this->mysqli->connect_error;
		  exit();
		}
		return $this;
	}

   public function getUserFromCredentials($email, $password): User|null {
      // when creating an account, we're only concerned with finding
      // accounts with the inputted email (emails should be unique)
      if (is_null($password)) {
         $statement = $this->mysqli->prepare("select * from users where email = (?) limit 1");
         // replace (?) with form credentials,
         // 's' marks parameter as a string
         $statement->bind_param('s', $email);
      } else {
         $statement = $this->mysqli->prepare("select * from users where email = (?) and password = (?) limit 1");
         // 'ss' marks both parameters as strings
         $statement->bind_param('ss', $email, $password);
      }

      $statement->execute();
      $user = mysqli_fetch_assoc($statement->get_result());

      // no such user exists, don't try finding the id
      if (is_null($user)) {
         return null;
      }

      return new User($user['id'], $user['email']);
   }

	function createUser($email, $password): void {
      $id = $this->getUserFromCredentials($email, null);
      if (!is_null($id)) {
         echo "Cannot create account: user already exists with this email.";
         return;
      }
      echo "Can create account: user does not exist with this email!";
   }

}

?>
