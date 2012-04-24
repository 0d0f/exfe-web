ALTER TABLE  `invitations` ADD  `exfee_updated_at` DATETIME NOT NULL;

ALTER TABLE  `invitations` ADD INDEX (`exfee_updated_at`);

ALTER TABLE  `exfees` DROP  `updated_at`;
