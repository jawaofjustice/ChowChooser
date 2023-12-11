<?php


//~ -Area to show order so far and total price
//~ -Selectable list of food items with prices
//~ -Category drop-down input to filter food list
//~ -Add button for food item
//~ -Submit button takes you back to view lobby and writes to database

class OrderCreation {
	private mysqli $db;
	private int $lobbyId;
	private int $userId;

	public function __construct(int $lobbyId) {
		$db = new Database();
		$this->db = $db->mysqli;
		$this->lobbyId = $lobbyId; // for testing purposes
		$this->userId = $_SESSION['user']->getId();
	}
	
	public function viewAddOrderItem() {
		
		$swapArray['lobbyId'] = $this->lobbyId;
		$lobby = Lobby::readLobby($this->lobbyId);
		$swapArray['userId'] = $this->userId;
		$swapArray['warningMessage'] = "";
		//$swapArray['warningMessage'] = "This is a sample warning message!";
		$swapArray['lobbyInfo'] = $this->readLobbyInfo();
		$swapArray['menuList'] = $this->buildMenu();
		$swapArray['existingOrderItems'] = $this->buildCurrentOrder();
		$swapArray['searchResultsHeader'] = $this->buildSearchResultHeader();
		$timerSwap['countDownTimeStart'] = date_format(new Datetime($lobby->getOrderingEndTime()),"M j, Y H:i:s");
		$timerSwap['elementToUpdate'] = 'orderEndTimeHolder';
		$timerSwap['countDownEndText'] = 'None, ordering is now complete!';
		$swapArray['countDownTimer'] = ChowChooserEngine::load_template('countDownTimer', $timerSwap);
		$swapArray['title'] = "Build Your Order!";
		$baseArray['title'] = "Build Your Order";
		$baseArray['loginLogoutForm'] = ChowChooserEngine::load_template("logoutForm");
		$baseArray['backButton'] = ChowChooserEngine::load_template("backButton", ["backLink" => "?action=showlobby&lobby=".$this->lobbyId]);
		
		
		$baseArray['mainContent'] = ChowChooserEngine::load_template("addOrderItem", $swapArray);
		
		echo ChowChooserEngine::load_template("base", $baseArray);
	}
	
	private function buildSearchResultHeader() {
		$output = "";
		
		if(isset($_POST['searchText'])) {
			$output .= "<div class=\"searchResultHeader\">Results for search \"" . $_POST['searchText'] . "\": <br /></div>";
		}
		
		return $output;
	}
	
	private function buildCurrentOrder() {
		
		
		
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
		$subtotal = 0;
		
		if (mysqli_num_rows($response) > 0) {
			
			foreach ($response as $r) {
				$i++;
				//$output .= "Result " . $i . ": " . print_r($r, 1). "<br />";
				$swap['orderLineItemId'] = "orderLineItem". $i;
				$swap['orderLineItemContents'] = $r['quantity'] . " x " . $r['name'] . " - $" . $r['price'];
				$swap['foodId'] = $r['id'];
				$swap['lobbyId'] = $this->lobbyId;
				$subtotal += $r['quantity'] * $r['price'];
				$output .= ChowChooserEngine::load_template('orderLineItem', $swap);
			}
			$output .= "<br /> Subtotal: $" . $subtotal;
			$output .= "<br /> Taxes: $" . number_format(round($subtotal * 0.06, 2), 2);
			$output .= "<br /> Total: $" . number_format(round($subtotal * 1.06, 2), 2);
			
		} else {
			$output .= "<br />You currently have no items in your order!<br /><br />";
		}	
		return $output;
	}
	
	
	
	
	private function readLobbyInfo() {
		// queries lobby and restaurant info for display 
		//l.*, lb.*, r.*
		$queryString = "select 
							r.name as restaurantName, l.*
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
		$info = mysqli_fetch_assoc($lobbyAndRestaurantInfo);
		
		
		return "Ordering for " . $info['restaurantName'] . " will end at " . date_format(new Datetime($info['ordering_end_time']),"M j, Y H:i:s");
		//~ $output = "";
		//~ $i = 0;
		//~ foreach ($lobbyAndRestaurantInfo as $r) {
			//~ $i++;
			//~ $output .= "Result " . $i . ": " . print_r($r, 1). "<br />";
		//~ }
		//~ return $output;
	}
	
	private function buildMenu() {
		// first check for our filters and build an array
		$searchFilterSuffix = "";
		$bindParamsSuffix = "";
		$bindParamsValuesSuffix = array($this->lobbyId);
		if(isset($_POST['searchText'])) {
			//echo "This search text was submitted: " . $_POST['searchText'];
			$searchFilterSuffix .= " and (";
			$terms = explode(" ", $_POST['searchText']);
			foreach ($terms as $t) {
				$searchFilterSuffix .= " f.name like (?) or";
				$bindParamsSuffix .= "s";
				array_push($bindParamsValuesSuffix, "%".$t."%");
			}
			$searchFilterSuffix = substr($searchFilterSuffix, 0, -3).");";
		}
		
		//echo "Search Suffix: " . $searchFilterSuffix;
		
		
		//~ //queries for food offered by this restaurant
		$queryString = "select 
							f.*
							from lobby_restaurant lb
							left join restaurant r
								on lb.restaurant_id = r.id
							left join food f 
								on r.id = f.restaurant_id
							where lb.lobby_id = (?) " . $searchFilterSuffix;
							
		$query = $this->db->prepare($queryString);
		//~ echo "Our bind params list looks like: " . 'i'.$bindParamsSuffix;
		//~ echo "<br /><br />Our query string looks like: " . $queryString."<br /><br />";
		//~ echo "Our array of actual values for our search looks like: " . print_r($bindParamsValuesSuffix ,1);
		
		$query->bind_param('i'.$bindParamsSuffix, ...$bindParamsValuesSuffix);
		
				
		
		$query->execute();
		$response = $query->get_result();
		
		//
		$output = "";
		if(mysqli_num_rows($response) > 0) {
			$i = 0;
			foreach ($response as $r) {
				$i++;
				//$output .= "Result " . $i . ": " . print_r($r, 1). "<br />";
				$swap['menuItemId'] = "menuItem". $i;
				$swap['menuItemContents'] = $r['name'] . " - $" . $r['price'];
				$swap['foodId'] = $r['id'];
				$swap['lobbyId'] = $this->lobbyId;
				

				$output .= ChowChooserEngine::load_template('menuItem', $swap);
			}
		} else {
			if (isset($_POST['searchText'])) {
				$output .= "Your search for \"" . $_POST['searchText'] . "\" found no results!";
			} else {
				$output .= "There are no food items available from this restaurant!";
			}
			
		}
		
		return $output;
	}
	
	
	private function userHasOrderedItem(int $lobbyId, int $foodId): bool  {
		// first we check to see if this is already added to this order and try to increment up if possible
		$queryString = "select * from order_item where user_id = ? and lobby_id = ? and food_id  = ? limit 1;";
		$query = $this->db->prepare($queryString);
		$query->bind_param('iii', $this->userId, $lobbyId, $foodId);
		
		$query->execute();
		$response = $query->get_result();
		if (mysqli_num_rows($response) > 0) {
			return true;
		}
		
		return false;
	}

	/**
	* Retrieve the quantity of a food item as ordered by a user.
	*/
	private function readQuantity(int $lobbyId, int $foodId): int {
	
		$queryString = "select quantity from order_item where user_id = ? and lobby_id = ? and food_id  = ? limit 1;";
		$query = $this->db->prepare($queryString);
		$query->bind_param('iii', $this->userId, $lobbyId, $foodId);
		
		$query->execute();
		$response = $query->get_result();
		if (mysqli_num_rows($response)) {
			$r = mysqli_fetch_assoc($response);
		
			$output = intval($r['quantity']);

			return $output;
		} else {
			return 0;
		}
		
	}
	
	public function processAddOrderItem() {
		
		/*
			This is where we'll save our new order item to the database:
			
		*/
		if(isset($_GET['foodId']) && isset($_GET['lobbyId'])) {
				
			$foodId = $_GET['foodId'];
			$lobbyId = $_GET['lobbyId']; 
			$qty = 1;
		
			// first we check to see if this is already added to this order and try to increment up if possible
			
			if ($this->userHasOrderedItem($lobbyId, $foodId)) {
				//if we have it already, increment
				$queryString = "update order_item set quantity=quantity+? where user_id = ? and lobby_id = ? and food_id = ?;";
				$query = $this->db->prepare($queryString);
				
				$query->bind_param('iiii', $qty, $this->userId, $lobbyId, $foodId);
				
				$query->execute();
				//$response = $query->get_result();
			} else {
				
				// if not we will add this as a new row
				$queryString = "insert into order_item (quantity, user_id, lobby_id, food_id) values (?, ?, ?, ?);";
				$query = $this->db->prepare($queryString);
				
				$query->bind_param('iiii', $qty, $this->userId, $this->lobbyId, $foodId);
				
				$query->execute();
				//$response = $query->get_result();
				
			}
		}
		
		//$this->viewAddOrderItem();
		header('Location: ?action=viewPlaceOrderSample&lobbyId='.$lobbyId);

	}
	
	public function processRemoveOrderItem() {
		
		/*
			This is where we'll save our new order item to the database:
			
		*/
		if(isset($_GET['foodId']) && isset($_GET['lobbyId'])) {
				
			$foodId = $_GET['foodId'];
			$lobbyId = $_GET['lobbyId']; 
			$qty = 1;
		
			// first we check to see if this is already added to this order
			
			if ($this->userHasOrderedItem($lobbyId, $foodId)) {
				if ($this->readQuantity($lobbyId, $foodId) > 1) { // if the qty is greater than 1 for this line item
					// we will decrement by 1
					$queryString = "update order_item set quantity=quantity-? where user_id = ? and lobby_id = ? and food_id = ?;";
					$query = $this->db->prepare($queryString);
					
					$query->bind_param('iiii', $qty, $this->userId, $lobbyId, $foodId);
					
					$query->execute();
					//$response = $query->get_result();
				} else { // otherwise we assume qty = 1 and delete the row
					$queryString = "delete from order_item where user_id = ? and lobby_id = ? and food_id = ?;";
					$query = $this->db->prepare($queryString);
					
					$query->bind_param('iii', $this->userId, $lobbyId, $foodId);
					
					$query->execute();
					//$response = $query->get_result();
				}
				
			
				
			}
		}
		
		//$this->viewAddOrderItem();
		header('Location: ?action=viewPlaceOrderSample&lobbyId='.$lobbyId);

	}

}

?>
