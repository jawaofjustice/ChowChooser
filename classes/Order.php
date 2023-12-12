<?php

class Order {

   private Database $db;
   private int $id;
   private int $foodId;
   private int $lobbyId;
   private int $userId;
   private int $quantity;

   function __construct(
      Database $db,
      int $id,
      int $quantity,
      int $userId,
      int $lobbyId,
      int $foodId
   ) {
      $this->db = new Database();
      $this->id = $id;
      $this->quantity = $quantity;
      $this->userId = $userId;
      $this->lobbyId = $lobbyId;
      $this->foodId = $foodId;
   }

   public function getId(): int {
      return $this->id;
   }

   public function getFoodId(): int {
      return $this->foodId;
   }

   public function getUserId(): int {
      return $this->userId;
   }

   public function getQuantity(): int {
      return $this->quantity;
   }

	/**
   * Reads all orders associated with a lobby.
   *
   * @return array<Order> A collection of `Order` instances.
   */
   public static function readLobbyOrders(int $lobbyId): array {
      $db = new Database();
      $statement = $db->mysqli->prepare("select * from order_item where lobby_id = (?) order by user_id");
      $statement->bind_param('i', $lobbyId);
      $statement->execute();

      $orders = array();
      foreach ($statement->get_result() as $order) {
         $id = $order['id'];
         $quantity = $order['quantity'];
         $userId = $order['user_id'];
         $lobbyId = $order['lobby_id'];
         $foodId = $order['food_id'];
         array_push($orders, new Order($db, $id, $quantity, $userId, $lobbyId, $foodId));
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
         $userId = $order['user_id'];
         $lobbyId = $order['lobby_id'];
         $foodId = $order['food_id'];
         array_push($orders, new Order($db, $id, $quantity, $userId, $lobbyId, $foodId));
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
