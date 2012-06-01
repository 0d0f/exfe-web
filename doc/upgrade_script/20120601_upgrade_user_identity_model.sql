ALTER TABLE `users` DROP COLUMN `avatar_content_type`;
ALTER TABLE `users` DROP COLUMN `avatar_file_size`;
ALTER TABLE `users` DROP COLUMN `avatar_updated_at`;
ALTER TABLE `users` DROP COLUMN `external_username`;

ALTER TABLE `identities` DROP COLUMN `avatar_content_type`;
ALTER TABLE `identities` DROP COLUMN `avatar_file_size`;
ALTER TABLE `identities` DROP COLUMN `avatar_updated_at`;
