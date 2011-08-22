ALTER TABLE  `invitations` ADD  `tokenexpired` BOOL NOT NULL ;
ALTER TABLE  `invitations` CHANGE  `tokenexpired`  `tokenexpired` TINYINT( 1 ) NOT NULL DEFAULT  '0'
