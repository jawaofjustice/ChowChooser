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
    function __construct($db, $id, $admin_id, $name, $votingEndTime, $orderingEndTime, $status_id) {
        $this->db = new Database();
        $this->id = $id;
        $this->admin_id = $admin_id;
        $this->name = $name;
        $this->votingEndTime = $votingEndTime;
        $this->orderingEndTime = $orderingEndTime;
        $this->status_id = $status_id;
    }

    public static function getLobbyFromDatabase(int $id) {
        $db = new Database();

        $statement = $db->mysqli->prepare("select * from lobby where lobby.id = (?)");
		$statement->bind_param('s', $id);
		$statement->execute();

        $lobbyArray = mysqli_fetch_assoc($statement->get_result());
        
        //Create new lobby object
        $lobby = new Lobby($db, $lobbyArray['id'], $lobbyArray['admin_id'], $lobbyArray['name'], $lobbyArray['voting_end_time'], $lobbyArray['ordering_end_time'], $lobbyArray['status_id']);

        //Make timestamp and date format
        date_default_timezone_set('America/New_York');
        $date = date('Y-m-d H:i:s');
        //echo($date);

        //Check if current time is over voting end time
        if(new DateTime($date) > new DateTime($lobby->getVotingEndTime())) {

            //Check if current time is over ordering end time
            if(new DateTime($date) > new DateTime($lobby->getOrderingEndTime())) {
                //This lobby is completed
                $lobby->setStatusId(3);
            } else {
                //in ordering phase
                $lobby->setStatusId(2);
            }

        } else {
            //in voting phase
            $lobby->setStatusId(1);
        }

        //Check whether the status_id in the database is correct or not
        if($lobby->getStatusId() != $lobbyArray['status_id']) {

            $statusId = $lobby->getStatusId();
            $id = $lobby->getId();

            //Update the row in the database
            $statement = $db->mysqli->prepare('UPDATE lobby SET status_id = (?) WHERE id = (?)');
            $statement->bind_param('ii', $statusId, $id);
            $statement->execute();

        }

        return $lobby;
        
    }

    //returns an array of arrays containing [restartant_id=?]
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
         ( (?), (?), (?), (?), (?) );");
      $statement->bind_param('sssii', $lobbyName, $votingEndTime, $orderingEndTime, $admin_id, $status_id);

      $statement->execute();
   }

}

?>
