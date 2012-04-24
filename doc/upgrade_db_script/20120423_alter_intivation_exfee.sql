<<<<<<< HEAD
ALTER TABLE  `invitations` ADD  `exfee_updated_at` DATETIME NOT NULL;
=======
ALTER TABLE  `invitations` ADD  `exfee_updated_at` DATETIME NOT NULL ;
ALTER TABLE  `crosses` ADD  `exfee_id` bigint(20) NOT NULL ;
>>>>>>> api allow check

ALTER TABLE  `invitations` ADD INDEX (`exfee_updated_at`);

ALTER TABLE  `exfees` DROP  `updated_at`;
