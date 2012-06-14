CREATE TABLE `tokens` (
	`id` bigint(20) NOT NULL AUTO_INCREMENT,
	`token` varchar(255) NOT NULL,
	`action` varchar(255) NOT NULL,
	`identity_id` bigint(20) NOT NULL,
	`user_id` bigint(20) NOT NULL,
	`created_at` datetime NOT NULL,
	`expiration_date` datetime NOT NULL,
	`used_at` datetime NOT NULL,
	`detail` text DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
