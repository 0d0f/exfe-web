CREATE TABLE IF NOT EXISTS `user_relations` (
  `id` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `r_identityid` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `external_identity` varchar(255) NOT NULL,
  `provider` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

