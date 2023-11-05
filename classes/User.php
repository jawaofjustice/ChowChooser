<?php

class User {

   private Database $db;

	function __construct() {
		$this->db = new Database();	
	}
		
	function editUser() {
		return "this is editing a user";
	}
	
	function resetPassword() {
		return "this is resetting a password";
	}
	
}

?>
