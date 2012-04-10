ALTER TABLE  `crosses` ADD  `date_word` VARCHAR( 34 ) NOT NULL ,
ADD  `time_word` VARCHAR( 34 ) NOT NULL ,
ADD  `date` VARCHAR( 10 ) NOT NULL ,
ADD  `time` VARCHAR( 8 ) NOT NULL ,
ADD  `output` TINYINT NOT NULL ;

ALTER TABLE  `crosses` CHANGE  `output`  `outputformat` TINYINT( 4 ) NOT NULL COMMENT  '0 OutputFormat, 1  OutputOrigin';

