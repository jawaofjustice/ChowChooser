<?php

class Lobby {

    private Database $db;
    private $id;
    private $admin_id;
    private $name;
    private $votingEndTime;
    private $orderingEndTime;
    private $status_id;

    //, $id, $admin_id, $name, $status_id
    function __construct($db) {
        $this->db = new Database();
        //$this->id = $id;
        //$this->admin_id = $admin_id;
        //$this->name = $name;
        //$this->status_id = $status_id;
    }

    public function getLobbyFromDatabase(int $id) {

        $statement = $this->db->mysqli->prepare("select * from lobby where lobby.id = (?)");
		$statement->bind_param('s', $id);
		$statement->execute();

        $lobbyArray = mysqli_fetch_assoc($statement->get_result());

        $this->id = $lobbyArray['id'];
        $this->admin_id = $lobbyArray['admin_id'];
        $this->name = $lobbyArray['name'];
        $this->votingEndTime = $lobbyArray['voting_end_time'];
        $this->orderingEndTime = $lobbyArray['ordering_end_time'];
        $this->status_id = $lobbyArray['status_id'];

    }

    public function getRestaurants(): Array {
        $statement = $this->db->mysqli->prepare('select restaurant_id from lobby_restaurant where lobby_restaurant.lobby_id = (?)');
        $statement->bind_param('s', $this->id);
        $statement->execute();

        foreach ($statement->get_result() as $restaurant) {
            $restaurants[] = $restaurant;
        }

        return $restaurants;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getAdminId() {
        return $this->admin_id;
    }

    public function getName() {
        return $this->name;
    }

    public function getVotingEndTime() {
        return $this->votingEndTime;
    }

    public function getOrderingEndTime() {
        return $this->orderingEndTime;
    }

    public function getStatusId() {
        return $this->status_id;
    }  

    public function setId($id) {
        $this->id = $id;
    }

    public function setStatusId($status_id) {
        $this->status_id = $status_id;
     }

    public function setName($name) {
        $this->name = $name;
     }

     public function setStatus($status) {
        $this->status = $status;
     }

   public static function createLobbyInDatabase(
      string $lobbyName,
      string|null $votingEndTime,
      string $orderingEndTime
   ): void {
      $db = new Database();
      // skip to "ordering" status if skipping the voting phase
      $status_id = is_null($votingEndTime) ? 2 : 1;
      $admin_id = $_SESSION['user']->getId();

      $statement = $db->mysqli->prepare("
         insert into lobby
         (name, voting_end_time, ordering_end_time, admin_id, status_id) values
         ( (?), (?), (?), (?), 1 );");
      $statement->bind_param('sssi', $lobbyName, $votingEndTime, $orderingEndTime, $admin_id);

      $statement->execute();
   }

}

?>
