<?php

class User {
	
	function __construct() {
		//return "this is a user object!";
		$this->db = Database::connect();	
	}
	
	function createUser() {
		$response = $this->db->query("describe users;");
		//$results = $response->fetch_assoc();
		
		echo "we found " . mysqli_num_rows($response) . " results\n ";
		while ($row = $response->fetch_assoc()) {
			echo "<pre>here's some result contents: " . print_r($row, true) . "</pre>";
		}
		
		
		// printing the array of results, or we can foreach loop through them
		//echo "Here's our db results: ".print_r($row, true);
		return "this is the create user function";
	}
	
	function editUser() {
		return "this is editing a user";
	}
	
	function resetPassword() {
		return "this is resetting a password";
	}
	
	function login() {
		return "this is logging in";
	}
	
	function logout() {
		return "this is logging out";
	}
}

?>
