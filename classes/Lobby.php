<?php

class Lobby {

    private Database $db;
    private $id;
    private $admin_id;
    private $name;
    private $votingEndTime;
    private $orderingEndTime;
    private $status_id;

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
        
        // Create new lobby object
        $lobby = new Lobby($db, $lobbyArray['id'], $lobbyArray['admin_id'], $lobbyArray['name'], $lobbyArray['voting_end_time'], $lobbyArray['ordering_end_time'], $lobbyArray['status_id']);

        // Make timestamp and date format
        date_default_timezone_set('America/New_York');
        $date = date('Y-m-d H:i:s');
        //echo($date);

        // Check if current time is over voting end time
        if(new DateTime($date) > new DateTime($lobby->getVotingEndTime())) {

            // Check if current time is over ordering end time
            if(new DateTime($date) > new DateTime($lobby->getOrderingEndTime())) {
                // This lobby is completed
                $lobby->setStatusId(3);
            } else {
                // in ordering phase
                $lobby->determineWinner();
                $lobby->setStatusId(2);
            }

        } else {
            // in voting phase
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
        $statement = $this->db->mysqli->prepare('SELECT restaurant_id, COUNT(restaurant_id) votes 
                                                    FROM chow_chooser.vote
                                                    WHERE lobby_id = (?)
                                                    GROUP BY restaurant_id
                                                    order by votes desc');
        $statement->bind_param('i', $this->id);
        $statement->execute();

        foreach ($statement->get_result() as $restaurant) {
            $restaurants[] = $restaurant;
        }

        return $restaurants;
    }

    public function determineWinner() {

        // determine the winning restaurant and make it the edit the lobby-restaurant table
        $statement = $this->db->mysqli->prepare("SELECT restaurant_id restaurant, COUNT(restaurant_id) votes 
                                                    FROM chow_chooser.vote
                                                    WHERE lobby_id = (?)
                                                    GROUP BY restaurant_id
                                                    HAVING COUNT(restaurant_id) = (
                                                        SELECT COUNT(restaurant_id)
                                                        FROM chow_chooser.vote
                                                        WHERE lobby_id = (?)
                                                        GROUP BY restaurant_id
                                                        ORDER BY COUNT(restaurant_id) DESC
                                                        LIMIT 1
                                                    )
                                                    order by votes desc");
        $statement->bind_param('ii', $this->id, $this->id);
        $statement->execute();

        // populate an array with the results because there will be more than one result if there is a tie
        foreach ($statement->get_result() as $votesForRestaurant) {
            $voteArray[] = $votesForRestaurant;
        }

        // if the length of voteArray is larger than one there is a tie
        if(count($voteArray) > 1) {

            // deal with a tie

            // start by getting the vote of the admin
            $statement = $this->db->mysqli->prepare("SELECT restaurant_id 
                                                        FROM vote JOIN lobby ON vote.lobby_id = lobby.id
                                                        WHERE lobby.admin_id = vote.user_id AND lobby_id = (?)");
            $statement->bind_param('i', $id);
            $statement->execute();

            $adminVote = mysqli_fetch_assoc($statement->get_result())['restaurant_id'];

            // check if the restaurant the admin voted for is in the list of tied restaurants
            foreach($voteArray as $candidate) {
                if(in_array($adminVote, $candidate)) {
                    $found = true;
                }
            }

            // if the admin did vote for one of the tied restaurants, set winningRestaurantId to the restaurant id that the admin voted for
            // otherwise set it to the first restaurant in the list of tied restaurants 
            if($found == true) {
                $winningRestaurantId = $adminVote; 
            } else {
                $winningRestaurantId = $voteArray[0]['restaurant'];
            }

        } else {
            // if we are here that means there was no tie. Set the winningRestaurantId
            $winningRestaurantId = $voteArray[0]['restaurant'];
        }

        $this->setWinningRestaurant($winningRestaurantId);

    }

    public function setWinningRestaurant($winningRestaurantId) {

        // mysql statement to delete every other restaurant from lobby_restaurant that isn't the winner
        $statement = $this->db->mysqli->prepare("DELETE FROM lobby_restaurant WHERE lobby_id = (?) AND restaurant_id != (?)");
		$statement->bind_param('ii', $this->id, $winningRestaurantId);
		$statement->execute();

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

    public function setStatusId($status_id) {
        $this->status_id = $status_id;
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

      // retrieve the ID of the lobby we just created
      $lobbyId = $db->mysqli->insert_id;

      //insert admin as a member of the newly created lobby
      $statement = $db->mysqli->prepare('
         INSERT INTO lobby_user (lobby_id, user_id)
         VALUES ( (?), (?) )');
      $statement->bind_param('ii', $lobbyId, $admin_id);
      $statement->execute();

   }

}

?>
