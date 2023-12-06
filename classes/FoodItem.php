<?php
class FoodItem {
	 private $id;
    private $name;
    private $price;

    public function __construct($id, $name, $price) {
        $this->id = $id;
        $this->name = $name;
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
		return $this->name.",".$this->price;
	}

   /**
   * Retrieves a food item from the database by ID.
   */
	public static function getFoodItemFromId(int $id): FoodItem {
		$db = new Database();
		$statement = $db->mysqli->prepare("select * from food where id = (?)");
		$statement->bind_param('i', $id);
		$statement->execute();
		$food = mysqli_fetch_assoc($statement->get_result());
		$name = $food['name'];
		$price = $food['price'];
		return new FoodItem($id, $name, $price);
	}

}
?>
