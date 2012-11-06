ALTER TABLE `crosses`       DROP COLUMN `time_type`;
ALTER TABLE `users`         DROP COLUMN `default_identity`;
ALTER TABLE `users`         DROP COLUMN `auth_token`;
ALTER TABLE `user_identity` ADD  COLUMN `order` INT(3) DEFAULT 999 NOT NULL;
DROP TABLE  `tokens`;
