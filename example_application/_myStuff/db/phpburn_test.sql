SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `albums`;

CREATE TABLE `albums` (
  `id_album` int(10) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id_album`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `extended_album`;

CREATE TABLE `extended_album` (
  `id_extended_album` int(10) NOT NULL auto_increment,
  `user` int(10) NOT NULL,
  PRIMARY KEY  (`id_extended_album`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `extended_user`;

CREATE TABLE `extended_user` (
  `id_extended_user` int(10) NOT NULL auto_increment,
  `id_user2` int(10) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id_extended_user`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

insert into `extended_user` values('1','0',''),
 ('2','0',''),
 ('3','0',''),
 ('4','0',''),
 ('5','0',''),
 ('6','0',''),
 ('7','4','Test Last Name'),
 ('8','5','Test Last Name'),
 ('9','6','Test Last Name'),
 ('10','7','Test Last Name'),
 ('11','8','Bueno'),
 ('12','9','Bueno'),
 ('13','10','Bueno'),
 ('14','11','Bueno');

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id_user` int(10) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;

insert into `users` values('1','a','a','2009-09-17 22:24:38'),
 ('2','b','b','2009-09-17 22:24:42'),
 ('3','c','c','2009-09-17 22:24:45'),
 ('4','Test Name','','2009-09-17 22:29:56'),
 ('5','','','2009-09-17 22:25:47'),
 ('6','','','2009-09-17 22:25:50'),
 ('7','','','2009-09-17 22:25:51'),
 ('8','','','2009-09-17 22:25:51'),
 ('9','','','2009-09-17 22:25:52'),
 ('10','','','2009-09-17 22:25:53'),
 ('11','','','2009-09-17 22:25:53'),
 ('12','','','2009-09-17 22:25:55'),
 ('13','Test Name','','2009-09-17 22:29:56'),
 ('14','Test Name','','2009-09-17 22:29:56'),
 ('15','Test Name','','2009-09-17 22:29:56'),
 ('16','Klederson','','2009-09-17 22:30:36'),
 ('17','Klederson','','2009-09-17 22:30:48'),
 ('18','Klederson','','2009-09-17 22:30:49'),
 ('19','Klederson','','2009-09-17 22:30:50');

DROP TABLE IF EXISTS `users2`;

CREATE TABLE `users2` (
  `id_user2` int(10) NOT NULL auto_increment,
  `id_user` int(10) NOT NULL,
  `name_user` varchar(255) NOT NULL,
  PRIMARY KEY  (`id_user2`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

insert into `users2` values('1','0',''),
 ('2','0',''),
 ('3','0',''),
 ('4','4','Test Name User'),
 ('5','13','Test Name User'),
 ('6','14','Test Name User'),
 ('7','15','Test Name User'),
 ('8','16','Acid'),
 ('9','17','Acid'),
 ('10','18','Acid'),
 ('11','19','Acid');

SET FOREIGN_KEY_CHECKS = 1;
