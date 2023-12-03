<?php
require_once "classes/Database.php";
require_once "classes/FoodItem.php";
require_once "classes/User.php";
require_once "classes/OrderCreation.php";
require_once "classes/Lobby.php";
require_once "classes/Restaurant.php";
require_once "classes/Vote.php";
require_once "classes/Order.php";

class ChowChooserEngine {
	public $db;

	function __construct() {

		// direct user to the welcome page if:
		// 1) User is not logged in, and
		// 2) User is not logging in, and
		// 3) User is not creating an account
		if (empty($_SESSION) && !isset($_POST['login']) && !isset($_GET['action'])) {
			$this->welcome();
			return;
		}

		$this->db = new Database();

		// uncomment the following line to see results of example query - sorry it breaks page formatting!
		//$this->example_query();

		if (isset($_POST['login'])) {
			$user = User::getUserFromCredentials($_POST['email'], $_POST['password']);
			if (is_null($user)) {
				$this->welcome("Failed to log in: invalid credentials");
				return;
			}
			$_SESSION['user'] = $user;
			$this->main_menu();
			return;
		} else if (isset($_POST['logout'])) {
			session_unset();
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



		// login form changes to logout form if user is logged in
		if (!isset($_SESSION['user'])) {
			$swapArray['loginLogoutForm'] = $this->load_template("loginForm");
		} else {
			$swapArray['loginLogoutForm'] = $this->load_template("logoutForm");
			$swapArray['userId'] = $_SESSION['user']->getUsername();
		}
		$this->swapArray = $swapArray;
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
				case "viewPlaceOrderSample":
					$order = new OrderCreation($_GET['lobbyId']);
					$order->viewAddOrderItem();
					break;
				case "processAddOrderItem":
					$order = new OrderCreation($_GET['lobbyId']);
					$order->processAddOrderItem();
					break;
				case "processRemoveOrderItem":
					$order = new OrderCreation($_GET['lobbyId']);
					$order->processRemoveOrderItem();
					break;
				case "createAccount":
					$this->createAccount();
					/* echo $this->main_menu(); */
					header("Location: ".$_SERVER['PHP_SELF']);
					break;
				case "showlobby":
				   if (isset($_POST['deleteOrderRequest'])) {
					  Order::deleteOrderById($_POST['orderId']);
				   }
					$this->view_lobby();
					break;
				case "main":
					//easy way to navigate to main menu
					$this->main_menu();
					break;
				case "vote":
					$user = $_SESSION['user'];
					Vote::toggleVote($user->getId(), $_GET['restaurantId'], $_GET['lobby']);
					$this->view_lobby();
					break;
				default: 

					// we cannot handle actions without an order key, show welcome / error page
					echo $_POST['action'].'<br>'.$_GET['action'].'<br>';
					echo "this is an error page :(";
				
			}

		} else {
			// for debug's sake we'll make an error page that we can only reach when all other checks fail in case we've borked logic
		}
	}

	function welcome($warning = null) {
		
		$swapArray['warningMessage'] = "" . $warning == "" ? "" : $warning;

		//~ // login form changes to logout form if user is logged in
		//~ if (!isset($_SESSION['user'])) {
			//~ $swapArray['loginLogoutForm'] = $this->load_template("loginForm");
		//~ } else {
			//~ $swapArray['loginLogoutForm'] = $this->load_template("logoutForm");
			//~ $swapArray['userId'] = $_SESSION['user']->getId();
		//~ }
		
		$swapArray['loginLogoutForm'] = "";
		$swapArray['mainContent'] = $this->load_template("welcome", $swapArray);
		echo $this->load_template("base", $swapArray);
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
		$swapArray['loginLogoutForm'] = $this->load_template("logoutForm");
		$swapArray['userName'] = $_SESSION['user']->getUsername();
		$all_user_lobbies=Lobby::getUsersLobbies($_SESSION['user']->getId());
		
		$swapArray['lobbies'] = $all_user_lobbies;

		$swapArray['mainContent'] = $this->load_template("main_menu", $swapArray);
		echo $this->load_template("base", $swapArray);
		
		return;
	}

	function start_new_order() {
		echo $this->view_order($this->generate_key());
	}

	function generate_key() {
		global $KEY_SALT;
		return md5($KEY_SALT.md5(date("Y-m-d h:i:sa"))); # the string after this date function is just specifying a format for how the date will output
	}

	public static function load_template($fileName, $swapArray = null) {
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


	function example_query() {
		$response = $this->db->query("describe lobby;");
		$results = $response->fetch_assoc();

		// printing the array of results, or we can foreach loop through them
		echo "Here's our db results: ".print_r($results);
	}

	function view_lobby() {

		$swapArray = $this->swapArray;
		$swapArray['lobbyId'] = $_GET['lobby'];
		$lobby = Lobby::getLobbyFromDatabase($_GET['lobby']);
		$userId = $_SESSION['user']->getId();
		$userIsAdmin = $userId == $lobby->getAdminId();

		$swapArray['votingEndTime'] = $lobby->getVotingEndTime();
		$swapArray['orderingEndTime'] = $lobby->getOrderingEndTime();

		switch ($lobby->getStatusId()) {
			
			case '1':
				//Lobby status: VOTING
				/*
				HTML TABLE ROW FORMAT

				<form action="?action=vote&lobby=1&restaurantId=1" method="post">
					<input type="submit" value="Hardcoded value for looby=1$restaurantId=1"/>
				</form>
				*/

				$tableContentSwapValue = '';

				$restaurantArray = $lobby->getRestaurants();
				foreach ($restaurantArray as $i) {
					$restaurant = Restaurant::getRestaurantFromDatabase($i['restaurant_id']);

					if(Vote::getUsersVote($userId, $lobby->getId()) == $restaurant->id) {
						$hasVoted = ' style="background-color: red;" ';
					} else {
						$hasVoted = ' ';
					}

					$tableContentSwapValue .= '<tr><td>'.$restaurant->name.'</td><td><form action="?action=vote&lobby='.$lobby->getId().
						'&restaurantId='.$restaurant->id.'" method="post"><input type="submit"'.$hasVoted.'value="Vote for '.$restaurant->name.'"/></form></td><td>Votes: '.
							Vote::getVotesForRestaurant($restaurant->id, $lobby->getId()).'</td></tr>';

				}

				$swapArray['tableContent'] = $tableContentSwapValue;

				echo $this->load_template('lobby_voting', $swapArray);
				break;

			case '2':
				// display orders from all users if you are the lobby admin,
				// otherwise just display your own
				if ($userIsAdmin) {
				   $orders = Order::getOrdersFromLobby($lobby->getId());
				} else {
				   $orders = Order::getOrdersFromUserAndLobby(
					  $userId,
					  $lobby->getId()
				   );
				}
				$orderTableRows = "";
				$adminColumnHeader = $userIsAdmin ? "<th>User</th>" : "";
			   
				$subtotal = 0.0;
				foreach ($orders as $order) {
				   $food = FoodItem::getFoodItemFromId($order->getFoodId());
				   $orderPrice = $food->price * $order->quantity;
				   $subtotal += $orderPrice;
				   $username = "";
				   if ($userIsAdmin) {
					  $username = User::getUserFromId($order->getUserId())->getUsername();
				   }
				   $rowSwap = Array();
				   $rowSwap['adminColumn'] = $userIsAdmin ? "<td>".$username."</td>" : "";
				   $rowSwap['foodName'] = $food->name;
				   $rowSwap['orderQty'] = $order->quantity;
				   $rowSwap['orderPrice'] = $orderPrice;
				   $rowSwap['orderId'] = $order->id;
				   
					$orderTableRows .= $this->load_template('lobbyOrderRow', $rowSwap);
				}

				// saves if/else indentation, even if it overwrites previous work
				if (empty($orders)) {
				   $orderDisplay = "<p>You have no orders in this lobby!</p>";
				}
				
				if (empty($orders)) {
					$swapArray['orderItems'] = "<p>You have no orders in this lobby!</p>";
				} else {
					$swapArray['orderItems'] = $this->load_template('lobbyOrderTable', ["orderTableRows" => $orderTableRows, "adminColumnHeader" => $adminColumnHeader]);
				}

				$swapArray['lobbyName'] = $lobby->getName();
				$swapArray['subtotal'] = $subtotal;
				$swapArray['taxes'] = number_format(round($subtotal * 0.06, 2), 2);
				$swapArray['totalPrice'] = number_format(round($subtotal * 1.06, 2), 2);
				// required for placing orders
				$swapArray['lobbyId'] = $lobby->getId();
				echo $this->load_template('base', ['mainContent' => $this->load_template('lobby_ordering', $swapArray), 'loginLogoutForm' => $this->load_template("logoutForm")]);
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

      // error message appears if:
      // (1) lobby name is empty
      // (2) voting end time is empty WHILE user did not elect to skip voting
      // (3) ordering end time is empty
		if (empty($lobbyName) || (is_null($votingEndTime) && !key_exists('skipVoting', $_POST)) || empty($orderingEndTime)) {
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

	private function createAccount() {
		// user is navigating to the page, has not submitted form
		if (!isset($_POST['formSubmitted'])) {
			$swap['loginLogoutForm'] = $this->load_template("logoutForm");
			$swap['mainContent'] = $this->load_template("createAccount", ["errorMsg" => ""]);
			echo $this->load_template("base", $swap);
			exit();
		}

		$email = $_POST['email'];
		$username = $_POST['username'];
		$password = $_POST['password'];

		if (empty($email) || empty($password) || empty($username)) {
			$errorMsg = "Please fill in all input fields.";
			$swap['loginLogoutForm'] = $this->load_template("logoutForm");
			$swap['mainContent'] = $this->load_template("createAccount", ["errorMsg" => $errorMsg]);
			echo $this->load_template("base", $swap);
			exit();
		}

		$user = User::createUserInDatabase($email, $password, $username);

		if (is_null($user)) {
			$errorMsg = "Something went wrong: error writing user to database";
			$swap['loginLogoutForm'] = $this->load_template("logoutForm");
			$swap['mainContent'] = $this->load_template("createAccount", ["errorMsg" => $errorMsg]);
			echo $this->load_template("base", $swap);
			exit();
		}

		$_SESSION['user'] = $user;
	}

}
?>
