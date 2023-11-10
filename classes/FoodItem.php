<?php
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
?>
