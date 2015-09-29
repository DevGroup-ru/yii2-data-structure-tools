DROP TABLE IF EXISTS `product`;

CREATE TABLE `product` (
  `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name`  VARCHAR(255) NOT NULL,
  `sort_order` INT(11) NOT NULL,
  `packed_json_data` TEXT NOT NULL
);

DROP TABLE IF EXISTS `category`;

CREATE TABLE `category` (
  `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name`  VARCHAR(255) NOT NULL,
  `sort_order` INT(11) NOT NULL
);