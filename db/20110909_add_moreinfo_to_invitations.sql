ALTER TABLE  `invitations` ADD  `via` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE  `invitations` ADD  `by_identity_id` BIGINT(255) NOT NULL ;
ALTER TABLE  `invitations` ADD  `lat` double NOT NULL;
ALTER TABLE  `invitations` ADD  `lng` double NOT NULL;


