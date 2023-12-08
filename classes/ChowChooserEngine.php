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
	private $swapArray;

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
			$user = User::readUserByCredentials($_POST['email'], $_POST['password']);
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
            case "joinLobby":
               $_SESSION['user']->joinLobby($_GET['inviteCode']);
               $this->main_menu();
               break;
				case "createLobby":
					$restaurantInputs = "";
					foreach (Restaurant::readAllRestaurants() as $restaurant) {
						$restaurantInputs .= '<input type="checkbox"'
							.'name="selectedRestaurant'.$restaurant->id.'"'
							.'value="'.$restaurant->id.'">'
							.'<label for="restaurant'.$restaurant->id.'">'
							.$restaurant->name . "</label></br>";
					}

					// user is navigating to the page, hasn't submitted the form
					$this->swapArray['restaurantInputs'] = $restaurantInputs;
					$this->swapArray['errorMsg'] = "";

					if (isset($_POST['formSubmitted'])) {
						$this->create_lobby();
						// redirect to main menu as per POST-Redirect-GET
						// design pattern in order to prevent duplicate
						// form requests on refresh
						header("Location: ".$_SERVER['PHP_SELF']);
						break;
					}

					echo $this->load_template("create_lobby", $this->swapArray);
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
					Vote::toggleVote($user->getId(), $_POST['restaurantId'], $_POST['lobbyId']);
					// redirection as per Post/Redirect/Get design pattern
					header("Location: ".$_SERVER['PHP_SELF']."?action=showlobby&lobby=".$_POST['lobbyId']);
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
		
		$swapArray['warningMessage'] = "" . $warning == "" ? "" : $warning . "<br /><br />";

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
		$all_user_lobbies = $_SESSION['user']->readLobbies();
		
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
		$lobby = Lobby::readLobby($_GET['lobby']);
		$userId = $_SESSION['user']->getId();
		$userIsAdmin = $userId == $lobby->getAdminId();

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

				// placed here because could be null, but guaranteed to be not null
				// if lobby is in voting phase
				$swapArray['votingEndTime'] = $lobby->getVotingEndTime();

				$tableContentSwapValue = '<table>';

				$restaurantArray = $lobby->getRestaurants();
				foreach ($restaurantArray as $r) {
					$restaurant = Restaurant::readRestaurant($r->getId());

					if(Vote::readVote($userId, $lobby->getId()) == $restaurant->id) {
						$hasVotedStyle = ' style="background-color: red;" ';
					} else {
						$hasVotedStyle = ' ';
					}

					$numVotes = Vote::readVotesForRestaurant($restaurant->id, $lobby->getId());

					$tableContentSwapValue .= '<tr><td>'.$restaurant->name.'</td>
						<td><form action="" method="post">
						<input type="hidden" name="action" value="vote">
						<input type="hidden" name="lobbyId" value="'.$lobby->getId().'">
						<input type="hidden" name="restaurantId" value="'.$restaurant->getId().'">
						<input type="submit" '.$hasVotedStyle.' value="Vote for '.$restaurant->getName().'"/>
						</form></td>
						<td>Votes: '.$numVotes.'</td></tr>';

				}

				$tableContentSwapValue .= "</table>";

				$swapArray['tableContent'] = $tableContentSwapValue;
				$swapArray['topRestaurant'] = $lobby->getWinningRestaurant()->name;

				echo $this->load_template('lobby_voting', $swapArray);
				break;

			case '2':
            // display the name of the restaurant that wins the voting phase
            $swapArray['restaurant'] = $lobby->getWinningRestaurant()->name;

            // display orders from all users if you are the lobby admin,
            // otherwise just display your own
            if ($userIsAdmin) {
               $orders = Order::readLobbyOrders($lobby->getId());
            } else {
               $orders = Order::readUserOrdersByLobby(
                  $userId,
                  $lobby->getId()
               );
            }

            $orderDisplay = '<table style="text-align: left">';
            if ($userIsAdmin) {
               $orderDisplay .= "<th>Username</th>";
            }
            $orderDisplay .= '<th>Quantity</th><th>Food</th><th>Order price</th>';
            $subtotal = 0.0;
            foreach ($orders as $order) {
               $food = FoodItem::readFoodItem($order->getFoodId());
               $orderPrice = $food->price * $order->quantity;
               $subtotal += $orderPrice;
               $orderDisplay .= "<tr><td>";
               if ($userIsAdmin) {
                  $username = User::readUserById($order->getUserId())->getUsername();
                  $orderDisplay .= $username."</td><td>";
               }
               $orderDisplay .= $order->quantity."</td><td>"
                  .$food->name."</td><td>$"
                  .$orderPrice."</td>"
                  .'<td><form action="" method="post">
                  <input type="hidden" name="deleteOrderRequest" value="SO TRUE" />
                  <input type="hidden" name="orderId" value="'.$order->id.'" />
                  <input name="deleteOrder" type="submit" value="Delete"/>
                  </form></td></tr>';
            }
            $orderDisplay .= "</table>";

            // saves if/else indentation, even if it overwrites previous work
            if (empty($orders)) {
               $orderDisplay = "<p>You have no orders in this lobby!</p>";
            }

            $swapArray['orderItems'] = $orderDisplay;
            $swapArray['lobbyName'] = $lobby->getName();
            $swapArray['subtotal'] = $subtotal;
            $swapArray['taxes'] = number_format(round($subtotal * 0.06, 2), 2);
            $swapArray['totalPrice'] = number_format(round($subtotal * 1.06, 2), 2);
            // required for placing orders
            $swapArray['lobbyId'] = $lobby->getId();
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

		// user must select at least one restaurant
		$noRestaurantIsSelected = true;

		$selectedRestaurantIds  = array();
		foreach ($_POST as $key => $restaurantId) {
			if (str_starts_with($key, "selectedRestaurant")) {
				$noRestaurantIsSelected = false;
				array_push($selectedRestaurantIds, $restaurantId);
         }
      }

		// user must select at least one restaurant
		$noRestaurantIsSelected = true;

		$selectedRestaurants = array();
		foreach ($_POST as $key => $restaurantId) {
			if (str_starts_with($key, "selectedRestaurant")) {
				$noRestaurantIsSelected = false;
				$restaurant = Restaurant::readRestaurant($restaurantId);
				array_push($selectedRestaurants, $restaurant);
			}
		}

		// error message appears if:
		// (1) lobby name is empty
		// (2) voting end time is empty WHILE user did not elect to skip voting
		// (3) ordering end time is empty
		// (4) no restaurant has been selected
		if (empty($lobbyName) || (is_null($votingEndTime) && !key_exists('skipVoting', $_POST)) || empty($orderingEndTime) || $noRestaurantIsSelected) {
			$this->swapArray["errorMsg"] = "Please enter data in all fields.";
			echo $this->load_template("create_lobby", $this->swapArray);
			// do not return to calling function, we have already
			// handled all page logic
			exit();
		}

		Lobby::createLobby(
			$lobbyName,
			$votingEndTime,
			$orderingEndTime,
			$selectedRestaurants
		);
	}

	private function createAccount() {
		// user is navigating to the page, has not submitted form
		if (!isset($_POST['formSubmitted'])) {
			echo $this->load_template("createAccount", ["errorMsg" => ""]);
			exit();
		}

		$email = $_POST['email'];
		$username = $_POST['username'];
		$password = $_POST['password'];

		if (empty($email) || empty($password) || empty($username)) {
			$errorMsg = "Please fill in all input fields.";
			echo $this->load_template("createAccount", ["errorMsg" => $errorMsg]);
			exit();
		}

		$user = User::createUserInDatabase($email, $password, $username);

		if (is_null($user)) {
			$errorMsg = "Something went wrong: error writing user to database";
			echo $this->load_template("createAccount", ["errorMsg" => $errorMsg]);
			exit();
		}

		$_SESSION['user'] = $user;
	}

}
?>
