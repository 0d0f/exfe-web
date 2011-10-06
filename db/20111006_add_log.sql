CREATE TABLE IF NOT EXISTS `logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `from_obj` varchar(20) NOT NULL,
  `from_id` bigint(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `to_obj` varchar(20) NOT NULL,
  `to_id` bigint(20) NOT NULL,
  `to_field` varchar(20) NOT NULL,
  `change_summy` varchar(50) NOT NULL,
  `meta` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;
