<?php

class Restaurant {

    private Database $db;
    private $id;
    private $name;

    public function __construct(Database $db, $id, $name) {
        $this->db = $db;
        $this->id = $id;
        $this->name = $name;
    }

    public function __get($property) {
		if (property_exists($this, $property)) {
            return $this->$property;
        }
	}

    //public function getName() {
      //  return $this->name;
    //}

    public static function getRestaurantFromDatabase(int $id) {
        $db = new Database();
        $statement = $db->mysqli->prepare("select * from restaurant where restaurant.id = (?)");
		$statement->bind_param('s', $id);
		$statement->execute();

        $restaurantArray = mysqli_fetch_assoc($statement->get_result());

        return new Restaurant($db, $restaurantArray['id'], $restaurantArray['name']);

        $this->id = $restaurantArray['id'];
        $this->name = $restaurantArray['name'];

    }

}

?>