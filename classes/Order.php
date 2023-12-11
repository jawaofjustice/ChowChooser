<?php

class Order {

   private Database $db;
   private int $id;
   private int $food_id;
   private int $lobby_id;
   private int $user_id;
   private int $quantity;

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

   public function getId(): int {
      return $this->id;
   }

   public function getFoodId(): int {
      return $this->food_id;
   }

   public function getUserId(): int {
      return $this->user_id;
   }

   public function __get($property) {
      if (property_exists($this, $property)) {
          return $this->$property;
      }
   }

	/**
   * Reads a record from the database by ID.
   *
   * @return Order An `Order` instance.
   */
   public static function readOrder(int $id): Order {
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

	/**
   * Reads all orders associated with a lobby.
   *
   * @return array<Order> A collection of `Order` instances.
   */
   public static function readLobbyOrders(int $lobbyId): array {
      $db = new Database();
      $statement = $db->mysqli->prepare("select * from order_item where lobby_id = (?)");
      $statement->bind_param('i', $lobbyId);
      $statement->execute();

      $orders = array();
      foreach ($statement->get_result() as $order) {
         $id = $order['id'];
         $quantity = $order['quantity'];
         $user_id = $order['user_id'];
         $lobby_id = $order['lobby_id'];
         $food_id = $order['food_id'];
         array_push($orders, new Order($db, $id, $quantity, $user_id, $lobby_id, $food_id));
      }

      return $orders;
   }

   /**
   * Reads orders placed by a specified user in a specified lobby.
	*
   * @return array<Order> An array of `Order` instances.
   */
   public static function readUserOrdersByLobby(int $userId, int $lobbyId): array {
      $db = new Database();
      $statement = $db->mysqli->prepare("select * from order_item where user_id = (?) and lobby_id = (?)");
      $statement->bind_param('ii', $userId, $lobbyId);
      $statement->execute();
      $orders = array();
      foreach ($statement->get_result() as $order) {
         $id = $order['id'];
         $quantity = $order['quantity'];
         $user_id = $order['user_id'];
         $lobby_id = $order['lobby_id'];
         $food_id = $order['food_id'];
         array_push($orders, new Order($db, $id, $quantity, $user_id, $lobby_id, $food_id));
      }
      return $orders;
   }

   public static function deleteOrderById(int $id): void {
      $db = new Database();
      $statement = $db->mysqli->prepare("delete from order_item where id = (?);");
      $statement->bind_param('i', $id);
      $statement->execute();
   }

}

?>
