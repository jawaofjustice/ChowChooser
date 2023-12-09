use chow_chooser;



DROP TABLE IF EXISTS `lobby_user`;
DROP TABLE IF EXISTS `order_item`;
DROP TABLE IF EXISTS `vote`;
DROP TABLE IF EXISTS `lobby_restaurant`;
DROP TABLE IF EXISTS `lobby`;
DROP TABLE IF EXISTS `status`;
DROP TABLE IF EXISTS `food`;
DROP TABLE IF EXISTS `restaurant`;
DROP TABLE IF EXISTS `user`;

create table status (
   id int unsigned not null auto_increment,
   description varchar(50) not null,
   primary key (id)
);

insert into status (description) values
   ("Voting"),
   ("Ordering"),
   ("Completed");

create table restaurant (
   id int unsigned not null auto_increment,
   name varchar(250) not null,
   address varchar(250) not null,
   phone varchar(250) not null,
   website varchar(250) not null,
   primary key (id)
);

insert into restaurant (name, address, phone, website) values
   ("Marco's Pizza", "1752 Plymouth Road, Ann Arbor MI 48105", "(734) 998-2600", "https://www.marcos.com"),
   ("Burger King", "4885 Washtenaw Ave, Ann Arbor, MI 48108", "(734) 434-8994", "https://www.bk.com"),
   ("McDonald's", "2310 W Stadium Blvd, Ann Arbor, MI 48103", "(734) 761-9087", "https://www.mcdonalds.com/us/en-us.html"),
   ("Olive Garden", "445 E Eisenhower Pkwy, Ann Arbor, MI 48108", "(734) 663-6875", "https://www.olivegarden.com/home"),
   ("Palm Palace", "2370 Carpenter Rd, Ann Arbor, MI 48108", "(734) 606-0706", "https://www.palmpalace.com"),
   ("Zingerman’s Deli", "422 Detroit St, Ann Arbor, MI 48104", "(734) 663-3354", "https://www.zingermansdeli.com"),
   ("Chipotle Mexican Grill", "3354 E Washtenaw Ave Ste A, Ann Arbor, MI 48104", "(734) 975-9912", "https://www.chipotle.com");

create table user (
   id int unsigned not null auto_increment,
   email varchar(45) not null,
   password varchar(45) not null,
   username varchar(45) not null,
   primary key (id)
);

insert into user (email, password, username) values
   ("dev","dev","dev"),
   ("quat@quat.com","quat","QuatCoretto"),
   ("jerma@email.com","jerma","JeremyElbertson"),
   ("tree@email.com","tree","treetreetree"),
   ("syshook@wccnet.edu","Caleb","Caleb"),
   ("anhubbard@wccnet.edu","Andrew","Andrew"),
   ("kabutler@wccnet.edu","Kieran","Kieran"),
   ("gadrake@wccnet.edu","Garrett","Garrett");

create table lobby (
   id int unsigned not null AUTO_INCREMENT,
   admin_id int unsigned not null,
   name varchar(250) not null,
   voting_end_time Datetime null,
   ordering_end_time Datetime not null,
   status_id int unsigned not null,
   invite_code char(6) not null,
   primary key (id),
   foreign key (admin_id) references user(id),
   foreign key (status_id) references status(id)
);

insert into lobby (name, admin_id, voting_end_time, ordering_end_time, status_id, invite_code) values
   ("Work", 1, "2024-01-29 12:00:00", "2023-12-12 12:00:00", 1, "ABCDEF"),
   ("English class", 1, "2023-11-08 12:00:00", "2023-12-12 12:00:00", 2, "ACDFEG"),
   ("Science class", 1, "2023-10-29 12:00:00", "2023-11-01 12:00:00", 3, "AAAAAA"),
   ("Sandwich club", 2, "2023-11-08 12:00:00", "2023-12-12 12:00:00", 2, "BABABA"),
   ("Chess club", 2, "2023-11-08 12:00:00", "2023-12-12 12:00:00", 2, "AB1235");

create table lobby_user (
   lobby_id int unsigned not null,
   user_id int unsigned not null,
   primary key (lobby_id, user_id),
   foreign key (lobby_id) references lobby(id),
   foreign key (user_id) references user(id)
);

insert into lobby_user (lobby_id, user_id) values
   (1, 1),
   (1, 2),
   (1, 3),
   (2, 1),
   (2, 2),
   (2, 3),
   (3, 3);

create table lobby_restaurant (
   lobby_id int unsigned not null,
   restaurant_id int unsigned not null,
   primary key (lobby_id, restaurant_id),
   foreign key (lobby_id) references lobby(id),
   foreign key (restaurant_id) references restaurant(id)
);

insert into lobby_restaurant (lobby_id, restaurant_id) values
   (1, 1),
   (1, 2),
   (1, 4),
   (1, 6),
   (2, 7),
   (3, 6),
   (3, 7),
   (4, 3),
   (4, 6),
   (5, 2),
   (5, 4);

create table vote (
   id int unsigned not null AUTO_INCREMENT,
   lobby_id int unsigned not null,
   restaurant_id int unsigned not null,
   user_id int unsigned not null,
   primary key (id),
   foreign key (lobby_id) references lobby(id),
   foreign key (user_id) references user(id),
   foreign key (restaurant_id) references restaurant(id)
);

insert into vote (lobby_id, user_id, restaurant_id) values
   (1, 1, 1),
   (1, 2, 1);

-- decimal(4,2) means that the decimal contains four digits
-- total, two of which are reserved for after the decimal.
-- Ranges from -99.99 to 99.99
create table food (
   id int unsigned not null AUTO_INCREMENT,
   name varchar(250) not null,
   price decimal(4,2) not null,
   restaurant_id int unsigned not null,
   primary key (id),
   foreign key (restaurant_id) references restaurant(id)
);

insert into food (name, price, restaurant_id) values
   ("Grande Feast", 26.99, 1),
   ("XLarge Pizza", 20.89, 1),
   ("Large Pizza", 17.99, 1),
   ("Medium Pizza", 15.09, 1),
   ("Small Pizza", 12.19, 1),
   ("Pizzoli", 6.99, 1),
   ("Sub Sandwich", 6.99, 1),
   ("Breadsticks", 5.99, 1),
   ("Wings", 12.99, 1),
   ("Salad", 6.99, 1),
   ("Whopper", 5.99, 2),
   ("Triple Whopper", 8.39, 2),
   ("Bacon Double Cheeseburger", 3.59, 2),
   ("Hamburger", 1.79, 2),
   ("Classic Fries", 3.19, 2),
   ("Onion Rings", 2.79, 2),
   ("Mozzarella Sticks", 2.59, 2),
   ("12PC Ghost Pepper Chicken Fries", 4.99, 2),
   ("16PC Chicken Nuggets", 4.49, 2),
   ("Big Fish", 4.79, 2),
   ("McCrispy", 5.89, 3),
   ("McCrispy Meal", 9.39, 3),
   ("Big Mac Meal", 11.09, 3),
   ("Double Quarter Pounder with Cheese Meal", 5.89, 3),
   ("10PC Chicken McNuggets Meal", 11.09, 3),
   ("20PC Chicken McNuggets", 9.59, 3),
   ("40PC Chicken McNuggets", 17.79, 3),
   ("French Fries", 3.29, 3),
   ("Tour of Italy", 20.79, 4),
   ("Chicken Marsala Fettuccine", 19.99, 4),
   ("Chicken Tortellini Alfredo", 21.49, 4),
   ("Spaghetti and Meatballs", 16.78, 4),
   ("Fettuccine Alfredo", 16.29, 4),
   ("Shrimp Scampi", 19.99, 4),
   ("Herb-Grilled Salmon", 20.99, 4),
   ("Grilled Chicken Margherita", 19.99, 4),
   ("Breadsticks", 4.49, 4),
   ("Tiramisu", 8.99, 4),
   ("Moroccan Chicken", 19.99, 5),
   ("Chicken Shawarma", 18.99, 5),
   ("Chicken Cream of Mushrooms", 19.99, 5),
   ("Lamb Chops", 28.99, 5),
   ("Palm Kafta", 21.99, 5),
   ("Beef Ghallaba", 20.99, 5),
   ("Moroccan Stew", 21.99, 5),
   ("Steak Saute", 20.99, 5),
   ("Grilled Salmon", 22.99, 5),
   ("Vegetarian Deluxe", 16.99, 5),
   ("Reuben", 18.99, 6),
   ("Potato Chips", 2.50, 6),
   ("Potato Salad", 5.50, 6),
   ("Coleslaw", 3.99, 6),
   ("BLT", 12.99, 6),
   ("Tuna Melt", 15.50, 6),
   ("Caesar Salad", 10.99, 6),
   ("Greek Island Salad", 15.99, 6),
   ("Chicken Bowl", 9.65, 7),
   ("Steak Salad", 12.50, 7),
   ("Veggie Burrito", 8.25, 7),
   ("Chips and Guacamole", 4.15, 7);

-- the order keyword is reserved,
-- so we can just call the table order_item
create table order_item (
   id int unsigned not null AUTO_INCREMENT,
   quantity int unsigned not null,
   user_id int unsigned not null,
   lobby_id int unsigned not null,
   food_id int unsigned not null,
   primary key (id),
   foreign key (lobby_id) references lobby(id),
   foreign key (user_id) references user(id),
   foreign key (food_id) references food(id)
);

insert into order_item (food_id, quantity, user_id, lobby_id) values
   (1, 1, 1, 1),
   (1, 1, 2, 1);
