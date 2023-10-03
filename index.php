<?php

$KEY_SALT = "This is a salt for our key generator";

class chowChooserEngine {
	
	function __construct() {
		
		// pseudo code time
		/*
		
		if no action is defined, we're either going to the welcome page or the view order page depending on a key being present or not.
		
		First we check for an action var existing
		
		Then we check of an order key var existing
		
		if no action key and no order key, then we go to welcome page
		if no order key but action key, perform action
		if no action but order key, go to view page
		
		
		
		
		
		
		
		
		
		New pseudo code now that I'm in better shape:
		
		1. Ways a user can access ChowChooser
			A. From visiting the base url
			B. From getting an invitiation link to an existing order
			
		2. Which page is a user greeted with for these methods:
			A. Welcome page, prompted to select a new order or join an order by entering a key
			B. View order page, directly joining an existing order using URL key
			
		3. What keys do we need to check for in the URL?
			A. Welcome page shows if no get params are detected
			B. View Order page as long as order_key exists 
			
		
		How does this make sense from key detection logic?
		
		1. We need to look for an orderKey key existing in the URL
			
			- If we find it, we can try to view the order
				-- This results in view_order page being called
				
			- If we have the orderKey but not value is assigned, show welcome page but with warning stating that user must supply key
			- If we have the orderKey but assigned value was not found in database, show welcome page but with warning staing that order with key was not found. 
				-- These two result in returning to the welcome page, but with an error displayed
				
			- If we have an orderKey and also an action, we can try to interact with the Order
				- Run command for action before returning to view order page
				- Available commands are:
					- startNewOrder: begins a new order by generating a key and redirecting to view_order page
					- addFoodOption: adds a food option to the current order
					- deleteFoodOption: removes a food option from the current order
					- updateFoodOption: alter an existing food option from current order
					- voteFoodOption: place a vote for this food option
					- unvoteFoodOption: remove a vote fo rthis food option
						-- These all result in some php processing on the backend and then return user to view page
						
		This to glean from notes:
			- The only action that can be performed without a orderKey is startNewOrder, which shouldn't be called anywhere except the base welcome page
			- Other actions cannot be performed without an orderKey
			 
		so we can assume:
			- User with no keys at all goes to welcome page (if no orderKey or actionKey exists)
			- User with an order key and no other action goes to view (if no actionKey exists but orderKey does) yes to 
			- User with an order key and actions goes to process, then view (if orderKey Exists and actionKey exists) yes to both
			- Other case: if an user provides an action with no order key (no orderKey, actionKey != startNewOrder) send them to welcome page and say no key specified
			
		$orderKeyExists = if(key_exists("orderKey", $_GET))
		$actionKeyExists = if(key_exists("action", $_GET))
			
		if(!$orderKeyExists && !$actionKeyExists) {
			// no action or orderKey means we're going to the welcome page
			$this->welcome();
		} else if ($orderKeyExists && !$actionKeyExists) {
			// orderKey exists but no actionKey mean swe're going to the view order page
			$this->view_order();
		} else if ($actionKeyExists) {
			// if we have an action key, we're going to now check if it's value is start_new:
				if ($_GET["action"] == "start_new") {
					// if it is, we're going to generate an orderKey and make a new order, then direct user to view that order
					$this->start_new_order();
				} else {
					// if it is not, we're going to check for an orderKey
					if ($orderKeyExists) {
						$this->handleOrderActions();
						// here we handle actions for the order
					} else {
						// we cannot handle actions without an order key, show welcome / error page
					}
				}
				
				
		} else {
			// for debug's sake we'll make an error page that we can only reach when all other checks fail in case we've borked logic
		}	
			
		*/
		
		if (!key_exists("orderKey", $_GET)) {
			// this is a basic welcome page to help people get a new order started or join an order
			$this->welcome();
		} elseif (key_exists("action", $_GET)) {
			// here's where we'll handle different actions which load different pages
			if ($_GET["action"] == "start_new") {
				// this is for setting up new orders
				$this->start_new_order();
			}
			
		} else {
			$this->view_order();
		}
	}
	
	function welcome() {
		echo $this->generate_key();
		
		//echo $this->load_template("welcome");
	}
	
	function view_order() {
		echo "This is our view function! We're looking at order " . $_GET["orderKey"] . "!\n";
	}
	
	function start_new_order() {
		
		echo "here's our key: " . $this->generate_key();
	}
	
	function generate_key() {
		global $KEY_SALT;
		return md5($KEY_SALT.md5(date("Y-m-d h:i:sa")));
	}
	
	function load_template($fileName, $thingsToReplace) {
		$fileLocation = "templates/" . $fileName . ".html";
		$file = fopen($fileLocation, "r") or die("Could not load file!");
		$contents = fread($file, filesize($fileLocation));
		return $contents;
	}
	
	function handle_order_actions() {
		echo "this is where we handle in-order actions!";
	}
}


$order = new chowChooserEngine();
?>
