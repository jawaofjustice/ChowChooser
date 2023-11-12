<?php

class Restaurant {

    private Database $db;
    private $id;
    private $name;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function __get($property) {
		if (property_exists($this, $property)) {
            return $this->$property;
        }
	}

    //public function getName() {
      //  return $this->name;
    //}

    public function getRestaurantFromDatabase(int $id) {

        $statement = $this->db->mysqli->prepare("select * from restaurant where restaurant.id = (?)");
		$statement->bind_param('s', $id);
		$statement->execute();

        $restaurantArray = mysqli_fetch_assoc($statement->get_result());

        $this->id = $restaurantArray['id'];
        $this->name = $restaurantArray['name'];

    }

}

?>