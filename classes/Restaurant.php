<?php

class Restaurant {

    private Database $db;
    private $id;
    private $name;
    private int $votesByLobby;

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

	public function getId(): int {
		return $this->id;
	}

   public function getName(): string {
      return $this->name;
   }

   /**
   * Retrieves all records from the `restaurant` table.
   *
   * @return array<Restaurant> A collection of `Restaurant` instances.
   */
   public static function getAllRestaurants(): array {
      $db = new Database();
      $statement = $db->mysqli->prepare("select * from restaurant");
      $statement->execute();

      $restaurants = array();
      foreach ($statement->get_result() as $restaurant) {
         $id = $restaurant['id'];
         $name = $restaurant['name'];
         array_push($restaurants, new Restaurant($db, $id, $name));
      }

      return $restaurants;
   }

   /**
   * Retrieves a record from the database's `restaurant` table by ID.
   */
    public static function getRestaurantFromDatabase(int $id) {
        $db = new Database();
        $statement = $db->mysqli->prepare("select * from restaurant where restaurant.id = (?)");
		$statement->bind_param('s', $id);
		$statement->execute();

        $restaurantArray = mysqli_fetch_assoc($statement->get_result());

        return new Restaurant($db, $restaurantArray['id'], $restaurantArray['name']);

    }

   /**
   * Calculates the number of votes placed for this restaurant in a particular lobby.
   * Stores the number of votes in the `votesByLobby` property.
   */
    public function setVotesByLobby(int $lobbyId) {

        $statement = $this->db->mysqli->prepare("SELECT COUNT(restaurant_id) votes 
                                                    FROM chow_chooser.vote
                                                    WHERE lobby_id = (?) AND restaurant_id = (?)
                                                    GROUP BY restaurant_id
                                                    order by votes desc");
		$statement->bind_param('ii', $lobbyId, $this->id);
		$statement->execute();

      $result = mysqli_fetch_assoc($statement->get_result());

      if (is_null($result)) {
         $this->votesByLobby = 0;
         return;
      }

      $this->votesByLobby = $result['votes'];
    }

    public function getVotesByLobby($lobbyId): int {
        return $this->votesByLobby;
    }

}

?>
