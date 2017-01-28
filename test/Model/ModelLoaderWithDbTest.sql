-- Adminer 4.2.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

TRUNCATE `comments`;
INSERT INTO `comments` (`comment_id`, `project_id`, `comment`) VALUES
(1,	1,	'comment one'),
(2,	1,	'comment two'),
(3,	2,	'comment three'),
(4,	2,	'comment four');

DROP TABLE IF EXISTS `details`;
CREATE TABLE `details` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) NOT NULL,
  `text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`detail_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

TRUNCATE `details`;
INSERT INTO `details` (`detail_id`, `comment_id`, `text`) VALUES
(1,	1,	'detail one'),
(2,	2,	'detail two'),
(3,	3,	'detail three'),
(4,	4,	'detail four'),
(5,	1,	'comment five');

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) NOT NULL,
  PRIMARY KEY (`project_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

TRUNCATE `projects`;
INSERT INTO `projects` (`project_id`, `project_name`) VALUES
(1,	'one'),
(2,	'two');

-- 2016-06-16 13:40:43