<?php

class Vote {

    private Database $db;
    private $lobbyId;
    private $restaurantId;
    private $userId;


    public function __construct($db, $lobbyId, $restaurantId, $userId) {
        $this->db = new Database();
        $this->lobbyId = $lobbyId;
        $this->restaurantId = $restaurantId;
        $this->userId = $userId;
    }

    public static function toggleVote($userId, $restaurantId, $lobbyId) {
        $db = new Database();

        //Check whether the user has voted before
        if (Vote::getUsersVote($userId, $lobbyId) > 0) {

            //do not write to database
            //tell user that they have already voted in this lobby
            //echo("y'already voted, son! (southern accent)");
            
            if (Vote::getUsersVote($userId, $lobbyId) == $restaurantId) {

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

    public static function getRestaurantsAndVotes($lobbyId): array {
        $db = new Database();

        $statement = $db->mysqli->prepare("SELECT r.name, COUNT(v.restaurant_id) Votes
                                            FROM vote v join restaurant r on v.restaurant_id = r.id
                                            WHERE v.lobby_id = (?)
                                            GROUP BY r.name
                                            ORDER BY Votes DESC");
                                            
        $statement->bind_param("i", $lobbyId);
        $statement->execute();

        foreach ($statement->get_result() as $row) {
            $results[] = $row;
        }

        return $results;
    }

    public static function getVotesForRestaurant($restaurantId, $lobbyId): int {
        $db = new Database();

        $statement = $db->mysqli->prepare("SELECT COUNT(id) FROM vote WHERE restaurant_id = (?) AND lobby_id = (?)");
        $statement->bind_param("ii", $restaurantId, $lobbyId);
        $statement->execute();

        $voteNum = mysqli_fetch_assoc($statement->get_result());
        return $voteNum['COUNT(id)'];
    }

    public static function getUsersVote($id, $lobby): int {
        $db = new Database();

		$statement = $db->mysqli->prepare("SELECT * FROM vote WHERE lobby_id = (?) AND user_id = (?)");
        $statement->bind_param("ii", $lobby, $id);
        $statement->execute();
        $result = $statement->get_result();

		if ($result->num_rows > 0) {
			$vote = mysqli_fetch_assoc($result);
            return $vote["restaurant_id"];
		}

        return 0;
    }

    static function userHasVoted($id, $lobby): bool {
		$db = new Database();

		$statement = $db->mysqli->prepare("SELECT * FROM vote WHERE lobby_id = (?) AND user_id = (?)");
        $statement->bind_param("ii", $lobby, $id);
        $statement->execute();
        $result = $statement->get_result();

		if ($result->num_rows > 0) {
			return true;
		}

		return false;

	}

}

?>