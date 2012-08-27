CREATE TABLE `devices` (
    `id`                 bigint(20)   unsigned NOT NULL AUTO_INCREMENT,
    `name`               varchar(255) COLLATE  utf8_unicode_ci DEFAULT NULL,
    `brand`              varchar(255) COLLATE  utf8_unicode_ci DEFAULT NULL,
    `model`              varchar(255) COLLATE  utf8_unicode_ci DEFAULT NULL,
    `os_version`         varchar(255) COLLATE  utf8_unicode_ci DEFAULT NULL,
    `browser_version`    varchar(255) COLLATE  utf8_unicode_ci DEFAULT NULL,
    `description`        text         COLLATE  utf8_unicode_ci DEFAULT NULL,
    `status`             tinyint(4)   NOT NULL,
    `user_id`            bigint(20)   unsigned NOT NULL,
    `first_connected_at` datetime     NOT NULL,
    `last_connected_at`  datetime     NOT NULL,
    `disconnected_at`    datetime     NOT NULL,
    `udid`               varchar(255) COLLATE  utf8_unicode_ci NOT NULL,
    `push_token`         varchar(255) COLLATE  utf8_unicode_ci NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
