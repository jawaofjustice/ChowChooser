<?php


//~ -Area to show order so far and total price
//~ -Selectable list of food items with prices
//~ -Category drop-down input to filter food list
//~ -Add button for food item
//~ -Submit button takes you back to view lobby and writes to database

class Order {

	function __construct() {
		
		$this->db = Database::connect();
		$this->lobbyId = 1; // for testing purposes
		//$this->viewAddOrderItem();
	}
	
	function viewAddOrderItem() {
		$currentFilter = "";
		
		$swapArray['pageTitle'] = "Add an item to your order!";
		$swapArray['loginLogoutForm'] = ChowChooserEngine::load_template("logoutForm");
		$swapArray['userId'] = $_SESSION['user']->getId();
		$swapArray['warningMessage'] = "";
		$swapArray['listOfItemsToSelectFrom'] = $this->buildFoodList($currentFilter);
		echo ChowChooserEngine::load_template("addOrderItem", $swapArray);
	}
	
	function buildFoodList($currentFilter) {
		//$query = $this->mysqli->prepare("select * from lobby where id = (?) limit 1");
		//$query->bind_param('s', $this->lobbyId);
		
		
		//$statement->execute();
		//$user = mysqli_fetch_assoc($statement->get_result());
		$response = $this->db->query("describe lobbies;");
		
		return "This is where we build a food list selector";
	}
	
	function buildFilterList() {
		return "This is where a filter list will show";
	}
	
	function processAddOrderItem() {
		
		/*
			This is where we'll save our new order item to the database:
			
		*/
		
		
		//$this->viewAddOrderItem();
		header('Location: ?action=viewPlaceOrderSample');

	}

}

?>
