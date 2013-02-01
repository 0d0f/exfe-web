ALTER TABLE `exfees`      ADD COLUMN `name`   text;

ALTER TABLE `crosses`     ADD COLUMN `closed` tinyint(1) DEFAULT 0;

ALTER TABLE `invitations` ADD COLUMN `remark` varchar(255);
