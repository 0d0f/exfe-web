INSERT INTO `identities` (`id`, `provider`, `external_identity`, `created_at`, `updated_at`, `name`, `bio`, `avatar_file_name`, `avatar_content_type`, `avatar_file_size`, `avatar_updated_at`, `external_username`, `oauth_token`) VALUES (233,'email','tester_leonard@0d0f.com','2012-05-08 18:37:59',NULL,'Leonard Hofstadter','I am a physicist at CalTech and live with my best friend Sheldon.','https://twimg0-a.akamaihd.net/profile_images/1204136991/johnny-galecki-as-leonard-hofstadter.jpg','',0,'0000-00-00 00:00:00','tester_leonard@0d0f.com',NULL),(235,'email','tester_raj@0d0f.com','2012-05-08 18:50:49',NULL,'Rajesh Koothrappali','Give me a grasshopper and I\'m ready to go!','https://twimg0-a.akamaihd.net/profile_images/198817661/200px-Kunal-Nayyar.jpg','',0,'0000-00-00 00:00:00','tester_raj@0d0f.com',NULL),(174,'email','tester_sheldon@0d0f.com','2012-05-08 17:02:14',NULL,'Sheldon Cooper','Quite possibly the most intelligent human on the planet. Brilliant theoretical physicist.','https://twimg0-a.akamaihd.net/profile_images/365042597/sheldon.jpg','',0,'0000-00-00 00:00:00','tester_sheldon@0d0f.com',NULL),(234,'email','tester_howard@0d0f.com','2012-05-08 18:46:32',NULL,'Howard Wolowitz','My mom lives with me! Got that?','https://twimg0-a.akamaihd.net/profile_images/198833248/simon-helberg.jpg','',0,'0000-00-00 00:00:00','tester_howard@0d0f.com',NULL),(236,'twitter','575129929','2012-05-08 18:37:59',NULL,'Leonard Hofstadter','I am a physicist at CalTech and live with my best friend Sheldon.','https://twimg0-a.akamaihd.net/profile_images/1204136991/johnny-galecki-as-leonard-hofstadter.jpg','',0,'0000-00-00 00:00:00','0d0f_tester_leo',NULL),(237,'twitter','575215638','2012-05-08 18:50:49',NULL,'Rajesh Koothrappali','Give me a grasshopper and I\'m ready to go!','https://twimg0-a.akamaihd.net/profile_images/198817661/200px-Kunal-Nayyar.jpg','',0,'0000-00-00 00:00:00','0d0f_tester_raj',NULL),(238,'twitter','575131718','2012-05-08 17:02:14',NULL,'Sheldon Cooper','Quite possibly the most intelligent human on the planet. Brilliant theoretical physicist.','https://twimg0-a.akamaihd.net/profile_images/365042597/sheldon.jpg','',0,'0000-00-00 00:00:00','0d0f_tester_she',NULL),(239,'twitter','575216679','2012-05-08 18:46:32',NULL,'Howard Wolowitz','My mom lives with me! Got that?','https://twimg0-a.akamaihd.net/profile_images/198833248/simon-helberg.jpg','',0,'0000-00-00 00:00:00','0d0f_tester_how',NULL);
INSERT INTO `user_identity` (`identityid`, `userid`, `created_at`, `updated_at`, `status`, `activecode`) VALUES (233,143,'2012-05-08 18:37:59','0000-00-00 00:00:00',3,'a72b25411c2a638bbd776b523ab8ff121336473479'),(174,142,'2012-05-08 17:02:14','0000-00-00 00:00:00',3,'6c418150226fb67322f6672589866d311336467734'),(234,144,'2012-05-08 18:46:32','0000-00-00 00:00:00',3,'273a7e11b33c312022fe14835d6ac14a1336473992'),(235,145,'2012-05-08 18:50:49','0000-00-00 00:00:00',3,'21e91ec2c8c4df31ba718c208f05caf41336474249'),(236,143,'2012-05-08 18:37:59','0000-00-00 00:00:00',3,'a72b25411c2a638bbd776b523ab8ff121336473479'),(237,145,'2012-05-08 17:02:14','2012-05-13 15:20:45',3,'6c418150226fb67322f6672589866d311336467734'),(238,142,'2012-05-08 18:46:32','2012-05-13 15:20:18',3,'273a7e11b33c312022fe14835d6ac14a1336473992'),(239,144,'2012-05-08 18:50:49','2012-05-13 15:21:09',3,'21e91ec2c8c4df31ba718c208f05caf41336474249');
INSERT INTO `users` (`id`, `encrypted_password`, `password_salt`, `reset_password_token`, `remember_created_at`, `sign_in_count`, `current_sign_in_at`, `last_sign_in_at`, `current_sign_in_ip`, `last_sign_in_ip`, `created_at`, `updated_at`, `name`, `bio`, `avatar_file_name`, `avatar_content_type`, `avatar_file_size`, `avatar_updated_at`, `external_username`, `cookie_logintoken`, `cookie_loginsequ`, `auth_token`, `timezone`, `default_identity`) VALUES (143,'227d2609d6eb0d70fbdbac29b7c76552','e7cb94f30bae42e12827b10ba2605883',NULL,NULL,0,NULL,NULL,'10.211.55.2',NULL,'2012-05-09 11:03:52',NULL,'Leonard Hofstadter','I am a physicist at CalTech and live with my best friend Sheldon.','https://twimg0-a.akamaihd.net/profile_images/1204136991/johnny-galecki-as-leonard-hofstadter.jpg','',NULL,NULL,'','228978416ee36a3ecbb65ff4ea773244','4651c154cd734883530aa25c029e1c56','','+08:00',233),(142,'84b0cc8b74a12db0cacd6f8d2a0f7957','4c0bb7147f5e582a9b0ecde448017844',NULL,NULL,0,NULL,NULL,'10.211.55.2',NULL,'2012-05-13 23:05:46',NULL,'Sheldon Cooper','Quite possibly the most intelligent human on the planet. Brilliant theoretical physicist.','https://twimg0-a.akamaihd.net/profile_images/365042597/sheldon.jpg','',NULL,NULL,'','dfabab72c1b04978fb4a55bbea747960','cd07e42d7ffd7ef94ee5bb59a07884ed','98eddc9c0afc48087f722ca1419c8650','+08:00',174),(144,'1eeab8d2c9ddd2576d1443ff9c5e9a2d','58cca7634a9cc2375d9e55f480612cb7',NULL,NULL,0,NULL,NULL,'10.211.55.2',NULL,'2012-05-08 18:46:32',NULL,'Howard Wolowitz','My mom lives with me! Got that?','https://twimg0-a.akamaihd.net/profile_images/198833248/simon-helberg.jpg','',NULL,NULL,'','','','','+08:00',234),(145,'9cd8fd76d0dcce434bee949d495300d8','a7ae82a5fa2ed1da76c79accada95f9a',NULL,NULL,0,NULL,NULL,'10.211.55.2',NULL,'2012-05-08 18:50:49',NULL,'Rajesh Koothrappali','Give me a grasshopper and I\'m ready to go!','https://twimg0-a.akamaihd.net/profile_images/198817661/200px-Kunal-Nayyar.jpg','',NULL,NULL,'','','','','+08:00',235);
