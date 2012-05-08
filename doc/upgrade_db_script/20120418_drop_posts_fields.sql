ALTER TABLE  `posts` DROP  `updated_at`;
ALTER TABLE  `posts` ADD  `del` BOOL NOT NULL DEFAULT  '0';
