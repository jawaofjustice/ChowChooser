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
	public Database $db;
	private array $swapArray;

	public function __construct() {

		// direct user to the welcome page if:
		// 1) User is not logged in, and
		// 2) User is not logging in, and
		// 3) User is not creating an account
		if (empty($_SESSION) && !isset($_POST['login']) && !isset($_GET['action'])) {
			$this->welcome();
			return;
		}

		$this->db = new Database();

		if (isset($_POST['login'])) {
			$user = User::readUserByCredentials($_POST['email'], $_POST['password']);
			if (is_null($user)) {
				$this->welcome("Failed to log in: invalid credentials");
				return;
			}
			$_SESSION['user'] = $user;
			$this->mainMenu();
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
			$this->mainMenu();
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
			$this->viewOrder($orderKey);

		} else if ($actionKeyExists) {
			// if we have an action key, we're going to now check if it's value is start_new:
			switch ($actionKey) {
				case "start_new": 
					// if it is, we're going to generate an orderKey and make a new order, then direct user to view that order
					$this->startNewOrder();
					break;
				case "editUser":
					echo $user->editUser();
					break;
            case "joinLobby":
               $inviteCode = $_GET['inviteCode'];
               $this->swapArray['errorMsg'] = $_SESSION['user']->joinLobby($inviteCode);
               $this->mainMenu();
               break;
				case "createLobby":
					$restaurantLabels = "";
					$restaurantCheckboxes = "";
					foreach (Restaurant::readAllRestaurants() as $restaurant) {
						$restaurantLabels .= '<label for="restaurant'.$restaurant->id.'">'
							.$restaurant->name . "</label><br />";
						$restaurantCheckboxes .= '<input type="checkbox"'
							.'name="selectedRestaurant'.$restaurant->id.'"'
							.'value="'.$restaurant->id.'"><br />';
					}

					// user is navigating to the page, hasn't submitted the form
					$this->swapArray['restaurantLabels'] = $restaurantLabels;
					$this->swapArray['restaurantCheckboxes'] = $restaurantCheckboxes;
					$this->swapArray['errorMsg'] = "";

					if (isset($_POST['formSubmitted'])) {
						$this->createLobby();
						// redirect to main menu as per POST-Redirect-GET
						// design pattern in order to prevent duplicate
						// form requests on refresh
						header("Location: ".$_SERVER['PHP_SELF']);
						break;
					}
					//echo $this->load_template("create_lobby", $this->swapArray);
					echo $this->load_template('base', [
										'title' => "Create a Lobby",
										'mainContent' => $this->load_template('create_lobby', $this->swapArray), 
										'loginLogoutForm' => $this->load_template('logoutForm'),
										'backButton' => $this->load_template('backButton', ["backLink" => "?"])
										]);
					break;
				case "deleteLobby":
					$lobby = Lobby::readLobby($_GET['lobbyId']);
					if($_SESSION['user']->getId() == $lobby->getAdminId())
						Lobby::deleteLobby($lobby->getId());
					else
						Lobby::deleteUserFromLobby($_SESSION['user']->getId(), $lobby->getId());
					$this->swapArray['errorMsg'] = "Lobby ".$lobby->getName()." Deleted";
					$this->mainMenu();
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
					$this->viewLobby();
					break;
				case "main":
					//easy way to navigate to main menu
					$this->mainMenu();
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

	/**
	* Displays the "welcome" page.
	*
	* @param string|null $warning An optional warning message to display.
	*/
	private function welcome(string $warning = null) {
		
		$swapArray['warningMessage'] = "" . $warning == "" ? "" : $warning;

		$swapArray['loginLogoutForm'] = "";
		$swapArray['mainContent'] = $this->load_template("welcome", $swapArray);
		$swapArray['backButton'] = "";
		$swapArray['title'] = "Welcome!";
		echo $this->load_template("base", $swapArray);
	}

	private function viewOrder(string $orderKey) {
		if($orderKey == "") { // if the user presses "join" with no key entered, this will put them back to the welcome page with a warning
			$warning = "You must supply a lobby code to join a lobby!";
			$this->welcome($warning);
		} else {
			echo "<a href=\"?\"><input type=\"button\" value=\"Back\" /></a><br /><br />";
			echo "This is our view function! We're looking at order " . $orderKey . "!\n";

			//Hardcoded food items
			$foodItems = [new foodItem("taco", 2.50), new foodItem("side of rice", 0.99), new foodItem("burger", 8.79), new foodItem("test", 1)];

			$foodItemsString = "";

			// display each food item in HTML
			if ($foodItems != null) {
				foreach ($foodItems as $i) {
					$foodItemsString .= "<tr><td>".$i->description."</td><td>".$i->price."</td></tr>";
				}
			}

			echo $this->load_template("view_order", ["foodItemsString" => $foodItemsString]);
		}
	}

	/**
	* Displays the "main menu" page.
	*/
	private function mainMenu(): void {
		$this->swapArray['userId'] = $_SESSION['user']->getId();
		$this->swapArray['loginLogoutForm'] = $this->load_template("logoutForm");
		$this->swapArray['userName'] = $_SESSION['user']->getUsername();
		$this->swapArray['title'] = "Main Menu";
		if (!key_exists('errorMsg', $this->swapArray))
			$this->swapArray['errorMsg'] = "";

		$all_user_lobbies = Lobby::getUsersLobbies($_SESSION['user']->getId());
		$this->swapArray['lobbies'] = $all_user_lobbies;
		$this->swapArray['mainContent'] = $this->load_template("main_menu", $this->swapArray);
		$this->swapArray['backButton'] = "";
		echo $this->load_template("base", $this->swapArray);
		
		return;
	}

	private function startNewOrder() {
		echo $this->viewOrder($this->generateKey());
	}

	private function generateKey() {
		global $KEY_SALT;
		$dateFormat = "Y-m-d h:i:sa";
		return md5($KEY_SALT.md5(date($dateFormat)));
	}

	/**
	* Displays a page based on a template.
	*
	* @param string $fileName The basename of the HTML template.
	* @param array $swapArray An array of text/HTML to inject into the template.
	*/
	public static function load_template(string $fileName, array $swapArray = null) {
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


	private function exampleQuery() {
		$response = $this->db->query("describe lobby;");
		$results = $response->fetch_assoc();

		// printing the array of results, or we can foreach loop through them
		echo "Here's our db results: ".print_r($results);
	}

	/**
	* Displays the "view lobby" page.
	*/
	private function viewLobby(): void {

		$swapArray = $this->swapArray;
		$swapArray['lobbyId'] = $_GET['lobby'];
		$lobby = Lobby::readLobby($_GET['lobby']);
		$userId = $_SESSION['user']->getId();
		$userIsAdmin = $userId == $lobby->getAdminId();
		$statusId = $lobby->getStatusId();
		$swapArray['orderingEndTime'] = $lobby->getOrderingEndTime();

		if ($userIsAdmin) {
			$inviteCode = $lobby->getInviteCode();
			$swapArray['adminView'] = "<br><h2>You are the admin!";
			// as long as status is not complete, show invite code
			if ($statusId != 3) {
				//<span id="myText" style="cursor: pointer; text-decoration: underline; color: blue;">Click me</span>
				$swapArray['adminView'] .= ' Lobby invite code: <span id="myText" style="cursor: pointer; text-decoration: underline; color: blue;">'.$inviteCode.'</span>';
			}
			$swapArray['adminView'] .= "</h2><br>";
			$swapArray['copyToClipboard'] = $this->load_template("copyToClipboard");
		} else {
			$swapArray['adminView'] = "";
			$swapArray['copyToClipboard'] = "";
		}

		switch ($statusId) {
			
			case '1': // VOTING PHASE
				/*
				HTML TABLE ROW FORMAT

				<form action="?action=vote&lobby=1&restaurantId=1" method="post">
					<input type="submit" value="Hardcoded value for looby=1$restaurantId=1"/>
				</form>
				*/

				$swapArray['votingEndTime'] = date_format(new Datetime($lobby->getVotingEndTime()),"M j, Y H:i:s");

				$tableContentSwapValue = '';

				$restaurantArray = $lobby->getRestaurants();
				$userHasVoted = Vote::userHasVoted($userId, $lobby->getId());
				foreach ($restaurantArray as $r) {
					$restaurant = Restaurant::readRestaurant($r->getId());
					
					$votedForThisRestaurant = Vote::readVote($userId, $lobby->getId()) == $restaurant->id;
					if($votedForThisRestaurant) {
						$hasVotedStyle = ' votedButton';
					} else {
						$hasVotedStyle = ' ';
					}

					$numVotes = Vote::readVotesForRestaurant($restaurant->id, $lobby->getId());
					$rowSwap = Array();
					$rowSwap['restaurantName'] = $restaurant->name;
					$rowSwap['lobbyId'] = $lobby->getId();
					$rowSwap['restaurantId'] = $restaurant->getId();
					$rowSwap['hasVotedStyle'] = $hasVotedStyle;
					$rowSwap['numVotes'] = $numVotes;
					$rowSwap['removeVoteText'] = $votedForThisRestaurant ? "Remove vote" : "Vote";
					$rowSwap['hideVoteButton'] = $votedForThisRestaurant || !$userHasVoted ? "" :"style=\"display:none;\"" ;
					
					$tableContentSwapValue .= $this->load_template('lobbyVotingRow', $rowSwap);
				}


				$swapArray['tableContent'] = $tableContentSwapValue;
				$swapArray['topRestaurant'] = $lobby->getWinningRestaurant()->name;
				$swapArray['lobbyName'] = $lobby->getName();
				
				$timerSwap['countDownTimeStart'] = date_format(new Datetime($lobby->getVotingEndTime()),"M j, Y H:i:s");
				$timerSwap['elementToUpdate'] = 'orderEndTimeHolder';
				$timerSwap['countDownEndText'] = 'None, voting has concluded!';
				$swapArray['countDownTimer'] = $this->load_template('countDownTimer', $timerSwap);
				echo $this->load_template('base', [
										'title' => "Voting for " . $lobby->getName(),
										'mainContent' => $this->load_template('lobby_voting', $swapArray), 
										'loginLogoutForm' => $this->load_template('logoutForm'),
										'backButton' => $this->load_template('backButton', ["backLink" => "?"])
										]);
				break;

			case '2': // ORDERING PHASE

				// display the name of restaurant that won during voting phase
				$swapArray['restaurant'] = $lobby->getWinningRestaurant()->name;

				// display orders from all users if user is the lobby admin,
				// otherwise just display the user's 
				if ($userIsAdmin) {
				   $orders = Order::readLobbyOrders($lobby->getId());
				} else {
				   $orders = Order::readUserOrdersByLobby(
					  $userId,
					  $lobby->getId()
				   );
				}

				
				
				$orderTableRows = "";
				$adminColumnHeader = $userIsAdmin ? "<th>User</th>" : "";
				   
				$subtotal = 0.0;
				foreach ($orders as $order) {
					
					
					$food = FoodItem::readFoodItem($order->getFoodId());
					$orderPrice = $food->price * $order->quantity;
					$subtotal += $orderPrice;
					$username = "";
					if ($userIsAdmin) {
					  $username = User::readUserById($order->getUserId())->getUsername();
					}
					$rowSwap = Array();
					$rowSwap['adminColumn'] = $userIsAdmin ? "<td>".$username."</td>" : "";
					$rowSwap['foodName'] = $food->name;
					$rowSwap['orderQty'] = $order->quantity;
					$rowSwap['orderPrice'] = $orderPrice;
					$rowSwap['orderId'] = $order->id;

					$orderTableRows .= $this->load_template('lobbyOrderRow', $rowSwap); 
					
				}

            if (empty($orders)) {
                $swapArray['orderItems'] = "<div class=\"centeredWarning\"><h3>You have no orders in this lobby!</h3></div>";
            } else {
                $swapArray['orderItems'] = $this->load_template('lobbyOrderTable', ["orderTableRows" => $orderTableRows, "adminColumnHeader" => $adminColumnHeader]);
            }

            $swapArray['lobbyName'] = $lobby->getName();
            $swapArray['subtotal'] = $subtotal;
            $swapArray['taxes'] = number_format(round($subtotal * 0.06, 2), 2);
            $swapArray['totalPrice'] = number_format(round($subtotal * 1.06, 2), 2);
            // required for placing orders
            $swapArray['lobbyId'] = $lobby->getId();
            
            $swapArray['orderingEndTime'] = $timerSwap['countDownTimeStart'] = date_format(new Datetime($lobby->getOrderingEndTime()),"M j, Y H:i:s");;
            $timerSwap['countDownTimeStart'] = $timerSwap['countDownTimeStart'] = date_format(new Datetime($lobby->getOrderingEndTime()),"M j, Y H:i:s");;
            $timerSwap['elementToUpdate'] = 'orderEndTimeHolder';
            $timerSwap['countDownEndText'] = 'None, ordering has concluded!';
			$swapArray['countDownTimer'] = $this->load_template('countDownTimer', $timerSwap);
            echo $this->load_template('base', [
										'title' => "Ordering for " . $lobby->getName(),
										'mainContent' => $this->load_template('lobby_ordering', $swapArray), 
										'loginLogoutForm' => $this->load_template('logoutForm'),
										'backButton' => $this->load_template('backButton', ["backLink" => "?"])
										]);
				break;

			case '3': // COMPLETED

				// display the name of the restaurant that wins the voting phase
			$winningRestaurantId = array_values($lobby->getRestaurants())[0]->getId();
			$restaurant = Restaurant::readRestaurant($winningRestaurantId);
			$swapArray['restaurant'] = $restaurant->name;

            // display orders from all users if you are the lobby admin,
            // otherwise just display your own
            if ($userIsAdmin) {
               $orders = Order::readLobbyOrders($lobby->getId());
            } else {
               $orders = Order::readLobbyOrders(
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
            $orderTableRows = "";
            $adminColumnHeader = $userIsAdmin ? "<th>User</th>" : "";
            foreach ($orders as $order) {
               $food = FoodItem::readFoodItem($order->getFoodId());
               $orderPrice = $food->price * $order->quantity;
               $subtotal += $orderPrice;
               $username = "";
				if ($userIsAdmin) {
				  $username = User::readUserById($order->getUserId())->getUsername();
				}
                  
				$rowSwap = Array();
				$rowSwap['adminColumn'] = $userIsAdmin ? "<td>".$username."</td>" : "";
				$rowSwap['foodName'] = $food->name;
				$rowSwap['orderQty'] = $order->quantity;
				$rowSwap['orderPrice'] = $orderPrice;
				$rowSwap['orderId'] = $order->id;

				$orderTableRows .= $this->load_template('lobbyCompleteRow', $rowSwap); 
            }
            $orderDisplay .= "</table>";

            if (empty($orders)) {
                $swapArray['orderItems'] = "<div class=\"centeredWarning\"><h3>You have no orders in this lobby!</h3></div>";
            } else {
                $swapArray['orderItems'] = $this->load_template('lobbyOrderTable', ["orderTableRows" => $orderTableRows, "adminColumnHeader" => $adminColumnHeader]);
            }

            $swapArray['lobbyName'] = $lobby->getName();
            $swapArray['subtotal'] = $subtotal;
            $swapArray['taxes'] = number_format(round($subtotal * 0.06, 2), 2);
            $swapArray['totalPrice'] = number_format(round($subtotal * 1.06, 2), 2);
            // required for placing orders
            $swapArray['lobbyId'] = $lobby->getId();
            
				echo $this->load_template('base', [
									'title' => "Ordering for " . $lobby->getName(),
									'mainContent' => $this->load_template('lobby_completed', $swapArray), 
									'loginLogoutForm' => $this->load_template('logoutForm'),
									'backButton' => $this->load_template('backButton', ["backLink" => "?"])
									]);
				break;

			default:
				//TODO actually handle this error
				echo 'statusId = '.$lobby->getStatusId().'. It should not reach this point';
				break;

			}

	}

	/**
	* Handles the "create lobby" page form data.
	*
	* Enforces certain rules before creating a new lobby.
	*/
	private function createLobby(): void {
		$lobbyName = $_POST["lobbyName"];
		$doSkipVoting = empty($_POST["skipVoting"]);
		$orderingEndTime = $_POST["orderingEndTime"];
		

		// the voting end time is null -> skip voting phase
		$votingEndTime = key_exists('votingEndTime', $_POST) ? $_POST['votingEndTime'] : null;

		// user must select at least one restaurant
		$noRestaurantIsSelected = true;

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

		// error message appears if one of the following is true:
		// (1) lobby name is empty
		// (2) voting end time is empty WHILE user did not elect to skip voting
		// (3) ordering end time is empty
		// (4) no restaurant has been selected
		if (empty($lobbyName) || (is_null($votingEndTime) && !key_exists('skipVoting', $_POST)) || empty($orderingEndTime) || $noRestaurantIsSelected) {
			$this->swapArray["errorMsg"] = "Please enter data in all fields.";
			//echo $this->load_template("create_lobby", $this->swapArray);
			echo $this->load_template('base', [
										'title' => "Create a Lobby",
										'mainContent' => $this->load_template('create_lobby', $this->swapArray), 
										'loginLogoutForm' => $this->load_template('logoutForm'),
										'backButton' => $this->load_template('backButton', ["backLink" => "?"])
										]);
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

	/**
	* Handles the "create account" page form data.
	*
	* Enforces certain rules before creating a new user.
	*/
	private function createAccount(): void {
		// user is navigating to the page, has not submitted form
		if (!isset($_POST['formSubmitted'])) {
			$swap['loginLogoutForm'] = "";
			$swap['mainContent'] = $this->load_template("createAccount", ["errorMsg" => ""]);
			$swap['title'] = "Create an Account";
			$swap['backButton'] = $this->load_template("backButton", ["backLink" => "?"]);
			echo $this->load_template("base", $swap);
			exit();
		}

		$email = $_POST['email'];
		$username = $_POST['username'];
		$password = $_POST['password'];

		if (empty($email) || empty($password) || empty($username)) {
			$errorMsg = "Please fill in all input fields.";
			$swap['loginLogoutForm'] = "";
			$swap['mainContent'] = $this->load_template("createAccount", ["errorMsg" => $errorMsg]);
			$swap['title'] = "Create an Account";
			$swap['backButton'] = $this->load_template("backButton", ["backLink" => "?"]);
			echo $this->load_template("base", $swap);
			exit();
		}

		$user = User::createUserInDatabase($email, $password, $username);

		if (is_null($user)) {
			$errorMsg = "Something went wrong: error writing user to database";
			$swap['loginLogoutForm'] = "";
			$swap['mainContent'] = $this->load_template("createAccount", ["errorMsg" => $errorMsg]);
			$swap['title'] = "Create an Account";
			$swap['backButton'] = $this->load_template("backButton", ["backLink" => "?"]);
			echo $this->load_template("base", $swap);
			exit();
		}

		$_SESSION['user'] = $user;
	}

}
?>
