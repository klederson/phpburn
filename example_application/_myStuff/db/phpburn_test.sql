SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `albums`;

CREATE TABLE `albums` (
  `id_album` int(10) NOT NULL auto_increment,
  `name` varchar(255) character set latin1 NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id_album`),
  KEY `id_album` (`id_album`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

insert into `albums` values('2','album test','2009-06-23 23:44:38');

DROP TABLE IF EXISTS `pictures`;

CREATE TABLE `pictures` (
  `id_pictures` int(10) NOT NULL auto_increment,
  `name` varchar(255) character set latin1 NOT NULL,
  `physical_name` varchar(255) character set latin1 NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `id_album` int(10) NOT NULL,
  PRIMARY KEY  (`id_pictures`),
  KEY `id_album` (`id_album`),
  CONSTRAINT `pictures_ibfk_1` FOREIGN KEY (`id_album`) REFERENCES `albums` (`id_album`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

insert into `pictures` values('2','picture one','myPic.jpg','2009-06-23 23:49:16','2'),
 ('3','picture two','myPic2.jpg','2009-06-23 23:49:29','2'),
 ('4','picture three','myPic3.jpg','2009-06-23 23:49:43','2');

DROP TABLE IF EXISTS `tags`;

CREATE TABLE `tags` (
  `id_tags` int(10) NOT NULL auto_increment,
  `name` varchar(255) character set latin1 NOT NULL,
  `enabled` enum('1','0') character set latin1 NOT NULL,
  PRIMARY KEY  (`id_tags`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

insert into `tags` values('1','test','1'),
 ('2','teste 2','1');

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `status` enum('waiting','active','suspense','blocked','deleted') NOT NULL default 'waiting',
  `id_album` int(10) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `album_id` (`id_album`),
  KEY `album_id_2` (`id_album`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

insert into `users` values('1','Klederson Bueno','admin','password','active','2'),
 ('2','PhpBURN','phpburn','password','active','0');

DROP TABLE IF EXISTS `rel_album_tags`;

CREATE TABLE `rel_album_tags` (
  `id_rel_album_tags` int(10) NOT NULL auto_increment,
  `id_album` int(10) NOT NULL,
  `id_tags` int(10) NOT NULL,
  PRIMARY KEY  (`id_rel_album_tags`),
  KEY `id_album` (`id_album`),
  KEY `id_tags` (`id_tags`),
  CONSTRAINT `rel_album_tags_ibfk_1` FOREIGN KEY (`id_tags`) REFERENCES `tags` (`id_tags`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `rel_album_tags_id_album` FOREIGN KEY (`id_album`) REFERENCES `albums` (`id_album`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

insert into `rel_album_tags` values('1','2','2'),
 ('2','2','1');

SET FOREIGN_KEY_CHECKS = 1;
