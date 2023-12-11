<?php

class Vote {

    private Database $db;
    private int $lobbyId;
    private int $restaurantId;
    private int $userId;


   public function __construct(
      Database $db,
      int $lobbyId,
      int $restaurantId,
      int $userId
   ) {
        $this->db = new Database();
        $this->lobbyId = $lobbyId;
        $this->restaurantId = $restaurantId;
        $this->userId = $userId;
    }

   /**
   * Toggles a user's vote for a restaurant in a lobby.
   *
   * If this vote already exists, it will be deleted.
   * Otherwise, the vote will be recorded.
   */
   public static function toggleVote(
      int $userId,
      int $restaurantId,
      int $lobbyId
   ): void {
        $db = new Database();

        //Check whether the user has voted before
        if (Vote::readVote($userId, $lobbyId) > 0) {

            //do not write to database
            //tell user that they have already voted in this lobby
            //echo("y'already voted, son! (southern accent)");
            
            if (Vote::readVote($userId, $lobbyId) == $restaurantId) {

                $statement = $db->mysqli->prepare("DELETE FROM vote WHERE user_id = (?) AND lobby_id = (?)");
                $statement->bind_param("ii", $userId, $lobbyId);
                $statement->execute();

            } 
        } else {

            $statement = $db->mysqli->prepare("INSERT INTO vote (lobby_id, restaurant_id, user_id) VALUES (?, ?, ?)");
            $statement->bind_param("iii", $lobbyId, $restaurantId, $userId);
            $statement->execute();

        }

        //print_r(Vote::getRestaurantsAndVotes($lobbyId));

    }

    public static function getRestaurantsAndVotes(int $lobbyId): array {
        $db = new Database();

        $statement = $db->mysqli->prepare("SELECT r.name, COUNT(v.restaurant_id) Votes
                                            FROM vote v join restaurant r on v.restaurant_id = r.id
                                            WHERE v.lobby_id = (?)
                                            GROUP BY r.name
                                            ORDER BY Votes DESC");
                                            
        $statement->bind_param("i", $lobbyId);
        $statement->execute();

        $results = array();
        foreach ($statement->get_result() as $row) {
            $results[] = $row;
        }

        return $results;
    }

    public static function readVotesForRestaurant(int $restaurantId, int $lobbyId): int {
        $db = new Database();

        $statement = $db->mysqli->prepare("SELECT COUNT(id) FROM vote WHERE restaurant_id = (?) AND lobby_id = (?)");
        $statement->bind_param("ii", $restaurantId, $lobbyId);
        $statement->execute();

        $voteNum = mysqli_fetch_assoc($statement->get_result());
        return $voteNum['COUNT(id)'];
    }

   /**
   * Retrieve a user's vote from a specific lobby.
   *
   * @return int ID of the restaurant that the user has voted for. Returns zero if the user has not voted in this lobby.
   */
    public static function readVote(int $userId, int $lobbyId): int {
        $db = new Database();

		$statement = $db->mysqli->prepare("SELECT * FROM vote WHERE lobby_id = (?) AND user_id = (?)");
        $statement->bind_param("ii", $lobbyId, $userId);
        $statement->execute();
        $result = $statement->get_result();

		if ($result->num_rows > 0) {
			$vote = mysqli_fetch_assoc($result);
            return $vote["restaurant_id"];
		}

        return 0;
    }

   /**
   * Returns whether or not a user has voted in a lobby.
   */
    public static function userHasVoted(int $userId, int $lobbyId): bool {
		$db = new Database();

		$statement = $db->mysqli->prepare("SELECT * FROM vote WHERE lobby_id = (?) AND user_id = (?)");
        $statement->bind_param("ii", $lobbyId, $userId);
        $statement->execute();
        $result = $statement->get_result();

		if ($result->num_rows > 0) {
			return true;
		}

		return false;

	}

}

?>
