<?php
require_once "classes/Database.php";
require_once "classes/FoodItem.php";
require_once "classes/User.php";
require_once "classes/Lobby.php";
require_once "classes/Restaurant.php";
require_once "classes/Order.php";

class ChowChooserEngine {

	private Database $db;

	function __construct() {

		// direct user to the welcome page if user
		// isn't logged in and isn't actively trying to log in,
		// such as when visiting the site for the first time
		if (empty($_SESSION) && !isset($_POST['login'])) {
			$this->welcome();
			return;
		}

		$this->db = new Database();

		// uncomment the following line to see results of example query - sorry it breaks page formatting!
		//$this->example_query();

		if(isset($_GET['showlobby'])) {
			if($_GET['showlobby'] == 'back') {
				$this->main_menu();
				return;
			}
			$this->view_lobby();
			return;
		}

		if (isset($_POST['login'])) {
			$user = User::getUserFromCredentials($_POST['email'], $_POST['password']);
			if (is_null($user)) {
				echo "Failed to log in: invalid credentials";
				$this->welcome();
				return;
			}
			$_SESSION['user'] = $user;
			$this->main_menu();
			return;
		} else if (isset($_POST['logout'])) {
			session_unset();
		} else if (isset($_POST['createAccount'])) {
			$this->db->createAccount($_POST['email'], $_POST['password']);
		}

		

		$orderKey = "";
		$actionKey = "";
		$orderKeyExists = key_exists("orderKey", $_POST) || key_exists("orderKey", $_GET);
		$actionKeyExists = key_exists("action", $_POST) || key_exists("action", $_GET);

		if ($orderKeyExists) {
			$orderKey = key_exists("orderKey", $_POST) ? $_POST['orderKey'] : $_GET['orderKey'];
		}
		if ($actionKeyExists) {
			$actionKey = key_exists("action", $_POST) ? $_POST['action'] : $_GET['action'];
		}
		
		// direct user to View Lobbies page if they are
		// logged in and have not submitted an action,
		// such as when they log in -> close the tab -> open the tab
		if (array_key_exists('user', $_SESSION) && !$actionKeyExists) {
			$this->main_menu();
			return;
		}

		if(!$orderKeyExists && !$actionKeyExists) {
			// no action or orderKey means we're going to the welcome page
			$this->welcome();

		} else if ($orderKeyExists && !$actionKeyExists) {
			// orderKey exists but no actionKey mean swe're going to the view order page
			$this->view_order($orderKey);

		} else if ($actionKeyExists) {
			// if we have an action key, we're going to now check if it's value is start_new:
			switch ($actionKey) {
				case "start_new": 
					// if it is, we're going to generate an orderKey and make a new order, then direct user to view that order
					$this->start_new_order();
					break;
				case "editUser":
					echo $user->editUser();
					break;
				case "createLobby":
					if (isset($_POST['formSubmitted'])) {
						$this->create_lobby();
						// redirect to main menu as per POST-Redirect-GET
						// design pattern in order to prevent duplicate
						// form requests on refresh
						header("Location: ".$_SERVER['PHP_SELF']);
						break;
					}
					// user is navigating to the page, hasn't submitted the form
					echo $this->load_template("create_lobby", ["errorMsg" => ""]);
					break;
				case "resetPassword":
					echo $user->resetPassword();
					break;
				default: 
				// if it is not, we're going to check for an orderKey
				if ($orderKeyExists) {
					$this->handle_order_actions();
					// here we handle actions for the order
				} else {
					// we cannot handle actions without an order key, show welcome / error page
					echo "this is an error page :(";
				}
			}

		} else {
			// for debug's sake we'll make an error page that we can only reach when all other checks fail in case we've borked logic
		}
	}

	function welcome($warning = null) {
		$swapArray['testMessage'] = "This is a message to swap into our template."; // this will replace the tag {{testMessage}} in the template welcome.html

		$swapArray['warningMessage'] = "" . $warning == "" ? "" : $warning . "<br /><br />";

		// login form changes to logout form if user is logged in
		if (!isset($_SESSION['user'])) {
			$swapArray['loginLogoutForm'] = $this->load_template("loginForm");
		} else {
			$swapArray['loginLogoutForm'] = $this->load_template("logoutForm");
			$swapArray['userId'] = $_SESSION['user']->getId();
		}

		//echo getenv('CHOWCHOOSER_P');
		echo $this->load_template("welcome", $swapArray);
	}

	function view_order($orderKey) {
		if($orderKey == "") { // if the user presses "join" with no key entered, this will put them back to the welcome page with a warning
			$warning = "You must supply a lobby code to join a lobby!";
			$this->welcome($warning);
		} else {
			echo "<a href=\"?\"><input type=\"button\" value=\"Back\" /></a><br /><br />";
			echo "This is our view function! We're looking at order " . $orderKey . "!\n";

			//TODO database functionality to check if the order exists

			//Hardcoded food items
			$foodItems = [new foodItem("taco", 2.50), new foodItem("side of rice", 0.99), new foodItem("burger", 8.79), new foodItem("test", 1)];

			//echo "<br>";
			//echo $foodItems[1]->price;
			//$foodItems['foodItemsLength'] = (string) count($foodItems) + 1;

			$foodItemsString = "";

			// now we're going to iterate through our $swapArray to replace any {{tags}} in the template
			if ($foodItems != null) {
				foreach ($foodItems as $i) {
					$foodItemsString .= "<tr><td>".$i->description."</td><td>".$i->price."</td></tr>";
				}
			}

			echo $this->load_template("view_order", ["foodItemsString" => $foodItemsString]);
		}
	}

	function main_menu(): void {
		$swapArray['userId'] = $_SESSION['user']->getId();
		$swapArray['lobbies'] = "a long string of html that will represent the lobbies the user is in";

		// TODO getUsersLobbies returns an array of Lobby instances, and
		// I call some printLobbies($arrayOfLobbies) function
		// for easy customization of display and stuff
		$this->db->getUsersLobbies($_SESSION['user']->getId());
		
		echo $this->load_template("main_menu", $swapArray);
		return;
	}

	function start_new_order() {
		echo $this->view_order($this->generate_key());
	}

	function generate_key() {
		global $KEY_SALT;
		return md5($KEY_SALT.md5(date("Y-m-d h:i:sa"))); # the string after this date function is just specifying a format for how the date will output
	}

	function load_template($fileName, $swapArray = null) {
		$fileLocation = "templates/" . $fileName . ".html";
		$file = fopen($fileLocation, "r") or die("Could not load file!");
		$contents = fread($file, filesize($fileLocation));

		if ($swapArray == null) {
			return $contents;
		}

		foreach ($swapArray as $key => $value) {
			$contents = str_replace("{{".$key."}}", $value, $contents);
		}

		return $contents;
	}

	function handle_order_actions() {
		echo "this is where we handle in-order actions!";
	}

	function example_query() {
		$response = $this->db->query("describe lobbies;");
		$results = $response->fetch_assoc();

		// printing the array of results, or we can foreach loop through them
		echo "Here's our db results: ".print_r($results);
	}

	function view_lobby() {

		$swapArray['lobbyId'] = $_GET['showlobby'];
		
		$lobby = new Lobby($this->db);
		$lobby->getLobbyFromDatabase($_GET['showlobby']);

		$swapArray['votingEndTime'] = $lobby->getVotingEndTime();
		$swapArray['orderingEndTime'] = $lobby->getOrderingEndTime();

		switch ($lobby->getStatusId()) {
			case '1':
				//Lobby status: VOTING

				//$restaurant = new Restaurant($this->db);
				//$restaurant->getRestaurantFromDatabase(1);
				//$swapArray['restaurants'] = $restaurant->name;
				/*
				$restaurantArray = $lobby->getRestaurants();
				foreach ($restaurantArray as $i) {
					$restaurant = new Restaurant($this->db);
					$restaurant->getRestaurantFromDatabase($i['restaurant_id']);
					$restaurants[] = $restaurant;
				}
				print_r($restaurants);
				*/
				$restaurantsSwapValue = '';

				$restaurantArray = $lobby->getRestaurants();
				foreach ($restaurantArray as $i) {
					//print_r($i['restaurant_id']);
					$restaurant = new Restaurant($this->db);
					$restaurant->getRestaurantFromDatabase($i['restaurant_id']);

					$restaurantsSwapValue .= '<li>'.$restaurant->name.'</li>';
					//echo '<br>';
					//echo $restaurant->name;
					//echo '<br>';
				}
				//echo $restaurantsSwapValue;
				$swapArray['restaurants'] = $restaurantsSwapValue;

				echo $this->load_template('lobby_voting', $swapArray);
				break;
			case '2':
            $orders = Order::getOrdersFromUserAndLobby(
               $_SESSION['user']->getId(), $lobby->getId()
            );

            $orderDisplay = '<table style="text-align: left"><th>Quantity</th><th>Food</th><th>Order price</th>';
            $totalPrice = 0.0;
            foreach ($orders as $order) {
               $food = FoodItem::getFoodItemFromId($order->getFoodId());
               $orderPrice = $food->price * $order->quantity;
               $totalPrice += $orderPrice;
               $orderDisplay .= "<tr><td>"
                  .$order->quantity."</td><td>"
                  .$food->name."</td><td>"
                  .$orderPrice."</td>"
                  ."</tr>";
            }
            $orderDisplay .= "</table>";

            $swapArray['orderItems'] = $orderDisplay;
            $swapArray['lobbyName'] = $lobby->getName();
            $swapArray['totalPrice'] = $totalPrice;
            echo $this->load_template('lobby_ordering', $swapArray);
				break;
			case '3':
				//Lobby status: COMPLETED
				echo $this->load_template("lobby_completed", $swapArray);
				break;
			default:
				//TODO actually handle this error
				echo 'statusId = '.$lobby->getStatusId().'. It should not reach this point';
				break;
			}
		//echo 'id: '.$lobby->getId().' admin: '.$lobby->getAdminId().' name: '.$lobby->getName().' status: '.$lobby->getStatusId();

	}

	private function create_lobby() {
		$lobbyName = $_POST["lobbyName"];
		$doSkipVoting = empty($_POST["skipVoting"]);
		$orderingEndTime = $_POST["orderingEndTime"];

		// the voting end time is null if "skip voting" is enabled, which means
		// the voting end time key is not sent in $_POST
		$votingEndTime = key_exists('votingEndTime', $_POST) ? $_POST['votingEndTime'] : null;

		// if "skip voting" is enabled, then the voting end time is allowed to be null.
		// if "skip voting" is disabled, voting end time must not be null
		$votingFieldsAreValid = key_exists("skipVoting", $_POST) ? !empty($votingEndTime) : !is_null($votingEndTime);

		if (empty($lobbyName) || $votingFieldsAreValid || empty($orderingEndTime)) {
			// reload the "create lobby" form with an error message
			echo $this->load_template("create_lobby", ["errorMsg" => "Please enter data in all fields."]);
			// do not return to calling function, we have already
			// handled all page logic
			exit();
		}

		Lobby::createLobbyInDatabase(
			$lobbyName,
			$votingEndTime,
			$orderingEndTime
		);
	}

}
?>
