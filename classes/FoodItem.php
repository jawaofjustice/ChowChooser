<?php
class FoodItem {
	private int $id;
    private string $name;
    private mixed $price;

    public function __construct(int $id, string $name, mixed $price) {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
    }

   public function getPrice(): mixed {
      return $this->price;
   }

   public function getName(): string {
      return $this->name;
   }

   /**
   * Reads a food item from the database by ID.
   */
	public static function readFoodItem(int $id): FoodItem {
		$db = new Database();
		$statement = $db->mysqli->prepare("select * from food where id = (?)");
		$statement->bind_param('i', $id);
		$statement->execute();
		$food = mysqli_fetch_assoc($statement->get_result());
		$name = $food['name'];
		$price = $food['price'];
		return new FoodItem($id, $name, $price);
	}

}
?>
