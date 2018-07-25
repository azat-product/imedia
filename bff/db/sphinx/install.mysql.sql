
-- индексирование
DROP TABLE IF EXISTS `bff_sphinx`;
CREATE TABLE `bff_sphinx` (
  `counter_id` tinyint(1) unsigned NOT NULL default 0,
  `indexed` timestamp NOT NULL default '0000-00-00 00:00:00',
  UNIQUE KEY  (`counter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

-- словоформы
DROP TABLE IF EXISTS `bff_sphinx_wordforms`;
CREATE TABLE `bff_sphinx_wordforms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module` tinyint(1) unsigned NOT NULL default 0,
  `src` varchar(250) NOT NULL default '',
  `dest` varchar(250) NOT NULL default '',
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;