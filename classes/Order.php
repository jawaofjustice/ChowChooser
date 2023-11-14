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
		$this->userId = 1;
		
		//$this->viewAddOrderItem();
	}
	
	function viewAddOrderItem() {
		$currentFilter = "";
		
		$swapArray['pageTitle'] = "Add an item to your order!";
		$swapArray['loginLogoutForm'] = ChowChooserEngine::load_template("logoutForm");
		$swapArray['userId'] = $_SESSION['user']->getId();
		$swapArray['warningMessage'] = "";
		$swapArray['listOfItemsToSelectFrom'] = $this->buildFoodList($currentFilter);
		$swapArray['existingOrderItems'] = $this->buildCurrentOrder();
		$swapArray['testOutput'] = $this->checkForExistingItem();
		echo ChowChooserEngine::load_template("addOrderItem", $swapArray);
	}
	
	function buildCurrentOrder() {
		
		
		
		//queries for food currently from this user in this lobby
		$queryString = "select 
							oi.quantity, f.*
							from order_item oi
							left join food f
								on oi.food_id = f.id
							where oi.lobby_id = ?
								and oi.user_id = ?";
		$query = $this->db->prepare($queryString);
		$query->bind_param('ii', $this->lobbyId, $this->userId);
		
		$query->execute();
		$response = $query->get_result();
		
		//
		
		
		
		$output = "";
		$i = 0;
		foreach ($response as $r) {
			$i++;
			$output .= "Result " . $i . ": " . print_r($r, 1). "<br />";
		}
		return $output;
	}
	
	function buildFoodList($currentFilter) {
		
		// basic test of sql return
		//~ $query = "select * from lobby;";
		//~ $response = $this->db->query($query);
		//~ $output = "";
		//~ $i = 0;
		//~ foreach ($response as $r) {
			//~ $i++;
			//~ $output .= "Result " . $i . ": " . print_r($r, 1). "<br />";
		//~ }
		//~ return $output;
		
		
		// queries lobby and restaurant info for display
		$queryString = "select 
							l.*, lb.*, r.*
							from lobby l 
							left join lobby_restaurant lb 
								on l.id = lb.lobby_id 
							left join restaurant r
								on lb.restaurant_id = r.id
							where l.id = (?) 
							limit 1";
		$query = $this->db->prepare($queryString);
		$query->bind_param('i', $this->lobbyId);
		
		$query->execute();
		$lobbyAndRestaurantInfo = $query->get_result();
		
		
		//queries for food offered by this restaurant
		$queryString = "select 
							f.*
							from lobby_restaurant lb
							left join restaurant r
								on lb.restaurant_id = r.id
							left join food f 
								on r.id = f.restaurant_id
							where lb.lobby_id = (?)";
		$query = $this->db->prepare($queryString);
		$query->bind_param('i', $this->lobbyId);
		
		$query->execute();
		$response = $query->get_result();
		
		//
		
		
		
		$output = "";
		$i = 0;
		foreach ($response as $r) {
			$i++;
			$output .= "Result " . $i . ": " . print_r($r, 1). "<br />";
		}
		return $output;
	}
	
	function buildFilterList() {
		return "This is where a filter list will show";
	}
	
	function checkForExistingItem() {
		$foodId = 2;
		$qty = 1;
		// first we check to see if this is already added to this order and try to increment up if possible
		$queryString = "select * from order_item where user_id = ? and lobby_id = ? and food_id  = ? limit 1;";
		$query = $this->db->prepare($queryString);
		$query->bind_param('iii', $this->userId, $this->lobbyId, $foodId);
		
		$query->execute();
		$response = $query->get_result();
		$output = mysqli_num_rows($response);
		
		// if we have 1 row, return a 1, otherwise 0
		
		return $output;
	}
	
	function processAddOrderItem() {
		
		/*
			This is where we'll save our new order item to the database:
			
		*/
		$foodId = 2;
		$qty = 1;
		// first we check to see if this is already added to this order and try to increment up if possible
		
		if ($this->checkForExistingItem()) {
			//if we have it already, increment
			$queryString = "update order_item set quantity=quantity+? where user_id = ? and lobby_id = ? and food_id = ?;";
			$query = $this->db->prepare($queryString);
			
			$query->bind_param('iiii', $qty, $this->userId, $this->lobbyId, $foodId);
			
			$query->execute();
			$response = $query->get_result();
		} else {
			
			// if not we will add this as a new row
			$queryString = "insert into order_item (quantity, user_id, lobby_id, food_id) values (?, ?, ?, ?);";
			$query = $this->db->prepare($queryString);
			
			$query->bind_param('iiii', $qty, $this->userId, $this->lobbyId, $foodId);
			
			$query->execute();
			$response = $query->get_result();
			
		}
		
		
		//$this->viewAddOrderItem();
		header('Location: ?action=viewPlaceOrderSample');

	}

}

?>
