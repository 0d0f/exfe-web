ALTER TABLE  `exfees` ADD  `updated_at` DATETIME NOT NULL ;
ALTER TABLE  `exfees` ADD INDEX  `update_at_exfeeid` (  `id` ,  `updated_at` )
