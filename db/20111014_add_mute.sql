CREATE TABLE IF NOT EXISTS `mute` (
  `id` bigint(20) NOT NULL,
  `object` varchar(20) NOT NULL,
  `object_id` bigint(20) NOT NULL,
  `sender_id` bigint(20) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
