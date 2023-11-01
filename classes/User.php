<?php

class User {
	
	function __construct() {
		//return "this is a user object!";
		$this->db = Database::connect();	
	}
	
	function createUser($email, $password) {
      $email = "jermaEmail@email.com";
      $mysqli = new mysqli("localhost", "chowChooserAdmin", "password", "chow_chooser");
      print_r($mysqli->query("select * from users where email = ".$email));
   }
		
		
	function editUser() {
		return "this is editing a user";
	}
	
	function resetPassword() {
		return "this is resetting a password";
	}
	
	function login($email, $password) {
      $mysqli = new mysqli("localhost", "chowChooserAdmin", "password", "chow_chooser");
      if ($mysqli->connect_errno) {
         echo "mysqli error!";
         exit();
      }
      $statement = $mysqli->prepare("select * from users where email = (?) and password = (?) LIMIT 1");
      $statement->bind_param('ss', $email, $password);
      $statement->execute();
      $result = $statement->get_result();
      print_r(mysqli_fetch_assoc($result));
	}
	
	function logout() {
		return "this is logging out";
	}
}

?>
