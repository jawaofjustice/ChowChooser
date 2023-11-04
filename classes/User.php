<?php

#[AllowDynamicProperties]
class User {

	function __construct() {
		//return "this is a user object!";
		$this->db = Database::connect();	
      $this->mysqli = new mysqli("localhost", "chowChooserAdmin", "password", "chow_chooser");
      if ($this->mysqli->connect_errno) {
         echo "ERROR initializing mysqli in User class";
         exit();
      }
	}

   function getUserIDWithCredentials($email, $password) {
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
      $result = $statement->get_result();
      $record = mysqli_fetch_assoc($result);

      // no such user exists, don't try finding the id
      if (is_null($record)) {
         return null;
      }

      $id = $record['id'];
      return $id;
   }
	
	function createUser($email, $password) {
      $id = $this->getUserIDWithCredentials($email, null);
      if (!is_null($id)) {
         echo "Cannot create account: user already exists with this email.";
         return;
      }
      echo "Can create account: user does not exist with this email!";
   }
		
	function editUser() {
		return "this is editing a user";
	}
	
	function resetPassword() {
		return "this is resetting a password";
	}
	
	function login($email, $password) {
      $id = $this->getUserIDWithCredentials($email, $password);
      if (is_null($id)) {
         echo "Incorrect credentials.";
         return;
      }
      $_SESSION['id'] = $id;
      echo "Succesfully logged in!";
	}
}

?>
