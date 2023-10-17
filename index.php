<?php

$KEY_SALT = "This is a salt for our key generator";

class FoodItem {
    private $description;
    private $price;

    public function __construct($description, $price) {
        $this->description = $description;
        $this->price = $price;
    }

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }

	public function __toString() {
		return $this->description.",".$this->price;
	}

}

class chowChooserEngine {
	
	function __construct() {
		
		
		$orderKeyExists = key_exists("orderKey", $_GET);
		$actionKeyExists = key_exists("action", $_GET);
			
		if(!$orderKeyExists && !$actionKeyExists) {
			// no action or orderKey means we're going to the welcome page
			$this->welcome();
		} else if ($orderKeyExists && !$actionKeyExists) {
			// orderKey exists but no actionKey mean swe're going to the view order page
			$this->view_order($_GET['orderKey']);
		} else if ($actionKeyExists) {
			// if we have an action key, we're going to now check if it's value is start_new:
				if ($_GET['action'] == "start_new") {
					// if it is, we're going to generate an orderKey and make a new order, then direct user to view that order
					$this->start_new_order();
				} else {
					// if it is not, we're going to check for an orderKey
					if ($orderKeyExists) {
						$this->handle_order_actions();
						// here we handle actions for the order
					} else {
						// we cannot handle actions without an order key, show welcome / error page
					}
				}
				
				
		} else {
			// for debug's sake we'll make an error page that we can only reach when all other checks fail in case we've borked logic
		}
	}
	
	function welcome($warning = null) {
		$swapArray['testMessage'] = "This is a message to swap into our template."; // this will replace the tag {{testMessage}} in the template welcome.html
		$swapArray['warningMessage'] = "" . $warning == "" ? "" : $warning . "<br /><br />";
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
}


$order = new chowChooserEngine();
?>
