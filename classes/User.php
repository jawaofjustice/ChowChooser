<?php

class User {

   private Database $db;
   private int $id;
   private string $email;

	function __construct(int $id, string $email) {
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

}

?>
