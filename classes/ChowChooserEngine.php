<?php
require_once "classes/FoodItem.php";
require_once "classes/User.php";
require_once "classes/Database.php";

class ChowChooserEngine {
	
	function __construct() {
		$this->db = Database::connect();
		
		// uncomment the following line to see results of example query - sorry it breaks page formatting!
		//$this->example_query();
		
		$orderKeyExists = key_exists("orderKey", $_POST);
		$actionKeyExists = key_exists("action", $_POST);
			
		if(!$orderKeyExists && !$actionKeyExists) {
			// no action or orderKey means we're going to the welcome page
			$this->welcome();
		} else if ($orderKeyExists && !$actionKeyExists) {
			// orderKey exists but no actionKey mean swe're going to the view order page
			$this->view_order($_POST['orderKey']);
		} else if ($actionKeyExists) {
			// if we have an action key, we're going to now check if it's value is start_new:
				switch ($_POST['action']) {
					case "start_new": 
							// if it is, we're going to generate an orderKey and make a new order, then direct user to view that order
							$this->start_new_order();
						break;
					case "createUser":
							$user = new User;
							echo $user->createUser();
						break;
					case "editUser":
							echo $user->editUser();
						break;
					case "resetPassword":
							echo $user->resetPassword();
						break;
					case "login":
							$user = new User;
							echo $user->login();
						break;
					case "logout":
							echo $user->logout();
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
		$swapArray['loginForm'] = $this->load_template("loginForm"); //using load template to insert a subtemplate
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
		
		// now we're going to iterate through our $swapArray to replace any {{tags}} in the template
		
		if ($swapArray != null) {
			foreach ($swapArray as $key => $value) {
				$contents = str_replace("{{".$key."}}", $value, $contents);
			}
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
}
?>
