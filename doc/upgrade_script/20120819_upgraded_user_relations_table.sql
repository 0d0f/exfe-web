ALTER TABLE `user_relations` CHANGE `id` `id` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT;
ALTER TABLE `user_relations` ADD CLOUMN `external_username` VARCHAR(255);
ALTER TABLE `user_relations` ADD COLUMN `avatar_filename` VARCHAR(255);
