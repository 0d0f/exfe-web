LOCK TABLES `identities` WRITE;
/*!40000 ALTER TABLE `identities` DISABLE KEYS */;
INSERT INTO `identities` VALUES (233,'email','leonard@exfe.com','2012-05-08 18:37:59',NULL,'Leonard Hofstadter','I am a physicist at CalTech and live with my best friend Sheldon.','https://twimg0-a.akamaihd.net/profile_images/1204136991/johnny-galecki-as-leonard-hofstadter.jpg','',0,'0000-00-00 00:00:00','leonard@exfe.com',NULL),(235,'email','raj@exfe.com','2012-05-08 18:50:49',NULL,'Rajesh Koothrappali','Give me a grasshopper and I\'m ready to go!','https://twimg0-a.akamaihd.net/profile_images/198817661/200px-Kunal-Nayyar.jpg','',0,'0000-00-00 00:00:00','raj@exfe.com',NULL),(174,'email','sheldon@exfe.com','2012-05-08 17:02:14',NULL,'Sheldon Cooper','Quite possibly the most intelligent human on the planet. Brilliant theoretical physicist.','https://twimg0-a.akamaihd.net/profile_images/365042597/sheldon.jpg','',0,'0000-00-00 00:00:00','sheldon@exfe.com',NULL),(234,'email','howard@exfe.com','2012-05-08 18:46:32',NULL,'Howard Wolowitz','My mom lives with me! Got that?','https://twimg0-a.akamaihd.net/profile_images/198833248/simon-helberg.jpg','',0,'0000-00-00 00:00:00','howard@exfe.com',NULL);
/*!40000 ALTER TABLE `identities` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `user_identity` WRITE;
/*!40000 ALTER TABLE `user_identity` DISABLE KEYS */;
INSERT INTO `user_identity` VALUES (233,143,'2012-05-08 18:37:59',3,'a72b25411c2a638bbd776b523ab8ff121336473479'),(174,142,'2012-05-08 17:02:14',3,'6c418150226fb67322f6672589866d311336467734'),(234,144,'2012-05-08 18:46:32',3,'273a7e11b33c312022fe14835d6ac14a1336473992'),(235,145,'2012-05-08 18:50:49',3,'21e91ec2c8c4df31ba718c208f05caf41336474249');
/*!40000 ALTER TABLE `user_identity` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (143,'227d2609d6eb0d70fbdbac29b7c76552','e7cb94f30bae42e12827b10ba2605883',NULL,NULL,0,NULL,NULL,'10.211.55.2',NULL,'2012-05-08 18:37:59',NULL,'Leonard Hofstadter','I am a physicist at CalTech and live with my best friend Sheldon.','https://twimg0-a.akamaihd.net/profile_images/1204136991/johnny-galecki-as-leonard-hofstadter.jpg','',NULL,NULL,'','','','','+08:00',233),(142,'84b0cc8b74a12db0cacd6f8d2a0f7957','4c0bb7147f5e582a9b0ecde448017844',NULL,NULL,0,NULL,NULL,'10.211.55.2',NULL,'2012-05-08 17:02:14',NULL,'Sheldon Cooper','Quite possibly the most intelligent human on the planet. Brilliant theoretical physicist.','https://twimg0-a.akamaihd.net/profile_images/365042597/sheldon.jpg','',NULL,NULL,'','','','98eddc9c0afc48087f722ca1419c8650','+08:00',174),(144,'1eeab8d2c9ddd2576d1443ff9c5e9a2d','58cca7634a9cc2375d9e55f480612cb7',NULL,NULL,0,NULL,NULL,'10.211.55.2',NULL,'2012-05-08 18:46:32',NULL,'Howard Wolowitz','My mom lives with me! Got that?','https://twimg0-a.akamaihd.net/profile_images/198833248/simon-helberg.jpg','',NULL,NULL,'','','','','+08:00',234),(145,'9cd8fd76d0dcce434bee949d495300d8','a7ae82a5fa2ed1da76c79accada95f9a',NULL,NULL,0,NULL,NULL,'10.211.55.2',NULL,'2012-05-08 18:50:49',NULL,'Rajesh Koothrappali','Give me a grasshopper and I\'m ready to go!','https://twimg0-a.akamaihd.net/profile_images/198817661/200px-Kunal-Nayyar.jpg','',NULL,NULL,'','','','','+08:00',235);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
