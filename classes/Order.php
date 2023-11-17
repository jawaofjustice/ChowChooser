<?php

class Order {

    private Database $db;
    private int $id;
    private int $food_id;
    private int $lobby_id;
    private int $user_id;
    private int $quantity;

    //, $id, $admin_id, $name, $status_id
    function __construct(
        Database $db,
        int $id,
        int $quantity,
        int $user_id,
        int $lobby_id,
        int $food_id
    ) {
        $this->db = new Database();
        $this->id = $id;
        $this->quantity = $quantity;
        $this->user_id = $user_id;
        $this->lobby_id = $lobby_id;
        $this->food_id = $food_id;
    }

    public static function getOrderFromDatabase(int $id) {
        $db = new Database();
        $statement = $db->mysqli->prepare("select * from order_item where id = (?)");
		$statement->bind_param('i', $id);
		$statement->execute();

        $orderArray = mysqli_fetch_assoc($statement->get_result());

        $db = $orderArray['db'];
        $id = $orderArray['id'];
        $quantity = $orderArray['quantity'];
        $user_id = $orderArray['user_id'];
        $lobby_id = $orderArray['lobby_id'];
        $food_id = $orderArray['food_id'];

        return new Order(
            $db,
            $id,
            $quantity,
            $user_id,
            $lobby_id,
            $food_id,
        );
    }

    public static function getOrdersFromUserId(int $userId) {
        $db = new Database();
        $statement = $db->mysqli->prepare("select * from order_item where user_id = (?)");
		$statement->bind_param('i', $userId);
		$statement->execute();
    }

}

?>
