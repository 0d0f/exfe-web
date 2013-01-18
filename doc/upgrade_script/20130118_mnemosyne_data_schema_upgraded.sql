CREATE TABLE `photos` (
  `id`               bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cross_id`         bigint(20) unsigned NOT NULL,
  `caption`          varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `by_identity_id`   bigint(20) unsigned NOT NULL,
  `created_at`       datetime NOT NULL,
  `updated_at`       datetime NOT NULL,
  `provider`         varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `external_id`      varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `location_lng`     char(10) DEFAULT NULL,
  `location_lat`     char(10) DEFAULT NULL,
  `fullsize_url`     varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `fullsize_width`   int(6) NOT NULL,
  `fullsize_height`  int(6) NOT NULL,
  `thumbnail_url`    varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `thumbnail_width`  int(6) NOT NULL,
  `thumbnail_height` int(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
