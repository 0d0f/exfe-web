ALTER TABLE `invitations` ADD COLUMN `token_used_at` datetime NOT NULL DEFAULT 0;

UPDATE `invitations` SET `token_used_at` = NOW() WHERE `tokenexpired` = 1;

ALTER TABLE `invitations` DROP COLUMN `tokenexpired`;
