use chow_chooser;
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
   -- phone varchar(250) not null,
   -- address varchar(250) not null,
   -- website varchar(250) not null,
   primary key (id)
);

insert into restaurant (name) values
   ("Marco's Pizza"),
   ("Burger King"),
   ("McDonald's"),
   ("Leo's");

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
   ("tree@email.com","tree","treetreetree");

create table lobby (
   id int unsigned not null AUTO_INCREMENT,
   admin_id int unsigned not null,
   name varchar(250) not null,
   status_id int unsigned not null,
   primary key (id),
   foreign key (admin_id) references user(id),
   foreign key (status_id) references status(id)
);

insert into lobby (name, status_id, admin_id) values
   ("Work", 1, 1),
   ("English class", 1, 1),
   ("Science class", 1, 1),
   ("Sandwich club", 2, 2),
   ("Chess club", 2, 2);

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
   (2, 1);

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
   ("Massive Whopper", 19.99, 1),
   ("Small Whopper", 9.99, 1),
   ("Celery", 3.24, 2),
   ("Tomato (organic)", 4.24, 2),
   ("Half-eaten fish", 1.50, 3),
   ("Smelly muffin", 8.52, 3);

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
