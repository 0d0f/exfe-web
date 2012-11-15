ALTER TABLE `invitations` ADD COLUMN `invited_by`  bigint(20) unsigned;
ALTER TABLE `identities`  ADD COLUMN `unreachable` tinyint(1) default 0;
ALTER TABLE `devices`     ADD COLUMN `unreachable` tinyint(1) default 0;
UPDATE `invitations` SET `invited_by`=`by_identity_id`;
CREATE TABLE `geoip_blocks` (
    `id`           bigint(20)            NOT NULL AUTO_INCREMENT,
    `start_ip_num` int(11)      unsigned NOT NULL,
    `end_ip_num`   int(11)      unsigned NOT NULL,
    `loc_id`       int(7)       unsigned NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;
CREATE TABLE `geoip_locations` (
    `loc_id`       int(7)       unsigned NOT NULL,
    `country`      varchar(7)            NOT NULL,
    `region`       varchar(7)            NOT NULL,
    `city`         varchar(255)          NOT NULL,
    `postal_code`  varchar(10)           NOT NULL,
    `latitude`     float(10,6)           NOT NULL,
    `longitude`    float(10,6)           NOT NULL,
    `metro_code`   varchar(7)            NOT NULL,
    `area_code`    varchar(7)            NOT NULL,
    PRIMARY KEY (`loc_id`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;
