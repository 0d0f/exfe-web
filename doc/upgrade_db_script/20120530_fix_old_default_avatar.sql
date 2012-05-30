UPDATE `identities` SET `avatar_file_name` = '' WHERE `avatar_file_name` = 'default.png' OR `avatar_file_name` is NULL OR `avatar_file_name` LIKE '%gravatar%';
UPDATE `users` SET `avatar_file_name` = '' WHERE `avatar_file_name` = 'default.png' OR `avatar_file_name` LIKE 'http://%';
