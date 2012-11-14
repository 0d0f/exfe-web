ALTER TABLE `invitations` ADD COLUMN `invited_by`  bigint(20) unsigned;
ALTER TABLE `identities`  ADD COLUMN `unreachable` tinyint(1) default 0;
ALTER TABLE `devices`     ADD COLUMN `unreachable` tinyint(1) default 0;
UPDATE `invitations` SET `invited_by`=`by_identity_id`;
