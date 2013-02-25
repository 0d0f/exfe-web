CREATE TABLE `response_options` (
  `id`             bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `object_type`    varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `option`         varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `responses` (
  `id`             bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `object_type`    varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `object_id`      bigint(20) unsigned NOT NULL,
  `response_id`    bigint(20) unsigned NOT NULL,
  `by_identity_id` bigint(20) unsigned NOT NULL,
  `created_at`     datetime NOT NULL,
  `updated_at`     datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


INSERT INTO `response_options` SET `object_type` = 'photo', `option` = '';
INSERT INTO `response_options` SET `object_type` = 'photo', `option` = 'LIKE';
