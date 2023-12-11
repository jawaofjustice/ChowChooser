<?php

class Lobby {

    private Database $db;
    private int $id;
    private int $admin_id;
    private string $name;
    private string|null $votingEndTime;
    private string $orderingEndTime;
    private int $status_id;
    private string $invite_code;

   function __construct(
      Database $db,
      int $id,
      int $admin_id,
      string $name,
      string|null $votingEndTime,
      string $orderingEndTime,
      int $status_id,
      string $invite_code
   ) {
        $this->db = new Database();
        $this->id = $id;
        $this->admin_id = $admin_id;
        $this->name = $name;
        $this->votingEndTime = $votingEndTime;
        $this->orderingEndTime = $orderingEndTime;
        $this->status_id = $status_id;
        $this->invite_code = $invite_code;
    }

   public function getInviteCode(): string {
      return $this->invite_code;
   }

   /**
   * Reads a lobby from the database by ID.
   */
    public static function readLobby(int $id): Lobby {
        $db = new Database();

        $statement = $db->mysqli->prepare("select * from lobby where lobby.id = (?)");
		$statement->bind_param('s', $id);
		$statement->execute();

        $lobbyArray = mysqli_fetch_assoc($statement->get_result());
        
        // Create new lobby object
        $lobby = new Lobby($db, $lobbyArray['id'], $lobbyArray['admin_id'], $lobbyArray['name'], $lobbyArray['voting_end_time'], $lobbyArray['ordering_end_time'], $lobbyArray['status_id'], $lobbyArray['invite_code']);

        // Make timestamp and date format
        date_default_timezone_set('America/New_York');
        $date = date('Y-m-d H:i:s');

      $voteEndTime = $lobby->getVotingEndTime();

      // the effective vote end time accounts for NULL voting_end_time
      if (is_null($voteEndTime)) {
         $effectiveVoteEndTime = null;
      } else {
         $effectiveVoteEndTime = new DateTime($voteEndTime);
      }

        // Check if current time is over voting end time
        if(new DateTime($date) > $effectiveVoteEndTime) {

            // Check if current time is over ordering end time
            if(new DateTime($date) > new DateTime($lobby->getOrderingEndTime())) {
                // Set lobby phase to "complete"
                $lobby->updateStatusId(3);
            } else {
                // in ordering phase
                $lobby->getWinningRestaurant();
                $lobby->deleteLoserRestaurants();
                $lobby->updateStatusId(2);
            }

        } else {
            // in voting phase
            $lobby->updateStatusId(1);
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

   /**
   * Reads all restaurants associated with this lobby.
   *
   * @return array<Restaurant> A collection of `Restaurant` instances.
   */
    public function getRestaurants(): Array {

        // fetch the restaurants assosiated with the lobby
        $statement = $this->db->mysqli->prepare('SELECT restaurant_id
                                                    FROM lobby_restaurant
                                                    WHERE lobby_id = (?)');
        $statement->bind_param('i', $this->id);
        $statement->execute();

        // create an object for each restaurant and populate the restaurants array with them
        $restaurants = array();
        foreach ($statement->get_result() as $restaurant) {
            array_push($restaurants, Restaurant::readRestaurant($restaurant['restaurant_id']));
        }

        // set the votesByLobby in each restaurant using the votes table (method contained in restaurant class)
        foreach($restaurants as $restaurant) {
            $restaurant->setVotesByLobby($this->id);
        }

        // order the array of restaurants by how many votes they have
        usort($restaurants, function($a, $b){
            return $b->getVotesByLobby($this->id) - $a->getVotesByLobby($this->id);
        });

        return $restaurants;
    }

   /**
   * Reads the most popularly voted restaurant associated with this lobby.
   *
   * @return Restaurant The winning restaurant.
   */
   public function getWinningRestaurant(): Restaurant {
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

      // populate an array with the results because there will be
      // more than one result if there is a tie
      $voteArray = array();
        foreach ($statement->get_result() as $votesForRestaurant) {
            array_push($voteArray, $votesForRestaurant);
        }

      // single winner with no ties -> return the winner
      if (count($voteArray) == 1) {
         $winningRestaurantId = $voteArray[0]['restaurant'];
         return Restaurant::readRestaurant($winningRestaurantId);
      }

      // no one voted -> can return any restaurant
      if (empty($voteArray)) {
         // safest way I found to get first element from a collection
         return array_values($this->getRestaurants())[0];
      }

      // deal with a tie, so start by getting the vote of the admin,
      // which will be the tie-breaker (if it exists)
      $statement = $this->db->mysqli->prepare("SELECT restaurant_id 
         FROM vote JOIN lobby ON vote.lobby_id = lobby.id
         WHERE lobby.admin_id = vote.user_id AND lobby_id = (?)");
      $statement->bind_param('i', $this->id);
      $statement->execute();

      $adminVoteRow = mysqli_fetch_assoc($statement->get_result());

      // admin did not vote -> return arbitrary restaurant
      if (is_null($adminVoteRow)) {
         $winningRestaurantId = $voteArray[0]['restaurant'];
         return Restaurant::readRestaurant($winningRestaurantId);
      }

      $adminVote = $adminVoteRow['restaurant_id'];

      // check if the restaurant the admin voted for is in the list of tied restaurants
      $found = false;
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

      return Restaurant::readRestaurant($winningRestaurantId);
   }


   /**
   * Deletes all records relating this lobby with any non-winning restaurants.
   */
   public function deleteLoserRestaurants(): void {
      $winningRestaurantId = $this->getWinningRestaurant()->getId();
      // mysql statement to delete every other restaurant from lobby_restaurant that isn't the winner
      $statement = $this->db->mysqli->prepare("
         DELETE FROM
         lobby_restaurant
         WHERE lobby_id = (?) AND restaurant_id != (?)");
      $statement->bind_param('ii', $this->id, $winningRestaurantId);
      $statement->execute();
   }
    
    public function getId(): int {
        return $this->id;
    }

    public function getAdminId(): int {
        return $this->admin_id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getVotingEndTime(): string|null {
        return $this->votingEndTime;
    }

    public function getOrderingEndTime(): string {
        return $this->orderingEndTime;
    }

    public function getStatusId(): int {
        return $this->status_id;
    }

   /**
   * Updates the status of a lobby.
   *
   * @param int The new status ID.
   */
   public function updateStatusId(int $status_id): void {
      $statement = $this->db->mysqli->prepare('
         UPDATE lobby
         SET status_id = (?)
         WHERE id = (?)');
      $statement->bind_param('ii', $status_id, $this->id);
      $statement->execute();
      $this->status_id = $status_id;
   }

   /**
   * Create a new record in the database's `lobby` table.
   *
   * @param string $lobbyName The lobby's human-readable name.
   * @param string|null $votingEndTime Date and time at which the voting phase will end. `null` skips the voting phase.
   * @param string $orderingEndTime Date and time at which the ordering phase will end.
   * @param array<Restaurant> $restaurants Restaurants to vote for during the voting phase.
   * @return int ID of the new lobby's record.
   */
   public static function createLobby(
      string $lobbyName,
      string|null $votingEndTime,
      string $orderingEndTime,
      array $restaurants
   ): int {
      $db = new Database();
      // skip to "ordering" status if skipping the voting phase
      $status_id = is_null($votingEndTime) ? 2 : 1;
      $admin_id = $_SESSION['user']->getId();

      $invite_code = Lobby::generateInviteCode();
      $statement = $db->mysqli->prepare("
         insert into lobby
         (name, voting_end_time, ordering_end_time, admin_id, status_id, invite_code) values
         ( (?), (?), (?), (?), (?), (?) );");
      $statement->bind_param('sssiis', $lobbyName, $votingEndTime, $orderingEndTime, $admin_id, $status_id, $invite_code);
      $statement->execute();

      // read the ID of the lobby we just created
      $lobbyId = $db->mysqli->insert_id;

      //insert admin as a member of the newly created lobby
      $statement = $db->mysqli->prepare('
         INSERT INTO lobby_user (lobby_id, user_id)
         VALUES ( (?), (?) )');
      $statement->bind_param('ii', $lobbyId, $admin_id);
      $statement->execute();

      foreach ($restaurants as $restaurant) {
         $restaurantId = $restaurant->getId();
         $statement = $db->mysqli->prepare('
            INSERT INTO lobby_restaurant (lobby_id, restaurant_id)
            VALUES ( (?), (?) )');
         $statement->bind_param('ii', $lobbyId, $restaurantId);
         $statement->execute();
      }

      return $lobbyId;
   }

   /**
   * Reads a lobby from the database by invite code.
   *
   * @return Lobby|null The matching lobby. Returns `null` if none were found.
   */
   public static function readLobbyByInviteCode(string $inviteCode): Lobby|null {
      $db = new Database;

      $inviteCode = strtoupper($inviteCode);

      $statement = $db->mysqli->prepare("
         SELECT *
         FROM lobby
         WHERE invite_code = (?)
         LIMIT 1");
      $statement->bind_param('s', $inviteCode);
      $statement->execute();

      $result = mysqli_fetch_assoc($statement->get_result());

      if (is_null($result)) {
         return null;
      }

      return new Lobby(
         $db,
         $result['id'],
         $result['admin_id'],
         $result['name'],
         $result['voting_end_time'],
         $result['ordering_end_time'],
         $result['status_id'],
         $result['invite_code']
      );
   }

   /**
   * @return string The invite code: six uppercase hexadecimal characters.
   */
   private static function generateInviteCode(): string {
      $longCode = sha1(rand(0, 20_000));
      return strtoupper(substr($longCode, 34));
   }

   public static function deleteLobby(int $id) {
      //echo("delete lobby: ".$id);
      $db = new Database();

      $statement = $db->mysqli->prepare("DELETE FROM lobby_restaurant WHERE lobby_id = (?)");
      $statement->bind_param('i', $id);
      $statement->execute();

      $statement = $db->mysqli->prepare("DELETE FROM lobby_user WHERE lobby_id = (?)");
      $statement->bind_param('i', $id);
      $statement->execute();

      $statement = $db->mysqli->prepare("DELETE FROM order_item WHERE lobby_id = (?)");
      $statement->bind_param('i', $id);
      $statement->execute();

      $statement = $db->mysqli->prepare("DELETE FROM vote WHERE lobby_id = (?)");
      $statement->bind_param('i', $id);
      $statement->execute();

      $statement = $db->mysqli->prepare("DELETE FROM lobby WHERE id = (?)");
      $statement->bind_param('i', $id);
      $statement->execute();
   }

   public static function deleteUserFromLobby(int $userId, int $lobbyId) {
      $db = new Database();

      $statement = $db->mysqli->prepare("DELETE FROM lobby_user WHERE user_id = (?) AND lobby_id = (?)");
      $statement->bind_param('ii', $userId, $lobbyId);
      $statement->execute();

   }

	public static function getUsersLobbies(int $id) {
		$db = new Database();
		$statement = $db->mysqli->prepare("select distinct l.*, lu.user_id, u.username, s.description 
			from lobby as l inner join lobby_user as lu on l.id = lu.lobby_id inner join status as s 
			on l.status_id=s.id inner join user as u on lu.user_id=u.id where lu.user_id = (?)");
		$statement->bind_param('s', $id);
		$statement->execute();

		$all_user_lobbies="";


		foreach ($statement->get_result() as $lobby) {
			if ($lobby['admin_id']==$lobby['user_id'])
				$adminIcon = "and administrated ";
			else
				$adminIcon = "";

			// Display lobby information based on the current phase
			if ($lobby['description']=="Voting")
				$phase_end_message="Voting ends at ".$lobby['voting_end_time'];
			else if ($lobby['description']=="Ordering")
				$phase_end_message="Ordering ends at ".$lobby['ordering_end_time'];
			else if ($lobby['description']=="Completed")
				$phase_end_message="Everyone has finished ordering. Enjoy your meal!";
			else
				$phase_end_message="ERROR: Invalid lobby status";

			// Append each lobby to a list of lobbies for the user
			$swapArray['lobbyId'] = $lobby['id'];
			$swapArray['lobbyName'] = $lobby['name'];
			$swapArray['lobbyUsername'] = $_SESSION['user']->getUsername() == $lobby['username'] ? "You" : $lobby['username'];
			$swapArray['lobbyAdminIcon'] = $adminIcon;
			$swapArray['lobbyPhaseEndMessage'] = $phase_end_message;
			
			$all_user_lobbies .= ChowChooserEngine::load_template('lobbyRow', $swapArray);
		}
		
		if ($all_user_lobbies == "") {
			return "<tr><td><h2>You have no active lobbies!</h2></td></tr>";
		}

		return $all_user_lobbies;
	}
}

?>
