ALTER TABLE  `users` DROP  `authentication_token`;
ALTER TABLE  `users` ADD  `auth_token` VARCHAR( 32 ) NOT NULL ;
ALTER TABLE  `users` ADD INDEX (  `auth_token` );
